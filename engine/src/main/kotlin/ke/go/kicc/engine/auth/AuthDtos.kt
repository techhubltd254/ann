package ke.go.kicc.engine.auth

import jakarta.validation.constraints.Email
import jakarta.validation.constraints.NotBlank
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.tenant.Tenant

data class LoginRequest(
    @field:Email
    @field:NotBlank
    val email: String,

    @field:NotBlank
    val password: String
)

data class RefreshRequest(
    val refreshToken: String = ""
)

data class TokenResponse(
    val accessToken: String,
    val refreshToken: String,
    val expiresIn: Long,
    val user: AuthUserView,
    // MFA challenge: when the account has MFA enabled, login returns these instead
    // of live tokens — exchange via POST /api/auth/mfa/verify within 5 minutes.
    val mfaRequired: Boolean = false,
    val mfaToken: String? = null,
)

data class AuthUserView(
    val id: Long,
    val email: String,
    val fullName: String,
    val tier: Role,
    val countySlug: String?,
    val boothId: Long?
)

data class MeResponse(
    val email: String,
    val fullName: String,
    val tier: Role,
    val countySlug: String?,
    val sectorId: Long?,
    val boothId: Long?,
    val privileges: List<String>,
    val tenant: Tenant
)
