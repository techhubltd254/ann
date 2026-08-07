package ke.go.kicc.engine.rbac

import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.data.CountyRepository
import ke.go.kicc.engine.notify.NotificationService
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.http.HttpStatus
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.server.ResponseStatusException
import java.time.Instant

@Service
class DelegationService(
    private val delegationRepository: DelegationRepository,
    private val permissionEvaluator: PermissionEvaluator,
    private val auditLogService: AuditLogService,
    private val userRepository: UserRepository,
    private val notificationService: NotificationService,
    private val countyRepository: CountyRepository,
    private val scopeResolver: ScopeResolver
) {

    @Transactional
    fun grant(actor: User, req: GrantDelegationRequest): DelegationView {
        if (req.role == Role.KICC) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "The KICC role cannot be delegated")
        }
        if (req.subjectUserId == actor.id) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Cannot delegate to yourself")
        }
        val subject = userRepository.findById(req.subjectUserId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Subject user not found") }
        if (!subject.active) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Subject user is inactive")
        }
        if (req.scopeType != ScopeType.ALL && req.scopeValue.isNullOrBlank()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "scopeValue is required for scope type ${req.scopeType}")
        }
        if (req.expiresAt != null && !req.expiresAt.isAfter(Instant.now())) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "expiresAt must be in the future")
        }
        assertScopeWithinActorEnvelope(actor, req.scopeType, req.scopeValue)
        val actorPrivileges = permissionEvaluator.effectivePrivileges(actor)
        if (req.extraPrivileges.any { it !in actorPrivileges }) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Actor does not hold the privileges it tried to delegate")
        }
        val now = Instant.now()
        val expiresAt = req.expiresAt
        val extras = req.extraPrivileges.toSet()
        val duplicate = delegationRepository.findBySubjectIdAndRevokedAtIsNull(req.subjectUserId)
            .any { d ->
                val dExpires = d.expiresAt
                val existingActive = dExpires == null || dExpires.isAfter(now)
                val newActive = expiresAt == null || expiresAt.isAfter(now)
                existingActive && newActive &&
                    d.role == req.role &&
                    d.scopeType == req.scopeType &&
                    d.scopeValue == req.scopeValue &&
                    d.extraPrivileges == extras
            }
        if (duplicate) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "An identical active delegation already exists")
        }
        val delegation = delegationRepository.save(
            Delegation(
                subject = subject,
                role = req.role,
                scopeType = req.scopeType,
                scopeValue = req.scopeValue,
                expiresAt = req.expiresAt,
                grantedBy = actor,
                extraPrivileges = req.extraPrivileges.toMutableSet()
            )
        )
        auditLogService.record(
            actorId = actor.id!!,
            action = "DELEGATION_GRANTED",
            targetUserId = subject.id,
            detail = "role=${req.role}, scope=${req.scopeType}:${req.scopeValue ?: "ALL"}, expires=${req.expiresAt}, extras=${req.extraPrivileges}"
        )
        notificationService.notify(
            subject.id!!,
            "DELEGATION_GRANTED",
            "Access granted",
            "You now hold the ${req.role} role (scope ${req.scopeType}:${req.scopeValue ?: "ALL"}) granted by ${actor.email}",
            "expires=${req.expiresAt}, extras=${req.extraPrivileges}"
        )
        return DelegationView.from(delegation)
    }

    @Transactional
    fun revoke(actor: User, delegationId: Long): DelegationView {
        val delegation = delegationRepository.findById(delegationId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Delegation not found") }
        if (delegation.revokedAt != null) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Delegation already revoked")
        }
        assertScopeWithinActorEnvelope(actor, delegation.scopeType, delegation.scopeValue)
        delegation.revokedAt = Instant.now()
        delegation.revokedBy = actor
        delegationRepository.save(delegation)
        auditLogService.record(
            actorId = actor.id!!,
            action = "DELEGATION_REVOKED",
            targetUserId = delegation.subject?.id,
            detail = "delegationId=$delegationId, role=${delegation.role}"
        )
        delegation.subject?.id?.let { subjectId ->
            notificationService.notify(
                subjectId,
                "DELEGATION_REVOKED",
                "Access revoked",
                "Your ${delegation.role} role (${delegation.scopeType}:${delegation.scopeValue ?: "ALL"}) was revoked by ${actor.email}",
                "delegationId=$delegationId"
            )
        }
        return DelegationView.from(delegation)
    }

    fun list(subjectUserId: Long?, activeOnly: Boolean): List<DelegationView> {
        val all = if (subjectUserId != null) {
            delegationRepository.findBySubjectIdAndRevokedAtIsNull(subjectUserId)
        } else {
            delegationRepository.findAll()
        }
        return all
            .asSequence()
            .filter { d ->
                val dExpires = d.expiresAt
                if (!activeOnly) true
                else d.revokedAt == null && (dExpires == null || dExpires.isAfter(Instant.now()))
            }
            .sortedByDescending { it.grantedAt }
            .map { DelegationView.from(it) }
            .toList()
    }

    /**
     * A grantor may only issue (or revoke) delegations whose scope lies inside
     * their own access envelope; an ALL-scope grant requires an unrestricted
     * envelope. Without this, a scope-restricted DELEGATE holder could re-grant
     * wider scopes than they themselves hold (C1 escalation vector).
     */
    private fun assertScopeWithinActorEnvelope(actor: User, scopeType: ScopeType, scopeValue: String?) {
        val access = scopeResolver.accessFor(actor)
        when (scopeType) {
            ScopeType.ALL -> if (access.isRestricted) {
                throw ResponseStatusException(HttpStatus.FORBIDDEN, "A scope-restricted actor cannot grant ALL scope")
            }
            ScopeType.COUNTY -> {
                val countyId = scopeValue?.let { countyRepository.findBySlug(it)?.id }
                    ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Unknown county slug: $scopeValue")
                if (access.counties.isNotEmpty() && countyId !in access.counties) {
                    throw ResponseStatusException(HttpStatus.FORBIDDEN, "Grant scope outside your access scope")
                }
            }
            ScopeType.SECTOR -> {
                val sectorId = scopeValue?.toLongOrNull()
                    ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "SECTOR scope requires a numeric sector id")
                if (access.sectorIds.isNotEmpty() && sectorId !in access.sectorIds) {
                    throw ResponseStatusException(HttpStatus.FORBIDDEN, "Grant scope outside your access scope")
                }
            }
            ScopeType.BOOTH -> {
                val boothId = scopeValue?.toLongOrNull()
                    ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "BOOTH scope requires a numeric booth id")
                if (access.boothIds.isNotEmpty() && boothId !in access.boothIds) {
                    throw ResponseStatusException(HttpStatus.FORBIDDEN, "Grant scope outside your access scope")
                }
            }
        }
    }
}
