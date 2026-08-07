package ke.go.kicc.engine.onboarding

import jakarta.validation.Valid

import ke.go.kicc.engine.user.User
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.*

@RestController
@RequestMapping("/api/onboarding")
class OnboardingController(
    private val onboardingService: OnboardingService
) {

    @PostMapping("/apply")
    fun apply(@Valid @RequestBody req: ApplyRequest): ApplyResponse = onboardingService.apply(req)

    @GetMapping("/applicants")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun list(): List<ApplicantView> = onboardingService.list()

    @PostMapping("/applicants/{id}/approve")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun approve(@AuthenticationPrincipal user: User, @PathVariable id: Long): ReviewResponse = onboardingService.approve(user, id)

    @PostMapping("/applicants/{id}/reject")
    @PreAuthorize("hasAuthority('USERS_MANAGE')")
    fun reject(@AuthenticationPrincipal user: User, @PathVariable id: Long): ApplicantView = onboardingService.reject(user, id)
}
