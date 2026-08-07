package ke.go.kicc.engine.ai

import ke.go.kicc.engine.data.*
import org.springframework.beans.factory.annotation.Value
import org.springframework.http.HttpStatus
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Component
import org.springframework.stereotype.Service
import org.springframework.web.bind.annotation.*
import org.springframework.web.client.RestClient
import org.springframework.web.server.ResponseStatusException
import java.net.URI

data class Recommendation(
    val entityType: String,
    val id: Long,
    val name: String,
    val score: Double,
    val reason: String
)

interface RecommendationProvider {
    fun recommend(countySlug: String, type: String?, limit: Int): List<Recommendation>
}

@Component
class MockRecommendationProvider(
    private val countyRepository: CountyRepository,
    private val attractionRepository: AttractionRepository,
    private val hotelRepository: HotelRepository,
    private val farmRepository: FarmRepository,
    private val productRepository: ProductRepository
) : RecommendationProvider {

    override fun recommend(countySlug: String, type: String?, limit: Int): List<Recommendation> {
        val county = countyRepository.findBySlug(countySlug)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found: $countySlug")
        val countyId = county.id!!
        val out = mutableListOf<Recommendation>()

        if (type.isNullOrBlank() || type == "attractions") {
            attractionRepository.findAllByCountyIdOrderByIdAsc(countyId)
                .filter { it.isPublished }
                .forEach { a ->
                    val score = if (a.category in setOf("nature", "beach", "park", "heritage")) 0.9 else 0.75
                    out.add(Recommendation("attractions", a.id!!, a.name, score, "Top ${a.category ?: "attraction"} in ${county.name}"))
                }
        }
        if (type.isNullOrBlank() || type == "hotels") {
            hotelRepository.findAllByCountyIdOrderByIdAsc(countyId)
                .filter { it.isPublished }
                .forEach { h ->
                    val score = (h.starRating ?: 3) / 5.0
                    out.add(Recommendation("hotels", h.id!!, h.name, score, "${h.starRating ?: 3}-star stay in ${county.name}"))
                }
        }
        if (type.isNullOrBlank() || type == "farms") {
            farmRepository.findAllByCountyIdOrderByIdAsc(countyId)
                .filter { it.isPublished }
                .forEach { f ->
                    val size = f.sizeAcres ?: 50.0
                    val score = (size / 500.0).coerceAtMost(0.95).coerceAtLeast(0.4)
                    out.add(Recommendation("farms", f.id!!, f.name, score, "Farm with ${f.mainCrops ?: "produce"} in ${county.name}"))
                }
        }
        if (type.isNullOrBlank() || type == "products") {
            productRepository.findAllByCountyIdOrderByIdAsc(countyId)
                .filter { it.isPublished && it.status == "available" }
                .forEach { p ->
                    val price = p.price ?: 0.0
                    val score = if (price > 0) (20000 / (price + 1000)).coerceAtMost(0.9) else 0.7
                    out.add(Recommendation("products", p.id!!, p.name, score, "Featured ${p.category ?: "product"} at KES ${price.toInt()}"))
                }
        }
        return out.sortedByDescending { it.score }.take(limit)
    }
}

@Component
class GeminiRecommendationProvider(
    @Value("\${kicc.ai.gemini-api-key}") private val apiKey: String,
    @Value("\${kicc.ai.gemini-endpoint}") private val endpoint: String
) : RecommendationProvider {

    private val client: RestClient = RestClient.builder().build()

    override fun recommend(countySlug: String, type: String?, limit: Int): List<Recommendation> {
        if (apiKey.isBlank()) {
            throw ResponseStatusException(HttpStatus.SERVICE_UNAVAILABLE, "AI provider is not configured (set GEMINI_API_KEY)")
        }
        val prompt = """
            Recommend $limit notable $type entities in $countySlug county, Kenya, for a tourism and trade platform.
            Reply with ONLY a JSON array of objects: [{"entityType": "...", "name": "...", "reason": "..."}]
        """.trimIndent()
        val body = mapOf("contents" to listOf(mapOf("parts" to listOf(mapOf("text" to prompt)))))
        val res = client.post()
            .uri(URI.create("$endpoint/models/gemini-1.5-flash:generateContent?key=$apiKey"))
            .header("Content-Type", "application/json")
            .body(body)
            .retrieve()
            .body(String::class.java) ?: "{}"
        val text = Regex("\"text\"\\s*:\\s*\"([^\"]*)\"").find(res)?.groupValues?.get(1) ?: return emptyList()
        val json = text.replace("\\n", "").replace("\\\\", "")
        val items = Regex("\\{[^{}]*\\}").findAll(json).mapNotNull { m ->
            val it = m.value
            val name = Regex("\"name\"\\s*:\\s*\"([^\"]+)\"").find(it)?.groupValues?.get(1) ?: return@mapNotNull null
            val entityType = Regex("\"entityType\"\\s*:\\s*\"([^\"]+)\"").find(it)?.groupValues?.get(1) ?: "generic"
            val reason = Regex("\"reason\"\\s*:\\s*\"([^\"]+)\"").find(it)?.groupValues?.get(1) ?: "AI recommendation"
            Recommendation(entityType, 0L, name, 0.5, reason)
        }.toList()
        return items.take(limit)
    }
}

@Service
class RecommendationService(
    private val mockProvider: MockRecommendationProvider,
    private val geminiProvider: GeminiRecommendationProvider,
    @Value("\${kicc.ai.provider:mock}") private val providerName: String
) {

    private fun provider(): RecommendationProvider =
        if (providerName == "gemini") geminiProvider else mockProvider

    fun recommend(countySlug: String, type: String?, limit: Int): Map<String, Any> {
        val items = provider().recommend(countySlug, type, limit)
        return mapOf(
            "provider" to (if (providerName == "gemini") "gemini" else "mock"),
            "countySlug" to countySlug,
            "count" to items.size,
            "recommendations" to items
        )
    }
}

@RestController
@RequestMapping("/api/recommendations")
class RecommendationController(private val recommendationService: RecommendationService) {

    @GetMapping
    @PreAuthorize("hasAuthority('ANALYTICS_VIEW')")
    fun recommend(
        @RequestParam countySlug: String,
        @RequestParam(required = false) type: String?,
        @RequestParam(defaultValue = "5") limit: Int
    ): Map<String, Any> = recommendationService.recommend(countySlug, type, limit.coerceIn(1, 20))
}
