package ke.go.kicc.engine.auth

import io.jsonwebtoken.Claims
import io.jsonwebtoken.Jwts
import io.jsonwebtoken.security.Keys
import ke.go.kicc.engine.user.User
import org.springframework.beans.factory.annotation.Value
import org.springframework.stereotype.Service
import java.time.Duration
import java.util.Date
import javax.crypto.SecretKey

@Service
class JwtService(
    @Value("\${kicc.jwt.secret}") secret: String
) {
    private val key: SecretKey = Keys.hmacShaKeyFor(secret.toByteArray())

    fun issue(user: User, ttl: Duration): String =
        Jwts.builder()
            .subject(user.id.toString())
            .claim("tier", user.tier.name)
            .claim("county", user.countySlug ?: "")
            .issuedAt(Date())
            .expiration(Date(System.currentTimeMillis() + ttl.toMillis()))
            .signWith(key)
            .compact()

    fun parse(token: String): Claims =
        Jwts.parser()
            .verifyWith(key)
            .build()
            .parseSignedClaims(token)
            .payload
}
