package ke.go.kicc.engine.user

import jakarta.validation.Valid
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.*

@RestController
@RequestMapping("/api/admin/users")
class UserAdminController(private val userAdminService: UserAdminService) {

    @GetMapping
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun list(
        @AuthenticationPrincipal actor: User,
        @RequestParam(defaultValue = "0") offset: Int,
        @RequestParam(defaultValue = "100") limit: Int
    ): List<UserView> =
        userAdminService.list(actor).drop(offset).take(limit)

    @PostMapping
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun create(@AuthenticationPrincipal actor: User, @Valid @RequestBody req: CreateUserRequest): UserView =
        userAdminService.create(actor, req)

    @DeleteMapping("/{id}")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun deactivate(@AuthenticationPrincipal actor: User, @PathVariable id: Long): UserView =
        userAdminService.deactivate(actor, id)

    @PutMapping("/{id}")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun edit(@AuthenticationPrincipal actor: User, @PathVariable id: Long, @RequestBody req: EditUserRequest): UserView =
        userAdminService.edit(actor, id, req)

    @PostMapping("/{id}/reset-password")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun resetPassword(@AuthenticationPrincipal actor: User, @PathVariable id: Long): ResetPasswordResponse =
        userAdminService.resetPassword(actor, id)

    @PostMapping("/{id}/reactivate")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun reactivate(@AuthenticationPrincipal actor: User, @PathVariable id: Long): UserView =
        userAdminService.reactivate(actor, id)
}
