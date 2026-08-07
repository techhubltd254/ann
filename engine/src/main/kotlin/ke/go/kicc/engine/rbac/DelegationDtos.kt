package ke.go.kicc.engine.rbac

import jakarta.validation.constraints.NotNull
import java.time.Instant

data class GrantDelegationRequest(
    @field:NotNull
    val subjectUserId: Long,

    @field:NotNull
    val role: Role,

    @field:NotNull
    val scopeType: ScopeType,

    val scopeValue: String? = null,

    val expiresAt: Instant? = null,

    val extraPrivileges: List<Privilege> = emptyList()
)

data class DelegationView(
    val id: Long,
    val subjectUserId: Long,
    val subjectEmail: String,
    val role: Role,
    val scopeType: ScopeType,
    val scopeValue: String?,
    val expiresAt: Instant?,
    val grantedByUserId: Long,
    val grantedAt: Instant,
    val revokedAt: Instant?,
    val extraPrivileges: List<Privilege>
) {
    companion object {
        fun from(d: Delegation) = DelegationView(
            id = d.id!!,
            subjectUserId = d.subject!!.id!!,
            subjectEmail = d.subject!!.email,
            role = d.role,
            scopeType = d.scopeType,
            scopeValue = d.scopeValue,
            expiresAt = d.expiresAt,
            grantedByUserId = d.grantedBy!!.id!!,
            grantedAt = d.grantedAt,
            revokedAt = d.revokedAt,
            extraPrivileges = d.extraPrivileges.sortedBy { it.name }
        )
    }
}
