package ke.go.kicc.engine.user

import jakarta.validation.constraints.Email
import jakarta.validation.constraints.NotBlank
import jakarta.validation.constraints.Size
import ke.go.kicc.engine.rbac.Role

data class CreateUserRequest(
    @field:Email
    @field:NotBlank
    val email: String,

    @field:NotBlank
    val fullName: String,

    val tier: Role,

    @field:Size(min = 8)
    val password: String,

    val countySlug: String? = null,

    val sectorId: Long? = null,

    val boothId: Long? = null
)

data class UserView(
    val id: Long,
    val email: String,
    val fullName: String,
    val tier: Role,
    val countySlug: String?,
    val sectorId: Long?,
    val boothId: Long?,
    val active: Boolean
) {
    companion object {
        fun from(user: User) = UserView(
            id = user.id!!,
            email = user.email,
            fullName = user.fullName,
            tier = user.tier,
            countySlug = user.countySlug,
            sectorId = user.sectorId,
            boothId = user.boothId,
            active = user.active
        )
    }
}

data class EditUserRequest(
    val fullName: String? = null,
    val tier: Role? = null,
    val countySlug: String? = null,
    val sectorId: Long? = null,
    val boothId: Long? = null,
    val active: Boolean? = null
)

data class ResetPasswordResponse(
    val userId: Long,
    val newPassword: String
)
