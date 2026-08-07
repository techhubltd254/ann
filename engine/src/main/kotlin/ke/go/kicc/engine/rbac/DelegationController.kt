package ke.go.kicc.engine.rbac

import jakarta.validation.Valid
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.*
import ke.go.kicc.engine.user.User

@RestController
@RequestMapping("/api/admin/delegations")
class DelegationController(private val delegationService: DelegationService) {

    @PostMapping
    @PreAuthorize("hasAuthority('DELEGATE')")
    fun grant(
        @AuthenticationPrincipal actor: User,
        @Valid @RequestBody req: GrantDelegationRequest
    ): DelegationView = delegationService.grant(actor, req)

    @GetMapping
    @PreAuthorize("hasAuthority('DELEGATE')")
    fun list(
        @RequestParam(required = false) subjectUserId: Long?,
        @RequestParam(defaultValue = "false") activeOnly: Boolean
    ): List<DelegationView> = delegationService.list(subjectUserId, activeOnly)

    @PostMapping("/{id}/revoke")
    @PreAuthorize("hasAuthority('DELEGATE')")
    fun revoke(@AuthenticationPrincipal actor: User, @PathVariable id: Long): DelegationView =
        delegationService.revoke(actor, id)
}
