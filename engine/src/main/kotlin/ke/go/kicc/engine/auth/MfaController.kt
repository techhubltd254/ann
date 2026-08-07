package ke.go.kicc.engine.auth

import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.rbac.Privilege
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.http.HttpStatus
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException

/**
 * MFA management endpoints. Enrollment requires an authenticated session;
 * verification of a pending login uses the short-lived mfaToken from /auth/login.
 */
@RestController
@RequestMapping("/api/auth/mfa")
class MfaController(
    private val users: UserRepository,
    private val totp: TotpService,
    private val audit: AuditLogService,
    private val authService: AuthService,
) {
    /** Roles/privileges for which MFA is mandatory (blueprint §Layer 1). */
    private fun mfaMandatory(user: User): Boolean =
        user.tier == Role.KICC

    @PostMapping("/enroll")
    fun enroll(@AuthenticationPrincipal user: User): Map<String, String> {
        val secret = totp.generateSecret()
        user.totpSecret = secret
        // not enabled until first code verifies
        users.save(user)
        audit.record(user.id ?: 0, "MFA_ENROLL", null, "email=${user.email}")
        return mapOf(
            "secret" to secret,
            "otpauth" to totp.otpauthUri(user.email, secret),
            "mandatory" to mfaMandatory(user).toString(),
        )
    }

    @PostMapping("/enable")
    fun enable(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, String>): Map<String, Any> {
        val secret = user.totpSecret
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Enroll first")
        val code = body["code"] ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "code required")
        if (!totp.verify(secret, code)) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid code — check your authenticator and retry")
        }
        user.mfaEnabled = true
        users.save(user)
        audit.record(user.id ?: 0, "MFA_ENABLED", null, "email=${user.email}")
        return mapOf("mfaEnabled" to true)
    }

    @PostMapping("/disable")
    fun disable(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, String>): Map<String, Any> {
        if (mfaMandatory(user)) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "MFA is mandatory for the KICC tier")
        }
        val secret = user.totpSecret
        val code = body["code"] ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "code required")
        if (secret == null || !totp.verify(secret, code)) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid code")
        }
        user.mfaEnabled = false
        user.totpSecret = null
        users.save(user)
        audit.record(user.id ?: 0, "MFA_DISABLED", null, "email=${user.email}")
        return mapOf("mfaEnabled" to false)
    }

    /** Step 2 of login when MFA is enabled: exchange mfaToken + TOTP code for full tokens. */
    @PostMapping("/verify")
    fun verify(@RequestBody body: Map<String, String>): TokenResponse {
        val token = body["mfaToken"] ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "mfaToken required")
        val code = body["code"] ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "code required")
        return authService.completeMfaLogin(token, code)
    }
}
