package ke.go.kicc.engine.config

import jakarta.servlet.FilterChain
import jakarta.servlet.http.HttpServletRequest
import jakarta.servlet.http.HttpServletResponse
import org.springframework.beans.factory.annotation.Value
import org.springframework.stereotype.Component
import org.springframework.web.filter.OncePerRequestFilter
import java.time.Instant
import java.util.concurrent.ConcurrentHashMap

@Component
class RateLimitFilter(
    @Value("\${kicc.rate-limit:10}") private val limit: Int = 10,
    @Value("\${kicc.rate-limit-enabled:true}") private val enabled: Boolean = true
) : OncePerRequestFilter() {

    private val windowSeconds: Long = 60

    private data class Bucket(val windowStart: Long, val count: Int)

    private val buckets = ConcurrentHashMap<String, Bucket>()

    override fun shouldNotFilter(request: HttpServletRequest): Boolean = !request.requestURI.startsWith("/api/auth/")

    override fun doFilterInternal(request: HttpServletRequest, response: HttpServletResponse, chain: FilterChain) {
        if (!enabled) {
            chain.doFilter(request, response)
            return
        }
        val key = "${request.remoteAddr}:${request.requestURI}"
        val now = Instant.now().epochSecond
        val bucket = buckets.compute(key) { _, existing ->
            if (existing == null || now - existing.windowStart >= windowSeconds) Bucket(now, 1)
            else existing.copy(count = existing.count + 1)
        } ?: return
        if (bucket.count > limit) {
            response.status = 429
            response.contentType = "application/json"
            response.writer.write("""{"error":"Too many requests","retryAfter":$windowSeconds}""")
            return
        }
        if (buckets.size > 10_000) buckets.clear()
        chain.doFilter(request, response)
    }
}
