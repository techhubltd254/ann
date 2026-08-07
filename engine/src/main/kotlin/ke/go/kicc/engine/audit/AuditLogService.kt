package ke.go.kicc.engine.audit

import ke.go.kicc.engine.data.CountyRepository
import ke.go.kicc.engine.rbac.ScopeResolver
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Propagation
import org.springframework.transaction.annotation.Transactional
import java.security.MessageDigest
import java.time.Instant

@Service
class AuditLogService(
    private val auditLogRepository: AuditLogRepository,
    private val scopeResolver: ScopeResolver,
    private val countyRepository: CountyRepository,
    private val userRepository: UserRepository
) {

    @Transactional
    fun record(actorId: Long, action: String, targetUserId: Long?, detail: String? = null) {
        val previous = auditLogRepository.findTopByOrderByIdDesc()
        val row = auditLogRepository.save(
            AuditLog(
                actorUserId = actorId,
                action = action,
                targetUserId = targetUserId,
                detail = detail,
                createdAt = Instant.now(),
                prevHash = previous?.hash
            )
        )
        row.hash = hashOf(row)
        auditLogRepository.save(row)
    }

    /**
     * Failed logins happen inside a rolling-back transaction; the audit entry
     * must survive it, so it runs in its own transaction.
     */
    @Transactional(propagation = Propagation.REQUIRES_NEW)
    fun recordLoginFailure(email: String) {
        record(actorId = 0L, action = "LOGIN_FAILED", targetUserId = null, detail = "email=$email")
    }

    fun list(user: User, targetUserId: Long?, limit: Int): List<AuditLogView> {
        val logs = if (targetUserId != null) {
            auditLogRepository.findByTargetUserIdOrderByCreatedAtDesc(targetUserId)
        } else {
            auditLogRepository.findAllByOrderByCreatedAtDesc()
        }
        val counties = scopeResolver.accessFor(user).counties
        val filtered = if (counties.isEmpty()) {
            logs
        } else {
            val allowedSlugs = countyRepository.findAllById(counties).map { it.slug }.toSet()
            val countyByUserId = userRepository.findAll().associate { it.id!! to it.countySlug }
            logs.filter { log ->
                val actorSlug = countyByUserId[log.actorUserId]
                val targetSlug = log.targetUserId?.let { countyByUserId[it] }
                (actorSlug != null && actorSlug in allowedSlugs) ||
                    (targetSlug != null && targetSlug in allowedSlugs)
            }
        }
        return filtered.take(limit).map { AuditLogView.from(it) }
    }

    /**
     * Walks the hash chain from the first hashed row (the chain root; legacy
     * pre-chain rows have a null hash and are reported, not failed).
     * Returns the recomputed hash of the last row so an auditor can chain
     * forward from an exported snapshot.
     */
    fun verify(): AuditChainStatus {
        val rows = auditLogRepository.findAllByOrderByIdAsc()
        val legacy = rows.count { it.hash == null }
        var brokenAt: Long? = null
        var checked = 0
        var tailHash: String? = null
        for (row in rows) {
            if (row.hash == null) continue
            if (row.hash != hashOf(row)) {
                brokenAt = row.id
                break
            }
            checked++
            tailHash = row.hash
        }
        return AuditChainStatus(
            valid = brokenAt == null,
            checked = checked,
            brokenAtId = brokenAt,
            legacyRows = legacy,
            tailHash = tailHash
        )
    }

    private fun hashOf(row: AuditLog): String {
        val digest = MessageDigest.getInstance("SHA-256")
        val canonical = "${row.prevHash ?: ""}|${row.id}|${row.actorUserId}|${row.action}|${row.targetUserId}|${row.detail}|" +
            row.createdAt.truncatedTo(java.time.temporal.ChronoUnit.MILLIS)
        return digest.digest(canonical.toByteArray()).joinToString("") { "%02x".format(it) }
    }
}

data class AuditChainStatus(
    val valid: Boolean,
    val checked: Int,
    val brokenAtId: Long?,
    val legacyRows: Int,
    val tailHash: String?
)

data class AuditLogView(
    val id: Long,
    val actorUserId: Long,
    val action: String,
    val targetUserId: Long?,
    val detail: String?,
    val createdAt: Instant
) {
    companion object {
        fun from(log: AuditLog) = AuditLogView(
            id = log.id!!,
            actorUserId = log.actorUserId,
            action = log.action,
            targetUserId = log.targetUserId,
            detail = log.detail,
            createdAt = log.createdAt
        )
    }
}
