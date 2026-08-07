package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.auth.TokenResponse
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
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.*

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class RecommendationFlowTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    @Test
    fun mockProviderReturnsScoredRecommendations() {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"admin@kicc.go.ke","password":"Admin@2026"}""")
        ).andExpect(status().isOk).andReturn()
        val token = objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
        val headers = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer ${token.accessToken}") }

        val response = mockMvc.perform(
            get("/api/recommendations?countySlug=kilifi&limit=5").headers(headers)
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.provider").value("mock"))
            .andExpect(jsonPath("$.count").value(5))
            .andReturn()

        val tree = objectMapper.readTree(response.response.contentAsString)
        tree.get("recommendations").forEach {
            assertTrue(it.get("name").asText().isNotBlank())
            assertTrue(it.get("score").asDouble() in 0.0..1.0)
            assertTrue(it.get("reason").asText().isNotBlank())
        }
    }

    @Test
    fun typeFilterLimitsToEntityType() {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"county@kicc.go.ke","password":"county@2026"}""")
        ).andExpect(status().isOk).andReturn()
        val token = objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
        val headers = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer ${token.accessToken}") }

        mockMvc.perform(
            get("/api/recommendations?countySlug=kilifi&type=hotels").headers(headers)
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.recommendations[0].entityType").value("hotels"))
    }
}
