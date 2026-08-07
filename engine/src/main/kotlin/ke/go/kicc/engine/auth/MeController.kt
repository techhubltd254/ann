package ke.go.kicc.engine.auth

import ke.go.kicc.engine.user.User
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RestController

@RestController
@RequestMapping("/api")
class MeController(private val authService: AuthService) {

    @GetMapping("/me")
    fun me(@AuthenticationPrincipal user: User): MeResponse = authService.me(user)
}
