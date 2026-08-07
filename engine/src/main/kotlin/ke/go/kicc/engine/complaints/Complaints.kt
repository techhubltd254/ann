package ke.go.kicc.engine.complaints

import jakarta.persistence.*
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.notify.NotificationService
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import org.springframework.scheduling.annotation.Scheduled
import java.security.SecureRandom
import java.time.Instant

@Entity
@Table(name = "complaints")
class Complaint(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var referenceNo: String = "",

    @Column(nullable = false)
    var category: String = "OTHER",

    @Column(nullable = false)
    var subject: String = "",

    @Column(nullable = false, length = 4000)
    var message: String = "",

    var contactName: String? = null,
    var contactEmail: String? = null,

    @Column(nullable = false)
    var priority: String = "P3",

    @Column(nullable = false)
    var status: String = "OPEN",

    @Column(length = 4000)
    var resolutionNote: String? = null,

    var assigneeUserId: Long? = null,

    @Column(nullable = false)
    var slaDueAt: String = "",

    @Column(nullable = false)
    var createdAt: String = "",

    var updatedAt: String? = null
)

@Entity
@Table(name = "complaint_notes")
class ComplaintNote(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var complaintId: Long = 0,

    @Column(nullable = false)
    var authorUserId: Long = 0,

    @Column(nullable = false, length = 4000)
    var note: String = "",

    @Column(nullable = false)
    var internal: Boolean = true,

    @Column(nullable = false)
    var createdAt: String = ""
)

interface ComplaintRepository : JpaRepository<Complaint, Long> {
    fun findByReferenceNo(referenceNo: String): Complaint?

    fun findAllByOrderByIdDesc(): List<Complaint>
}

interface ComplaintNoteRepository : JpaRepository<ComplaintNote, Long> {
    fun findAllByComplaintIdOrderByIdAsc(complaintId: Long): List<ComplaintNote>
}

data class ComplaintView(
    val id: Long,
    val referenceNo: String,
    val category: String,
    val subject: String,
    val message: String,
    val contactName: String?,
    val contactEmail: String?,
    val priority: String,
    val status: String,
    val resolutionNote: String?,
    val assigneeUserId: Long?,
    val slaDueAt: String,
    val overdue: Boolean,
    val createdAt: String,
    val updatedAt: String?,
    val notesCount: Int
) {
    companion object {
        fun from(c: Complaint, notesCount: Int) = ComplaintView(
            id = c.id!!,
            referenceNo = c.referenceNo,
            category = c.category,
            subject = c.subject,
            message = c.message,
            contactName = c.contactName,
            contactEmail = c.contactEmail,
            priority = c.priority,
            status = c.status,
            resolutionNote = c.resolutionNote,
            assigneeUserId = c.assigneeUserId,
            slaDueAt = c.slaDueAt,
            overdue = c.status != "RESOLVED" && c.status != "CLOSED" &&
                runCatching { Instant.parse(c.slaDueAt).isBefore(Instant.now()) }.getOrDefault(false),
            createdAt = c.createdAt,
            updatedAt = c.updatedAt,
            notesCount = notesCount
        )
    }
}

data class ComplaintNoteView(
    val id: Long,
    val authorUserId: Long,
    val note: String,
    val internal: Boolean,
    val createdAt: String
) {
    companion object {
        fun from(n: ComplaintNote) = ComplaintNoteView(
            id = n.id!!,
            authorUserId = n.authorUserId,
            note = n.note,
            internal = n.internal,
            createdAt = n.createdAt
        )
    }
}

data class PublicComplaintRequest(
    @field:jakarta.validation.constraints.NotBlank
    val category: String = "OTHER",
    @field:jakarta.validation.constraints.NotBlank
    @field:jakarta.validation.constraints.Size(min = 3, max = 200)
    val subject: String = "",
    @field:jakarta.validation.constraints.NotBlank
    @field:jakarta.validation.constraints.Size(min = 10, max = 4000)
    val message: String = "",
    val contactName: String? = null,
    val contactEmail: String? = null
)

data class PublicComplaintResponse(
    val referenceNo: String,
    val status: String,
    val createdAt: String
)

data class PublicStatusView(
    val referenceNo: String,
    val status: String,
    val resolutionNote: String?,
    val createdAt: String,
    val updatedAt: String?
)

data class StatusRequest(
    val status: String,
    val resolutionNote: String? = null,
    val priority: String? = null
)

data class AssignRequest(val assigneeUserId: Long)

data class NoteRequest(
    @field:jakarta.validation.constraints.NotBlank
    @field:jakarta.validation.constraints.Size(min = 1, max = 4000)
    val note: String = "",
    val internal: Boolean = true
)

@Service
class ComplaintService(
    private val complaintRepository: ComplaintRepository,
    private val complaintNoteRepository: ComplaintNoteRepository,
    private val userRepository: UserRepository,
    private val auditLogService: AuditLogService,
    private val notificationService: NotificationService
) {

    private val random = SecureRandom()
    private val validCategories = setOf("BILLING", "BOOKING", "LISTING", "DATA", "SUPPORT", "OTHER")
    private val slaHours = mapOf("P1" to 4L, "P2" to 24L, "P3" to 72L)

    @Transactional
    fun createPublic(req: PublicComplaintRequest): PublicComplaintResponse {
        val category = req.category.uppercase().takeIf { it in validCategories } ?: "OTHER"
        val priority = if (category == "BILLING" || category == "BOOKING") "P2" else "P3"
        val now = Instant.now()
        val complaint = complaintRepository.save(
            Complaint(
                referenceNo = generateReference(),
                category = category,
                subject = req.subject.trim(),
                message = req.message.trim(),
                contactName = req.contactName?.take(200),
                contactEmail = req.contactEmail?.take(200),
                priority = priority,
                status = "OPEN",
                slaDueAt = now.plusSeconds(slaHours[priority]!! * 3600).toString(),
                createdAt = now.toString()
            )
        )
        return PublicComplaintResponse(complaint.referenceNo, complaint.status, complaint.createdAt)
    }

    fun statusByReference(referenceNo: String): PublicStatusView {
        val complaint = complaintRepository.findByReferenceNo(referenceNo.uppercase())
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "Complaint not found")
        return PublicStatusView(
            referenceNo = complaint.referenceNo,
            status = complaint.status,
            resolutionNote = complaint.resolutionNote,
            createdAt = complaint.createdAt,
            updatedAt = complaint.updatedAt
        )
    }

    fun list(user: User, status: String?, priority: String?): List<ComplaintView> {
        val noteCounts = complaintNoteRepository.findAll().groupingBy { it.complaintId }.eachCount()
        return complaintRepository.findAllByOrderByIdDesc()
            .filter { status == null || it.status.equals(status, ignoreCase = true) }
            .filter { priority == null || it.priority.equals(priority, ignoreCase = true) }
            .map { ComplaintView.from(it, noteCounts[it.id] ?: 0) }
    }

    fun detail(user: User, id: Long): ComplaintView {
        val complaint = find(id)
        return ComplaintView.from(complaint, complaintNoteRepository.findAllByComplaintIdOrderByIdAsc(id).size)
    }

    fun notes(user: User, id: Long): List<ComplaintNoteView> {
        find(id)
        return complaintNoteRepository.findAllByComplaintIdOrderByIdAsc(id).map { ComplaintNoteView.from(it) }
    }

    @Transactional
    fun updateStatus(user: User, id: Long, req: StatusRequest): ComplaintView {
        val complaint = find(id)
        val valid = mapOf(
            "OPEN" to listOf("IN_PROGRESS"),
            "IN_PROGRESS" to listOf("OPEN", "RESOLVED"),
            "RESOLVED" to listOf("CLOSED")
        )
        val from = complaint.status
        val to = req.status.uppercase()
        if (to !in (valid[from] ?: emptyList())) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid transition $from -> $to")
        }
        if (to == "RESOLVED" && req.resolutionNote.isNullOrBlank()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "resolutionNote is required to resolve")
        }
        req.priority?.let { p ->
            val priority = p.uppercase().takeIf { it in slaHours } ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Unknown priority $p")
            if (priority != complaint.priority) complaint.slaDueAt = Instant.now().plusSeconds(slaHours[priority]!! * 3600).toString()
            complaint.priority = priority
        }
        complaint.status = to
        if (to == "RESOLVED") complaint.resolutionNote = req.resolutionNote?.trim()
        if (to == "CLOSED" || to == "RESOLVED") complaint.updatedAt = Instant.now().toString()
        complaintRepository.save(complaint)
        auditLogService.record(
            actorId = user.id!!, action = "COMPLAINT_STATUS", targetUserId = null,
            detail = "complaint=${complaint.referenceNo}, $from -> $to"
        )
        complaint.assigneeUserId?.let { assignee ->
            notificationService.notify(
                assignee, "COMPLAINT_STATUS",
                "Complaint $to",
                "${complaint.referenceNo}: ${complaint.subject} is now $to",
                "complaintId=${complaint.id}"
            )
        }
        return detail(user, id)
    }

    @Transactional
    fun assign(user: User, id: Long, assigneeUserId: Long): ComplaintView {
        val complaint = find(id)
        val assignee = userRepository.findById(assigneeUserId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Assignee not found") }
        complaint.assigneeUserId = assignee.id
        complaint.updatedAt = Instant.now().toString()
        complaintRepository.save(complaint)
        auditLogService.record(
            actorId = user.id!!, action = "COMPLAINT_ASSIGNED", targetUserId = assignee.id,
            detail = "complaint=${complaint.referenceNo}"
        )
        notificationService.notify(
            assignee.id!!, "COMPLAINT_ASSIGNED",
            "Complaint assigned to you",
            "${complaint.referenceNo}: ${complaint.subject}",
            "complaintId=${complaint.id}"
        )
        return detail(user, id)
    }

    @Transactional
    fun addNote(user: User, id: Long, req: NoteRequest): ComplaintNoteView {
        val complaint = find(id)
        val note = complaintNoteRepository.save(
            ComplaintNote(
                complaintId = complaint.id!!,
                authorUserId = user.id!!,
                note = req.note.trim(),
                internal = req.internal,
                createdAt = Instant.now().toString()
            )
        )
        auditLogService.record(
            actorId = user.id!!, action = "COMPLAINT_NOTE", targetUserId = null,
            detail = "complaint=${complaint.referenceNo}, internal=${req.internal}"
        )
        return ComplaintNoteView.from(note)
    }

    private fun find(id: Long): Complaint =
        complaintRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Complaint not found") }

    @Scheduled(fixedRate = 900000)
    @Transactional
    fun sweepOverdueComplaints() {
        val now = Instant.now()
        val overdue = complaintRepository.findAllByOrderByIdDesc()
            .filter { it.status != "RESOLVED" && it.status != "CLOSED" }
            .filter {
                runCatching { Instant.parse(it.slaDueAt).isBefore(now) }.getOrDefault(false)
            }
        for (c in overdue.take(50)) {
            if (c.assigneeUserId != null) {
                notificationService.notify(
                    c.assigneeUserId!!, "COMPLAINT_OVERDUE",
                    "Complaint overdue: ${c.referenceNo}",
                    c.subject.take(200)
                )
            }
        }
    }

    private fun generateReference(): String {
        val alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
        return "KICC-CMP-" + (1..6).map { alphabet[random.nextInt(alphabet.length)] }.joinToString("")
    }
}

@RestController
@RequestMapping("/api/public/complaints")
class PublicComplaintController(private val complaintService: ComplaintService) {

    @PostMapping
    fun create(@jakarta.validation.Valid @RequestBody req: PublicComplaintRequest): PublicComplaintResponse =
        complaintService.createPublic(req)

    @GetMapping("/{referenceNo}")
    fun status(@PathVariable referenceNo: String): PublicStatusView =
        complaintService.statusByReference(referenceNo)
}

@RestController
@RequestMapping("/api/complaints")
class ComplaintController(private val complaintService: ComplaintService) {

    @GetMapping
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun list(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @RequestParam(required = false) status: String?,
        @RequestParam(required = false) priority: String?
    ): List<ComplaintView> = complaintService.list(user, status, priority)

    @GetMapping("/{id}")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun detail(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long
    ): ComplaintView = complaintService.detail(user, id)

    @GetMapping("/{id}/notes")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun notes(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long
    ): List<ComplaintNoteView> = complaintService.notes(user, id)

    @PostMapping("/{id}/status")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun status(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody req: StatusRequest
    ): ComplaintView = complaintService.updateStatus(user, id, req)

    @PostMapping("/{id}/assign")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun assign(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody req: AssignRequest
    ): ComplaintView = complaintService.assign(user, id, req.assigneeUserId)

    @PostMapping("/{id}/notes")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun addNote(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @jakarta.validation.Valid @RequestBody req: NoteRequest
    ): ComplaintNoteView = complaintService.addNote(user, id, req)
}
