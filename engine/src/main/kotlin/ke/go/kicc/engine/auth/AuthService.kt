package ke.go.kicc.engine.auth

import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.rbac.PermissionEvaluator
import ke.go.kicc.engine.tenant.Tenant
import ke.go.kicc.engine.tenant.TenantContext
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.beans.factory.annotation.Value
import org.springframework.http.HttpStatus
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.server.ResponseStatusException
import org.springframework.scheduling.annotation.Scheduled
import java.security.MessageDigest
import java.security.SecureRandom
import java.time.Duration
import java.time.Instant
import java.util.Base64

@Service
class AuthService(
    private val userRepository: UserRepository,
    private val passwordEncoder: PasswordEncoder,
    private val jwtService: JwtService,
    private val refreshTokenRepository: RefreshTokenRepository,
    private val permissionEvaluator: PermissionEvaluator,
    private val auditLogService: AuditLogService,
    private val totpService: TotpService,
    @Value("\${kicc.jwt.secret}") private val jwtSecret: String,
    @Value("\${kicc.jwt.access-ttl}") accessTtlSeconds: Long,
    @Value("\${kicc.jwt.refresh-ttl}") refreshTtlSeconds: Long
) {
    private val accessTtl: Duration = Duration.ofSeconds(accessTtlSeconds)
    private val refreshTtl: Duration = Duration.ofSeconds(refreshTtlSeconds)
    private val random = SecureRandom()

    @Transactional
    fun login(email: String, password: String): TokenResponse {
        val user = userRepository.findByEmail(email.lowercase())
            ?: run {
                auditLogService.recordLoginFailure(email.lowercase())
                throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid credentials")
            }
        if (!passwordEncoder.matches(password, user.passwordHash)) {
            auditLogService.recordLoginFailure(email.lowercase())
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid credentials")
        }
        if (!user.active) {
            auditLogService.record(actorId = user.id!!, action = "LOGIN_BLOCKED", targetUserId = null, detail = "email=${email.lowercase()} (account disabled)")
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Account disabled")
        }
        // MFA branch: issue a short-lived challenge token, NOT a session.
        if (user.mfaEnabled) {
            val challenge = issueMfaChallenge(user)
            auditLogService.record(actorId = user.id!!, action = "LOGIN_MFA_CHALLENGE", targetUserId = null, detail = "email=${email.lowercase()}")
            return TokenResponse(
                accessToken = "", refreshToken = "", expiresIn = 0,
                user = AuthUserView(user.id!!, user.email, user.fullName, user.tier, user.countySlug, user.boothId),
                mfaRequired = true, mfaToken = challenge,
            )
        }
        auditLogService.record(actorId = user.id!!, action = "LOGIN_SUCCESS", targetUserId = null, detail = "email=${email.lowercase()}")
        return issueTokens(user)
    }

    /** Step 2 of an MFA login: validate the challenge token + TOTP code, then issue the session. */
    @Transactional
    fun completeMfaLogin(mfaToken: String, code: String): TokenResponse {
        val claims = runCatching { jwtService.parse(mfaToken) }.getOrElse {
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid or expired MFA challenge")
        }
        if (claims.get("purpose", String::class.java) != "mfa") {
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid challenge token")
        }
        val user = userRepository.findById(claims.subject.toLong()).orElseThrow {
            ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid challenge token")
        }
        val secret = user.totpSecret
            ?: throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "MFA not configured")
        if (!totpService.verify(secret, code)) {
            auditLogService.record(actorId = user.id!!, action = "MFA_FAILED", targetUserId = null, detail = "email=${user.email}")
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid code")
        }
        auditLogService.record(actorId = user.id!!, action = "LOGIN_SUCCESS_MFA", targetUserId = null, detail = "email=${user.email}")
        return issueTokens(user)
    }

    private fun issueMfaChallenge(user: User): String =
        io.jsonwebtoken.Jwts.builder()
            .subject(user.id.toString())
            .claim("purpose", "mfa")
            .issuedAt(java.util.Date())
            .expiration(java.util.Date(System.currentTimeMillis() + 5 * 60 * 1000))
            .signWith(io.jsonwebtoken.security.Keys.hmacShaKeyFor(jwtSecret.toByteArray()))
            .compact()

    @Transactional
    fun refresh(refreshToken: String): TokenResponse {
        val stored = refreshTokenRepository.findByTokenHash(sha256(refreshToken))
            ?: throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid refresh token")
        if (stored.revokedAt != null || stored.expiresAt.isBefore(Instant.now())) {
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid refresh token")
        }
        val user = stored.user
            ?: throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid refresh token")
        if (!user.active) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Account disabled")
        }
        stored.revokedAt = Instant.now()
        refreshTokenRepository.save(stored)
        auditLogService.record(actorId = user.id!!, action = "TOKEN_REFRESH", targetUserId = null)
        return issueTokens(user)
    }

    @Transactional
    fun logout(refreshToken: String) {
        val stored = refreshTokenRepository.findByTokenHash(sha256(refreshToken))
        stored?.let {
            it.revokedAt = Instant.now()
            refreshTokenRepository.save(it)
            auditLogService.record(actorId = it.user?.id ?: 0L, action = "LOGOUT", targetUserId = null)
        }
    }

    fun me(user: User): MeResponse {
        TenantContext.set(Tenant(user.countySlug, user.sectorId, user.boothId))
        return MeResponse(
            email = user.email,
            fullName = user.fullName,
            tier = user.tier,
            countySlug = user.countySlug,
            sectorId = user.sectorId,
            boothId = user.boothId,
            privileges = permissionEvaluator.effectivePrivileges(user).map { it.name }.sorted(),
            tenant = TenantContext.get()
        )
    }

    private fun issueTokens(user: User): TokenResponse {
        val raw = ByteArray(48).also { random.nextBytes(it) }
        val token = Base64.getUrlEncoder().withoutPadding().encodeToString(raw)
        refreshTokenRepository.save(
            RefreshToken(
                user = user,
                tokenHash = sha256(token),
                expiresAt = Instant.now().plus(refreshTtl)
            )
        )
        return TokenResponse(
            accessToken = jwtService.issue(user, accessTtl),
            refreshToken = token,
            expiresIn = accessTtl.toSeconds(),
            user = AuthUserView(user.id!!, user.email, user.fullName, user.tier, user.countySlug, user.boothId)
        )
    }

    private fun sha256(value: String): String =
        MessageDigest.getInstance("SHA-256")
            .digest(value.toByteArray())
            .joinToString("") { "%02x".format(it) }

    @Scheduled(cron = "0 30 3 * * *")
    @Transactional
    fun purgeExpiredTokens() {
        val cutoff = Instant.now().minusSeconds(86400)
        val expired = refreshTokenRepository.findAll()
            .filter { it.revokedAt != null || (it.expiresAt?.isBefore(cutoff) == true) }
        if (expired.isNotEmpty()) refreshTokenRepository.deleteAll(expired)
    }
}
