package ke.go.kicc.engine.config

import ke.go.kicc.engine.auth.JwtAuthFilter
import org.springframework.beans.factory.annotation.Value
import org.springframework.context.annotation.Bean
import org.springframework.context.annotation.Configuration
import org.springframework.http.MediaType
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity
import org.springframework.security.config.annotation.web.builders.HttpSecurity
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity
import org.springframework.security.config.http.SessionCreationPolicy
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.security.web.SecurityFilterChain
import org.springframework.security.web.authentication.UsernamePasswordAuthenticationFilter
import org.springframework.web.cors.CorsConfiguration
import org.springframework.web.cors.CorsConfigurationSource
import org.springframework.web.cors.UrlBasedCorsConfigurationSource

@Configuration
@EnableWebSecurity
@EnableMethodSecurity
class SecurityConfig(
    private val rateLimitFilter: RateLimitFilter,
    @Value("\${spring.h2.console.enabled:false}") private val h2ConsoleEnabled: Boolean,
    @Value("\${kicc.cors-origins:http://localhost:5173}") private val corsOrigins: String
) {

    @Bean
    fun passwordEncoder(): PasswordEncoder = BCryptPasswordEncoder()

    @Bean
    fun corsConfigurationSource(): CorsConfigurationSource {
        val cfg = CorsConfiguration()
        cfg.allowedOrigins = corsOrigins.split(",").filter { it.isNotBlank() }
        cfg.allowedMethods = listOf("GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS")
        cfg.allowedHeaders = listOf("*")
        cfg.allowCredentials = true
        val src = UrlBasedCorsConfigurationSource()
        src.registerCorsConfiguration("/**", cfg)
        return src
    }

    @Bean
    fun securityFilterChain(http: HttpSecurity, jwtAuthFilter: JwtAuthFilter): SecurityFilterChain {
        val permitAll = mutableListOf(
            "/",
            "/index.html",
            "/assets/**",
            "/favicon.ico",
            "/login",
            "/admin/**",
            "/complaint",
            "/track",
            "/track/**",
            "/apply",
            "/result",
            "/api/auth/login",
            "/api/auth/refresh",
            "/api/health",
            "/api/metrics",
            "/api/ops/heartbeat",
            "/api/ops/backups/ingest",
            "/api/media/**",
            "/api/onboarding/apply",
            "/api/public/complaints/**",
            "/api/data/public/**",
            "/api/marketplace/products",
            "/actuator/health",
            "/actuator/info",
            "/v3/api-docs/**",
            "/swagger-ui.html",
            "/swagger-ui/**"
        )
        if (h2ConsoleEnabled) permitAll.add("/h2-console/**")
        http
            .csrf { it.disable() }
            .cors { }
            .sessionManagement { it.sessionCreationPolicy(SessionCreationPolicy.STATELESS) }
            .headers { it.frameOptions { f -> f.sameOrigin() } }
            .authorizeHttpRequests {
                it.requestMatchers(*permitAll.toTypedArray()).permitAll()
                it.anyRequest().authenticated()
            }
            .exceptionHandling {
                it.authenticationEntryPoint { _, res, _ ->
                    res.status = 401
                    res.contentType = MediaType.APPLICATION_JSON_VALUE
                    res.writer.write("""{"error":"Unauthorized"}""")
                }
                it.accessDeniedHandler { _, res, _ ->
                    res.status = 403
                    res.contentType = MediaType.APPLICATION_JSON_VALUE
                    res.writer.write("""{"error":"Forbidden"}""")
                }
            }
            .addFilterBefore(rateLimitFilter, UsernamePasswordAuthenticationFilter::class.java)
            .addFilterBefore(jwtAuthFilter, UsernamePasswordAuthenticationFilter::class.java)
        return http.build()
    }
}
