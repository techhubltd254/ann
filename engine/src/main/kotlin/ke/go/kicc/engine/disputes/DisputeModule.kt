package ke.go.kicc.engine.disputes

import jakarta.persistence.*
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Service
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import java.time.LocalDateTime
import java.time.format.DateTimeFormatter

/**
 * Two-tier dispute resolution (blueprint §Layer 4):
 *  - Tier 1 (automated): amounts < KES 5,000 — rule-based auto-resolution at raise time.
 *  - Tier 2 (human): amounts ≥ KES 5,000 — queued here for a payments admin.
 * Resolution outcomes: RELEASE_TO_SELLER, REFUND_BUYER, PARTIAL_REFUND.
 */
@Entity
@Table(name = "dispute_cases")
class DisputeCase(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(name = "escrow_transaction_id", nullable = false)
    var escrowTransactionId: Long = 0,

    @Column(name = "raised_by", nullable = false)
    var raisedBy: Long = 0,

    var reason: String? = null,

    @Column(columnDefinition = "text")
    var description: String? = null,

    @Column(nullable = false)
    var status: String = "open",           // open | auto_resolved | resolved | rejected

    var resolution: String? = null,        // RELEASE_TO_SELLER | REFUND_BUYER | PARTIAL_REFUND (+note)

    @Column(name = "resolved_at")
    var resolvedAt: String? = null,

    @Column(name = "resolved_by")
    var resolvedBy: Long? = null,

    @Column(name = "created_at")
    var createdAt: String? = null,

    @Column(name = "updated_at")
    var updatedAt: String? = null,
)

interface DisputeRepository : JpaRepository<DisputeCase, Long> {
    fun findAllByStatusOrderByIdDesc(status: String): List<DisputeCase>
    fun findAllByOrderByIdDesc(): List<DisputeCase>
}

@Service
class DisputeService(
    private val disputes: DisputeRepository,
    private val audit: AuditLogService,
) {
    private val ts = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")

    fun queue(status: String?): List<DisputeCase> =
        if (status.isNullOrBlank() || status == "all") disputes.findAllByOrderByIdDesc()
        else disputes.findAllByStatusOrderByIdDesc(status)

    fun resolve(actor: User, id: Long, outcome: String, note: String?): DisputeCase {
        val d = disputes.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "dispute not found") }
        if (d.status != "open") throw ResponseStatusException(HttpStatus.CONFLICT, "dispute already ${d.status}")
        val allowed = setOf("RELEASE_TO_SELLER", "REFUND_BUYER", "PARTIAL_REFUND")
        if (outcome !in allowed) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "outcome must be one of $allowed")
        d.status = "resolved"
        d.resolution = outcome + (note?.takeIf { it.isNotBlank() }?.let { " — $it" } ?: "")
        d.resolvedBy = actor.id
        d.resolvedAt = LocalDateTime.now().format(ts)
        d.updatedAt = d.resolvedAt
        val saved = disputes.save(d)
        audit.record(actor.id ?: 0, "DISPUTE_RESOLVED", null, "id=$id outcome=$outcome")
        return saved
    }

    fun reject(actor: User, id: Long, note: String?): DisputeCase {
        val d = disputes.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "dispute not found") }
        if (d.status != "open") throw ResponseStatusException(HttpStatus.CONFLICT, "dispute already ${d.status}")
        d.status = "rejected"
        d.resolution = "REJECTED" + (note?.takeIf { it.isNotBlank() }?.let { " — $it" } ?: "")
        d.resolvedBy = actor.id
        d.resolvedAt = LocalDateTime.now().format(ts)
        d.updatedAt = d.resolvedAt
        val saved = disputes.save(d)
        audit.record(actor.id ?: 0, "DISPUTE_REJECTED", null, "id=$id")
        return saved
    }

    fun stats(): Map<String, Long> = mapOf(
        "open" to disputes.findAllByStatusOrderByIdDesc("open").size.toLong(),
        "resolved" to disputes.findAllByStatusOrderByIdDesc("resolved").size.toLong(),
        "auto_resolved" to disputes.findAllByStatusOrderByIdDesc("auto_resolved").size.toLong(),
        "rejected" to disputes.findAllByStatusOrderByIdDesc("rejected").size.toLong(),
    )
}

@RestController
@RequestMapping("/api/disputes")
class DisputeController(private val service: DisputeService) {

    @GetMapping
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun list(@RequestParam(required = false) status: String?) = service.queue(status)

    @GetMapping("/stats")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun stats() = service.stats()

    data class ResolveBody(val outcome: String = "", val note: String? = null)

    @PostMapping("/{id}/resolve")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun resolve(@AuthenticationPrincipal actor: User, @PathVariable id: Long, @RequestBody body: ResolveBody) =
        service.resolve(actor, id, body.outcome, body.note)

    @PostMapping("/{id}/reject")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun reject(@AuthenticationPrincipal actor: User, @PathVariable id: Long, @RequestBody body: Map<String, String?>) =
        service.reject(actor, id, body["note"])
}
