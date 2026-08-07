package ke.go.kicc.engine.user

import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.data.CountyRepository
import ke.go.kicc.engine.rbac.PermissionEvaluator
import ke.go.kicc.engine.rbac.Privilege
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.rbac.ScopeResolver
import org.springframework.http.HttpStatus
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.server.ResponseStatusException
import java.security.SecureRandom

@Service
class UserAdminService(
    private val userRepository: UserRepository,
    private val countyRepository: CountyRepository,
    private val auditLogService: AuditLogService,
    private val passwordEncoder: PasswordEncoder,
    private val permissionEvaluator: PermissionEvaluator,
    private val scopeResolver: ScopeResolver
) {
    fun list(actor: User): List<UserView> {
        val all = userRepository.findAll()
        val allowedSlugs = allowedCountySlugs(actor) ?: return all.map { UserView.from(it) }
        return all.filter { it.countySlug != null && it.countySlug in allowedSlugs }.map { UserView.from(it) }
    }

    @Transactional
    fun create(actor: User, req: CreateUserRequest): UserView {
        val email = req.email.lowercase()
        if (userRepository.findByEmail(email) != null) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "A user with this email already exists")
        }
        if (req.tier == ke.go.kicc.engine.rbac.Role.COUNTY && req.countySlug.isNullOrBlank()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "countySlug is required for COUNTY tier")
        }
        validatePasswordComplexity(req.password)
        assertNoTierEscalation(actor, req.tier)
        assertCountyScope(actor, req.countySlug)
        val user = userRepository.save(
            User(
                email = email,
                fullName = req.fullName,
                passwordHash = passwordEncoder.encode(req.password),
                tier = req.tier,
                countySlug = req.countySlug,
                sectorId = req.sectorId,
                boothId = req.boothId,
                active = true
            )
        )
        auditLogService.record(
            actorId = actor.id!!,
            action = "USER_CREATED",
            targetUserId = user.id,
            detail = "tier=${req.tier}, county=${req.countySlug}"
        )
        return UserView.from(user)
    }

    @Transactional
    fun deactivate(actor: User, id: Long): UserView {
        val target = userRepository.findById(id).orElseThrow {
            ResponseStatusException(HttpStatus.NOT_FOUND, "User not found")
        }
        if (target.id == actor.id) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "You cannot deactivate your own account")
        }
        assertNoTierEscalation(actor, target.tier)
        assertCountyScope(actor, target.countySlug)
        target.active = false
        auditLogService.record(
            actorId = actor.id!!,
            action = "USER_DEACTIVATED",
            targetUserId = target.id,
            detail = "tier=${target.tier}"
        )
        return UserView.from(target)
    }

    @Transactional
    fun edit(actor: User, id: Long, req: EditUserRequest): UserView {
        val target = userRepository.findById(id).orElseThrow {
            ResponseStatusException(HttpStatus.NOT_FOUND, "User not found")
        }
        if (req.tier != null && req.tier != target.tier) assertNoTierEscalation(actor, req.tier)
        req.tier?.let { target.tier = it }
        req.fullName?.let { target.fullName = it }
        req.countySlug?.let {
            if (it.isNotBlank()) assertCountyScope(actor, it)
            target.countySlug = it.ifBlank { null }
        }
        req.sectorId?.let { target.sectorId = if (it == 0L) null else it }
        req.boothId?.let { target.boothId = if (it == 0L) null else it }
        req.active?.let { target.active = it }
        userRepository.save(target)
        auditLogService.record(
            actorId = actor.id!!,
            action = "USER_UPDATED",
            targetUserId = target.id,
            detail = "tier=${target.tier}, county=${target.countySlug}"
        )
        return UserView.from(target)
    }

    @Transactional
    fun resetPassword(actor: User, id: Long): ResetPasswordResponse {
        val target = userRepository.findById(id).orElseThrow {
            ResponseStatusException(HttpStatus.NOT_FOUND, "User not found")
        }
        assertNoTierEscalation(actor, target.tier)
        assertCountyScope(actor, target.countySlug)
        val chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#"
        val sb = StringBuilder(16)
        val rng = SecureRandom()
        sb.append("ABC"[rng.nextInt(3)])
        sb.append("abc"[rng.nextInt(3)])
        sb.append("0123456789"[rng.nextInt(10)])
        repeat(13) { sb.append(chars[rng.nextInt(chars.length)]) }
        val newPassword = sb.toList().shuffled(rng).joinToString("")
        target.passwordHash = passwordEncoder.encode(newPassword)
        userRepository.save(target)
        auditLogService.record(
            actorId = actor.id!!,
            action = "USER_PASSWORD_RESET",
            targetUserId = target.id,
            detail = "by admin"
        )
        return ResetPasswordResponse(userId = target.id!!, newPassword = newPassword)
    }

    @Transactional
    fun reactivate(actor: User, id: Long): UserView {
        val target = userRepository.findById(id).orElseThrow {
            ResponseStatusException(HttpStatus.NOT_FOUND, "User not found")
        }
        assertNoTierEscalation(actor, target.tier)
        assertCountyScope(actor, target.countySlug)
        target.active = true
        userRepository.save(target)
        auditLogService.record(
            actorId = actor.id!!,
            action = "USER_REACTIVATED",
            targetUserId = target.id,
            detail = "tier=${target.tier}"
        )
        return UserView.from(target)
    }

    /** Allowed county slugs for the actor, or null when unrestricted. */
    private fun allowedCountySlugs(actor: User): Set<String>? {
        val counties = scopeResolver.accessFor(actor).counties
        if (counties.isEmpty()) return null
        return countyRepository.findAllById(counties).map { it.slug }.toSet()
    }

    private fun assertCountyScope(actor: User, targetCountySlug: String?) {
        val allowed = allowedCountySlugs(actor) ?: return
        if (targetCountySlug == null || targetCountySlug !in allowed) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Cannot manage users outside your county scope")
        }
    }

    private fun validatePasswordComplexity(password: String) {
        if (password.length < 8) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Password must be at least 8 characters")
        if (!password.matches(Regex(".*[A-Z].*"))) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Password must contain an uppercase letter")
        if (!password.matches(Regex(".*[a-z].*"))) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Password must contain a lowercase letter")
        if (!password.matches(Regex(".*\\d.*"))) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Password must contain a digit")
    }

    private fun assertNoTierEscalation(actor: User, target: Role) {
        val rank = mapOf(
            Role.EXHIBITOR to 0,
            Role.COUNTY to 1,
            Role.NATIONAL to 2,
            Role.KICC to 3
        )
        val canDelegate = permissionEvaluator.effectivePrivileges(actor).contains(Privilege.DELEGATE)
        if (target == Role.KICC && !canDelegate) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Only a DELEGATE holder can create KICC-tier users")
        }
        if (rank[target]!! > rank[actor.tier]!! && !canDelegate) {
            throw ResponseStatusException(
                HttpStatus.FORBIDDEN,
                "Cannot create a user with a higher tier than your own without DELEGATE"
            )
        }
    }
}
