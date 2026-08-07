package ke.go.kicc.engine.data

import jakarta.persistence.*
import jakarta.validation.Valid
import jakarta.validation.constraints.Min
import jakarta.validation.constraints.NotNull
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.notify.NotificationService
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import java.security.SecureRandom
import java.time.Instant

@Entity
@Table(name = "bookings")
class Booking(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false, unique = true)
    var bookingReference: String = "",

    @Column(nullable = false)
    var userId: Long = 0,

    var exhibitionId: Long? = null,
    var boothId: Long? = null,

    @Column(nullable = false)
    var quantity: Int = 1,

    @Column(nullable = false)
    var subtotal: Double = 0.0,

    @Column(nullable = false)
    var tax: Double = 0.0,

    @Column(nullable = false)
    var total: Double = 0.0,

    @Column(nullable = false)
    var currency: String = "KES",

    @Column(nullable = false)
    var status: String = "PENDING",

    var notes: String? = null,
    var paidAt: String? = null,
    var cancelledAt: String? = null,

    @Column(nullable = false)
    var createdAt: String = ""
)

interface BookingRepository : JpaRepository<Booking, Long> {
    fun findByUserIdOrderByIdDesc(userId: Long): List<Booking>

    fun findAllByOrderByIdDesc(): List<Booking>
}

data class BookingRequest(
    @field:NotNull
    val boothId: Long,

    @field:Min(1)
    val quantity: Int = 1,

    val notes: String? = null
)

data class BookingStatusRequest(
    @field:NotNull
    val status: String
)

@Service
class BookingService(
    private val bookingRepository: BookingRepository,
    private val boothRepository: BoothRepository,
    private val auditLogService: AuditLogService,
    private val notificationService: NotificationService,
    private val userRepository: UserRepository
) {

    private val random = SecureRandom()

    fun list(user: User): List<Booking> =
        if (user.tier == Role.EXHIBITOR) bookingRepository.findByUserIdOrderByIdDesc(user.id!!)
        else bookingRepository.findAllByOrderByIdDesc()

    @Transactional
    fun create(user: User, req: BookingRequest): Booking {
        val booth = boothRepository.findById(req.boothId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Booth not found") }
        if (booth.status != "available") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Booth is not available")
        }
        val remaining = booth.maxQuantity - booth.bookedQuantity
        if (remaining < req.quantity) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Only $remaining unit(s) of this booth remain")
        }
        booth.bookedQuantity += req.quantity
        boothRepository.save(booth)

        val subtotal = booth.price * req.quantity
        val tax = subtotal * 0.16
        val booking = bookingRepository.save(
            Booking(
                bookingReference = "KICC-" + generateReference(),
                userId = user.id!!,
                exhibitionId = booth.exhibitionId,
                boothId = booth.id,
                quantity = req.quantity,
                subtotal = subtotal,
                tax = tax,
                total = subtotal + tax,
                currency = "KES",
                status = "PENDING",
                notes = req.notes,
                createdAt = Instant.now().toString()
            )
        )
        auditLogService.record(user.id!!, "BOOKING_CREATED", null, "reference=${booking.bookingReference}, booth=${booth.id}, qty=${req.quantity}")
        userRepository.findAll()
            .filter { it.tier != Role.EXHIBITOR && it.id != user.id }
            .forEach { admin ->
                notificationService.notify(
                    admin.id!!,
                    "BOOKING_CREATED",
                    "New booking ${booking.bookingReference}",
                    "${booth.boothNumber} (${booth.name}) — ${req.quantity} × KES ${booth.price.toLong()} = KES ${booking.total.toLong()}",
                    "by ${user.email}, boothId=${booth.id}"
                )
            }
        return booking
    }

    @Transactional
    fun setStatus(user: User, id: Long, newStatus: String): Booking {
        val booking = bookingRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Booking not found") }
        val allowed = when {
            user.tier == Role.EXHIBITOR ->
                booking.userId == user.id && booking.status == "PENDING" && newStatus == "CANCELLED"
            else -> newStatus in setOf("PENDING", "CONFIRMED", "PAID", "CANCELLED", "REFUNDED")
        }
        if (!allowed) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Status change not allowed")
        }
        val freesCapacity = booking.status != "CANCELLED" && booking.status != "REFUNDED" &&
            (newStatus == "CANCELLED" || newStatus == "REFUNDED")
        if (freesCapacity) {
            booking.boothId?.let { boothId ->
                boothRepository.findById(boothId).ifPresent { booth ->
                    booth.bookedQuantity = (booth.bookedQuantity - booking.quantity).coerceAtLeast(0)
                    boothRepository.save(booth)
                }
            }
        }
        booking.status = newStatus
        if (newStatus == "CANCELLED") booking.cancelledAt = Instant.now().toString()
        if (newStatus == "PAID") booking.paidAt = Instant.now().toString()
        bookingRepository.save(booking)
        auditLogService.record(user.id!!, "BOOKING_STATUS_CHANGED", null, "booking=${booking.bookingReference}, status=$newStatus")
        if (booking.userId != user.id!!) {
            notificationService.notify(
                booking.userId,
                "BOOKING_STATUS",
                "Booking ${booking.bookingReference} is now $newStatus",
                "Your booth booking was updated by ${user.email}",
                "status=$newStatus"
            )
        }
        return booking
    }

    private fun generateReference(): String {
        val alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
        return (1..8).map { alphabet[random.nextInt(alphabet.length)] }.joinToString("")
    }
}

@RestController
@RequestMapping("/api/bookings")
class BookingController(private val bookingService: BookingService) {

    @GetMapping
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun list(@AuthenticationPrincipal user: User): List<Booking> = bookingService.list(user)

    @PostMapping
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun create(@AuthenticationPrincipal user: User, @Valid @RequestBody req: BookingRequest): Booking =
        bookingService.create(user, req)

    @PatchMapping("/{id}/status")
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun setStatus(
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody req: BookingStatusRequest
    ): Booking = bookingService.setStatus(user, id, req.status)
}
