package ke.go.kicc.engine.notify

import jakarta.persistence.*
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.ResponseEntity
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import org.springframework.web.servlet.mvc.method.annotation.SseEmitter
import java.time.Instant
import java.util.concurrent.ConcurrentHashMap

@Entity
@Table(name = "notifications")
class Notification(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var userId: Long = 0,

    @Column(nullable = false)
    var type: String = "",

    @Column(nullable = false)
    var title: String = "",

    @Column(nullable = false, length = 1000)
    var message: String = "",

    @Column(length = 500)
    var detail: String? = null,

    var readAt: String? = null,

    @Column(nullable = false)
    var createdAt: String = ""
)

interface NotificationRepository : JpaRepository<Notification, Long> {
    fun findByUserIdOrderByIdDesc(userId: Long): List<Notification>

    fun countByUserIdAndReadAtIsNull(userId: Long): Long
}

data class NotificationView(
    val id: Long,
    val userId: Long,
    val type: String,
    val title: String,
    val message: String,
    val detail: String?,
    val readAt: String?,
    val createdAt: String
) {
    companion object {
        fun from(n: Notification) = NotificationView(
            id = n.id!!,
            userId = n.userId,
            type = n.type,
            title = n.title,
            message = n.message,
            detail = n.detail,
            readAt = n.readAt,
            createdAt = n.createdAt
        )
    }
}

@Service
class NotificationService(private val notificationRepository: NotificationRepository) {

    private val emitters: ConcurrentHashMap<Long, MutableSet<SseEmitter>> = ConcurrentHashMap()

    @Transactional
    fun notify(userId: Long, type: String, title: String, message: String, detail: String? = null) {
        val notification = notificationRepository.save(
            Notification(
                userId = userId,
                type = type,
                title = title,
                message = message,
                detail = detail,
                createdAt = Instant.now().toString()
            )
        )
        push(userId, NotificationView.from(notification))
    }

    fun list(userId: Long): List<NotificationView> =
        notificationRepository.findByUserIdOrderByIdDesc(userId).take(50).map { NotificationView.from(it) }

    fun unreadCount(userId: Long): Long = notificationRepository.countByUserIdAndReadAtIsNull(userId)

    @Transactional
    fun markRead(userId: Long, id: Long): NotificationView {
        val n = notificationRepository.findById(id)
            .orElseThrow { ResponseStatusException(org.springframework.http.HttpStatus.NOT_FOUND, "Notification not found") }
        if (n.userId != userId) {
            throw ResponseStatusException(org.springframework.http.HttpStatus.NOT_FOUND, "Notification not found")
        }
        if (n.readAt == null) n.readAt = Instant.now().toString()
        return NotificationView.from(notificationRepository.save(n))
    }

    @Transactional
    fun markAllRead(userId: Long): Int {
        val updated = notificationRepository.findByUserIdOrderByIdDesc(userId)
            .filter { it.readAt == null }
            .map { it.apply { readAt = Instant.now().toString() } }
        notificationRepository.saveAll(updated)
        return updated.size
    }

    fun stream(userId: Long): SseEmitter {
        val emitter = SseEmitter(1_800_000L) // 30 min timeout
        emitter.onCompletion { remove(userId, emitter) }
        emitter.onTimeout { remove(userId, emitter) }
        emitter.onError { remove(userId, emitter) }
        emitters.computeIfAbsent(userId) { ConcurrentHashMap.newKeySet() }.add(emitter)
        return emitter
    }

    private fun push(userId: Long, view: NotificationView) {
        emitters[userId]?.forEach { e ->
            try {
                e.send(SseEmitter.event().name("notification").data(view))
            } catch (_: Exception) {
                remove(userId, e)
            }
        }
    }

    private fun remove(userId: Long, emitter: SseEmitter) {
        emitters[userId]?.remove(emitter)
        if (emitters[userId]?.isEmpty() == true) emitters.remove(userId)
    }
}

@RestController
@RequestMapping("/api/notifications")
class NotificationController(private val notificationService: NotificationService) {

    @GetMapping
    fun list(@AuthenticationPrincipal user: User): List<NotificationView> =
        notificationService.list(user.id!!)

    @GetMapping("/unread-count")
    fun unreadCount(@AuthenticationPrincipal user: User): Map<String, Long> =
        mapOf("count" to notificationService.unreadCount(user.id!!))

    @PostMapping("/{id}/read")
    fun markRead(@AuthenticationPrincipal user: User, @PathVariable id: Long): NotificationView =
        notificationService.markRead(user.id!!, id)

    @PostMapping("/read-all")
    fun markAllRead(@AuthenticationPrincipal user: User): Map<String, Int> =
        mapOf("updated" to notificationService.markAllRead(user.id!!))

    @GetMapping("/stream")
    fun stream(@AuthenticationPrincipal user: User): SseEmitter = notificationService.stream(user.id!!)
}
