package ke.go.kicc.engine.auth

import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import jakarta.validation.Valid
import org.springframework.beans.factory.annotation.Value
import org.springframework.http.ResponseCookie
import org.springframework.http.ResponseEntity
import org.springframework.web.bind.annotation.*

@RestController
@RequestMapping("/api/auth")
class AuthController(
    private val authService: AuthService,
    @Value("\${kicc.cookie-secure:false}") private val cookieSecure: Boolean
) {

    private fun accessCookie(token: String, maxAge: Long) =
        ResponseCookie.from("kicc_access", token)
            .httpOnly(true).secure(cookieSecure).sameSite("Strict").path("/").maxAge(maxAge)
            .build().toString()

    private fun refreshCookie(token: String, maxAge: Long) =
        ResponseCookie.from("kicc_refresh", token)
            .httpOnly(true).secure(cookieSecure).sameSite("Strict").path("/").maxAge(maxAge)
            .build().toString()

    private fun clearCookie(name: String) =
        ResponseCookie.from(name, "")
            .httpOnly(true).secure(cookieSecure).sameSite("Strict").path("/").maxAge(0)
            .build().toString()

    @PostMapping("/login")
    fun login(@Valid @RequestBody req: LoginRequest, response: HttpServletResponse): TokenResponse {
        val tokens = authService.login(req.email, req.password)
        response.addHeader("Set-Cookie", accessCookie(tokens.accessToken, tokens.expiresIn.toLong()))
        response.addHeader("Set-Cookie", refreshCookie(tokens.refreshToken, tokens.expiresIn.toLong() * 672))
        return tokens
    }

    @PostMapping("/refresh")
    fun refresh(
        @RequestBody(required = false) req: RefreshRequest?,
        request: HttpServletRequest,
        response: HttpServletResponse
    ): TokenResponse {
        val token = req?.refreshToken?.takeIf { it.isNotBlank() } ?: cookieValue(request, "kicc_refresh") ?: ""
        val tokens = authService.refresh(token)
        response.addHeader("Set-Cookie", accessCookie(tokens.accessToken, tokens.expiresIn.toLong()))
        response.addHeader("Set-Cookie", refreshCookie(tokens.refreshToken, tokens.expiresIn.toLong() * 672))
        return tokens
    }

    @PostMapping("/logout")
    fun logout(
        @RequestBody(required = false) req: RefreshRequest?,
        request: HttpServletRequest,
        response: HttpServletResponse
    ): ResponseEntity<Unit> {
        (req?.refreshToken ?: cookieValue(request, "kicc_refresh"))?.let { authService.logout(it) }
        response.addHeader("Set-Cookie", clearCookie("kicc_access"))
        response.addHeader("Set-Cookie", clearCookie("kicc_refresh"))
        return ResponseEntity.ok().build()
    }

    private fun cookieValue(request: HttpServletRequest, name: String): String? =
        request.cookies?.firstOrNull { it.name == name }?.value
}
