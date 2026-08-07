package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.auth.TokenResponse
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Assertions.assertTrue
import org.junit.jupiter.api.Test
import org.springframework.beans.factory.annotation.Autowired
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc
import org.springframework.boot.test.context.SpringBootTest
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.test.context.ActiveProfiles
import org.springframework.test.web.servlet.MockMvc
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status
import java.nio.charset.StandardCharsets
import java.util.*
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class SyncFlowTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    private val secret = "kicc-sync-dev-secret-2026"

    private fun login(email: String, password: String): TokenResponse {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
    }

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun hmac(data: String): String {
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(SecretKeySpec(secret.toByteArray(), "HmacSHA256"))
        return Base64.getEncoder().encodeToString(mac.doFinal(data.toByteArray(StandardCharsets.UTF_8)))
    }

    @Test
    fun pullReturnsSignedCountyBundle() {
        val county = login("county@kicc.go.ke", "county@2026")
        val res = mockMvc.perform(
            get("/api/sync/pull?entityType=attractions").headers(bearer(county.accessToken))
        )
            .andExpect(status().isOk)
            .andReturn()

        assertEquals("kilifi", res.response.getHeader("X-Sync-Signature")?.let { "kilifi" })

        val body = res.response.contentAsString
        val tree = objectMapper.readTree(body)
        assertEquals("kilifi", tree.get("countySlug").asText())
        assertTrue(tree.get("rows").size() > 0)
        assertEquals("attractions", tree.get("rows")[0].get("entityType").asText())
    }

    @Test
    fun pushWithWrongSignatureIsRejected() {
        val county = login("county@kicc.go.ke", "county@2026")
        val payload = """{"countySlug":"kilifi","rows":[]}"""
        mockMvc.perform(
            post("/api/sync/push")
                .headers(bearer(county.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .header("X-Sync-Signature", "AAAA-tampered")
                .content(payload)
        ).andExpect(status().isUnauthorized)
    }

    @Test
    fun pushAppliesLastWriteWinsAndCountyIsForced() {
        val county = login("county@kicc.go.ke", "county@2026")

        val pullRes = mockMvc.perform(
            get("/api/sync/pull?entityType=attractions").headers(bearer(county.accessToken))
        ).andExpect(status().isOk).andReturn()
        val pulled = objectMapper.readTree(pullRes.response.contentAsString)
        val row = pulled.get("rows")[0].get("row").deepCopy<com.fasterxml.jackson.databind.node.ObjectNode>()
        val id = row.get("id").asLong()
        row.put("name", "Synced Renamed Attraction")
        row.put("updatedAt", "2999-01-01 00:00:00")

        val pushBody = """{"countySlug":"mombasa","rows":[{"entityType":"attractions","row":${objectMapper.writeValueAsString(row)}}]}"""
        val res = mockMvc.perform(
            post("/api/sync/push")
                .headers(bearer(county.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .header("X-Sync-Signature", hmac(pushBody))
                .content(pushBody)
        ).andExpect(status().isOk).andReturn()

        val result = objectMapper.readTree(res.response.contentAsString)
        assertEquals("kilifi", result.get("countySlug").asText())
        assertEquals(1, result.get("applied").asInt())

        mockMvc.perform(get("/api/data/attractions/$id").headers(bearer(county.accessToken)))
            .andExpect(status().isOk)
            .andExpect(org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath("$.name").value("Synced Renamed Attraction"))
    }

    @Test
    fun stalePushIsSkipped() {
        val county = login("county@kicc.go.ke", "county@2026")

        val pullRes = mockMvc.perform(
            get("/api/sync/pull?entityType=attractions").headers(bearer(county.accessToken))
        ).andExpect(status().isOk).andReturn()
        val row = objectMapper.readTree(pullRes.response.contentAsString).get("rows")[0].get("row").deepCopy<com.fasterxml.jackson.databind.node.ObjectNode>()
        row.put("name", "Stale Name")
        row.put("updatedAt", "2000-01-01 00:00:00")

        val pushBody = """{"countySlug":"kilifi","rows":[{"entityType":"attractions","row":${objectMapper.writeValueAsString(row)}}]}"""
        mockMvc.perform(
            post("/api/sync/push")
                .headers(bearer(county.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .header("X-Sync-Signature", hmac(pushBody))
                .content(pushBody)
        ).andExpect(status().isOk)
            .andExpect(org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath("$.applied").value(0))
            .andExpect(org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath("$.skipped").value(1))
    }

    @Test
    fun newOfflineRowIsInserted() {
        val county = login("county@kicc.go.ke", "county@2026")
        val row = mapOf(
            "name" to "Offline Created Hotel",
            "category" to "boutique",
            "starRating" to 4,
            "localId" to "offline-${System.nanoTime()}",
            "isPublished" to false,
            "updatedAt" to "2999-01-01 00:00:00"
        )
        val pushBody = """{"countySlug":"kilifi","rows":[{"entityType":"hotels","row":${objectMapper.writeValueAsString(row)}}]}"""
        mockMvc.perform(
            post("/api/sync/push")
                .headers(bearer(county.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .header("X-Sync-Signature", hmac(pushBody))
                .content(pushBody)
        ).andExpect(status().isOk)
            .andExpect(org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath("$.applied").value(1))

        val list = mockMvc.perform(get("/api/data/hotels").headers(bearer(county.accessToken)))
            .andExpect(status().isOk)
            .andReturn()
        assertTrue(objectMapper.readTree(list.response.contentAsString)
            .any { it.get("name").asText() == "Offline Created Hotel" })
    }
}
