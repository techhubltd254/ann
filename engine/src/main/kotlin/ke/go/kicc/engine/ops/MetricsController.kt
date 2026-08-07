package ke.go.kicc.engine.ops

import ke.go.kicc.engine.user.UserRepository
import ke.go.kicc.engine.data.CountyRepository
import org.springframework.jdbc.core.JdbcTemplate
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RestController
import java.lang.management.ManagementFactory

/**
 * Prometheus metrics (public, non-sensitive counters only).
 * Scrape: GET /api/metrics — node/prom scrape_configs entry point.
 */
@RestController
class MetricsController(
    private val jdbc: JdbcTemplate,
    private val users: UserRepository,
    private val counties: CountyRepository,
) {
    @GetMapping("/api/metrics", produces = ["text/plain; version=0.0.4"])
    fun metrics(): String {
        val sb = StringBuilder()
        fun gauge(name: String, help: String, value: Number) {
            sb.append("# HELP $name $help\n# TYPE $name gauge\n$name $value\n")
        }
        val runtime = Runtime.getRuntime()
        gauge("kicc_jvm_uptime_seconds", "JVM uptime", ManagementFactory.getRuntimeMXBean().uptime / 1000)
        gauge("kicc_jvm_memory_used_bytes", "JVM used heap", runtime.totalMemory() - runtime.freeMemory())
        gauge("kicc_users_total", "Registered users", runCatching { users.count() }.getOrDefault(-1))
        gauge("kicc_counties_total", "Counties", runCatching { counties.count() }.getOrDefault(-1))
        for ((table, metric) in mapOf(
            "bookings" to "kicc_bookings_total",
            "payment_intents" to "kicc_payments_total",
            "dispute_cases" to "kicc_disputes_total",
            "orders" to "kicc_orders_total",
            "embeddings" to "kicc_embeddings_total",
        )) {
            gauge(metric, "Total $table", runCatching {
                jdbc.queryForObject("SELECT COUNT(*) FROM $table", Long::class.java) ?: -1
            }.getOrDefault(-1))
        }
        return sb.toString()
    }
}
