package ke.go.kicc.engine.rbac

import ke.go.kicc.engine.user.User
import org.springframework.stereotype.Service
import java.time.Instant

@Service
class PermissionEvaluator(private val delegationRepository: DelegationRepository) {

    fun effectivePrivileges(user: User): Set<Privilege> {
        val now = Instant.now()
        val result = RoleDefaults.privilegesFor(user.tier).toMutableSet()
        if (user.id != null) {
            delegationRepository.findBySubjectIdAndRevokedAtIsNull(user.id!!)
                .filter { d ->
                    val expires = d.expiresAt
                    expires == null || expires.isAfter(now)
                }
                .forEach { d ->
                    result.addAll(RoleDefaults.privilegesFor(d.role))
                    result.addAll(d.extraPrivileges)
                }
        }
        return result
    }
}
