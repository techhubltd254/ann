package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.audit.AuditLog
import ke.go.kicc.engine.audit.AuditLogRepository
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.auth.TokenResponse
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Assertions.assertFalse
import org.junit.jupiter.api.Assertions.assertNotNull
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

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class AuditChainTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    @Autowired
    lateinit var auditLogRepository: AuditLogRepository

    @Autowired
    lateinit var auditLogService: AuditLogService

    private fun login(email: String, password: String): TokenResponse {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
    }

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun verifyChain(): Map<String, Any?> {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val res = mockMvc.perform(get("/api/admin/audit-logs/verify").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk).andReturn()
        val tree = objectMapper.readTree(res.response.contentAsString)
        return mapOf(
            "valid" to tree.get("valid").asBoolean(),
            "checked" to tree.get("checked").asInt(),
            "brokenAtId" to (tree.get("brokenAtId").let { if (it.isNull) null else it.asLong() }),
            "legacyRows" to tree.get("legacyRows").asInt(),
            "tailHash" to (tree.get("tailHash").let { if (it.isNull) null else it.asText() })
        )
    }

    private fun hasAction(action: String, contains: String? = null): Boolean =
        auditLogRepository.findAllByOrderByCreatedAtDesc().any { log ->
            log.action == action && (contains == null || (log.detail ?: "").contains(contains))
        }

    @Test
    fun chainGrowsAndVerifies() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        mockMvc.perform(get("/api/sync/pull?countySlug=kilifi").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)

        assertTrue(hasAction("LOGIN_SUCCESS"))
        assertTrue(hasAction("SYNC_PULL", "county=kilifi"))

        val status = verifyChain()
        assertTrue(status["valid"] as Boolean)
        assertTrue(status["checked"] as Int >= 2)
        assertNotNull(status["tailHash"])
    }

    @Test
    fun loginFailuresAreAuditedWithoutActor() {
        mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"ghost@kicc.go.ke","password":"wrong"}""")
        ).andExpect(status().isUnauthorized)

        assertTrue(hasAction("LOGIN_FAILED", "ghost@kicc.go.ke"))
        assertTrue(auditLogRepository.findAllByOrderByCreatedAtDesc().any { it.action == "LOGIN_FAILED" && it.actorUserId == 0L })
        assertTrue(verifyChain()["valid"] as Boolean)
    }

    @Test
    fun tamperingIsDetectedAndRestored() {
        login("admin@kicc.go.ke", "Admin@2026")
        val target = auditLogRepository.findTopByOrderByIdDesc()
            ?: throw AssertionError("no audit rows")
        val originalDetail = target.detail
        target.detail = "TAMPERED-BEFORE-VERIFY"
        auditLogRepository.save(target)

        try {
            val status = verifyChain()
            assertFalse(status["valid"] as Boolean)
            assertNotNull(status["brokenAtId"])
        } finally {
            target.detail = originalDetail
            auditLogRepository.save(target)
        }
        assertTrue(verifyChain()["valid"] as Boolean)
    }

    @Test
    fun refreshAndLogoutAreAudited() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val refreshRes = mockMvc.perform(
            post("/api/auth/refresh")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"refreshToken":"${admin.refreshToken}"}""")
        ).andExpect(status().isOk).andReturn()
        val newTokens = objectMapper.readValue(refreshRes.response.contentAsString, TokenResponse::class.java)

        mockMvc.perform(
            post("/api/auth/logout")
                .headers(bearer(newTokens.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"refreshToken":"${newTokens.refreshToken}"}""")
        ).andExpect(status().isOk)

        assertTrue(hasAction("TOKEN_REFRESH"))
        assertTrue(hasAction("LOGOUT"))
        assertTrue(verifyChain()["valid"] as Boolean)
    }
}
