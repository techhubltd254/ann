package ke.go.kicc.engine.auth

import io.jsonwebtoken.JwtException
import jakarta.servlet.FilterChain
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import ke.go.kicc.engine.rbac.PermissionEvaluator
import ke.go.kicc.engine.tenant.Tenant
import ke.go.kicc.engine.tenant.TenantContext
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken
import org.springframework.security.core.authority.SimpleGrantedAuthority
import org.springframework.security.core.context.SecurityContextHolder
import org.springframework.stereotype.Component
import org.springframework.web.filter.OncePerRequestFilter

@Component
class JwtAuthFilter(
    private val jwtService: JwtService,
    private val userRepository: UserRepository,
    private val permissionEvaluator: PermissionEvaluator
) : OncePerRequestFilter() {

    override fun doFilterInternal(
        request: HttpServletRequest,
        response: HttpServletResponse,
        filterChain: FilterChain
    ) {
        try {
            val header = request.getHeader("Authorization")
            val cookie = request.cookies?.firstOrNull { it.name == "kicc_access" }?.value
            when {
                header != null && header.startsWith("Bearer ") -> authenticate(header.removePrefix("Bearer "))
                cookie != null -> authenticate(cookie)
                request.requestURI.endsWith("/api/notifications/stream") ->
                    request.getParameter("token")?.let { authenticate(it) }
            }
            filterChain.doFilter(request, response)
        } finally {
            TenantContext.clear()
        }
    }

    private fun authenticate(token: String) {
        if (SecurityContextHolder.getContext().authentication != null) return
        try {
            val claims = jwtService.parse(token)
            val userId = claims.subject.toLongOrNull() ?: return
            val user = userRepository.findById(userId).orElse(null)
            if (user == null || !user.active) return
            val privileges = permissionEvaluator.effectivePrivileges(user)
            val authorities = privileges.map { SimpleGrantedAuthority(it.name) }
            val authentication = UsernamePasswordAuthenticationToken(user, null, authorities)
            SecurityContextHolder.getContext().authentication = authentication
            TenantContext.set(Tenant(user.countySlug, user.sectorId, user.boothId))
        } catch (_: JwtException) {
            SecurityContextHolder.clearContext()
        }
    }
}
