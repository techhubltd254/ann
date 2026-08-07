package ke.go.kicc.engine

import com.fasterxml.jackson.databind.JsonNode
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
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status
import java.time.Instant

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class DelegationEngineTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun login(email: String, password: String): TokenResponse {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
    }

    private fun userIdByEmail(token: String, email: String): Long {
        val res = mockMvc.perform(get("/api/admin/users").headers(bearer(token)))
            .andExpect(status().isOk).andReturn()
        val users = objectMapper.readTree(res.response.contentAsString)
        return users.find { it["email"].asText() == email }!!["id"].asLong()
    }

    @Test
    fun nonKiccAdminCannotDelegate() {
        val county = login("county@kicc.go.ke", "county@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitorId = userIdByEmail(kicc.accessToken, "exhibitor@kicc.go.ke")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(county.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"subjectUserId":$exhibitorId,"role":"NATIONAL","scopeType":"ALL"}"""
                )
        ).andExpect(status().isForbidden)
    }

    @Test
    fun kiccCanDelegateAndRevokeImmediately() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")

        val createRes = mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"email":"ops@kicc.go.ke","fullName":"Ops Admin","tier":"EXHIBITOR","password":"Ops@2026","boothId":1}"""
                )
        ).andExpect(status().isOk).andReturn()
        val opsId = objectMapper.readTree(createRes.response.contentAsString)["id"].asLong()

        val ops = login("ops@kicc.go.ke", "Ops@2026")
        val countyId = userIdByEmail(kicc.accessToken, "county@kicc.go.ke")

        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(ops.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$countyId,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isForbidden)

        val grantRes = mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"subjectUserId":$opsId,"role":"NATIONAL","scopeType":"ALL","extraPrivileges":["DELEGATE"]}"""
                )
        ).andExpect(status().isOk).andExpect(jsonPath("$.role").value("NATIONAL")).andReturn()
        val delegationId = objectMapper.readTree(grantRes.response.contentAsString)["id"].asLong()

        val opsWithDelegation = login("ops@kicc.go.ke", "Ops@2026")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(opsWithDelegation.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$countyId,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isOk)

        mockMvc.perform(
            post("/api/admin/delegations/$delegationId/revoke")
                .headers(bearer(kicc.accessToken))
        ).andExpect(status().isOk).andExpect(jsonPath("$.revokedAt").exists())

        val opsAfterRevoke = login("ops@kicc.go.ke", "Ops@2026")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(opsAfterRevoke.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$countyId,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun expiredDelegationLosesAccess() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val createRes = mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"email":"exp@kicc.go.ke","fullName":"Expiry Admin","tier":"EXHIBITOR","password":"Exp@2026","boothId":2}"""
                )
        ).andExpect(status().isOk).andReturn()
        val expId = objectMapper.readTree(createRes.response.contentAsString)["id"].asLong()
        val expires = Instant.now().plusSeconds(2).toString()

        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"subjectUserId":$expId,"role":"NATIONAL","scopeType":"ALL","extraPrivileges":["DELEGATE"],"expiresAt":"$expires"}"""
                )
        ).andExpect(status().isOk)

        val expWithDelegation = login("exp@kicc.go.ke", "Exp@2026")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(expWithDelegation.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":1,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isOk)

        Thread.sleep(2500)

        val expAfterExpiry = login("exp@kicc.go.ke", "Exp@2026")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(expAfterExpiry.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":1,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun kiccRoleCannotBeDelegated() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val countyId = userIdByEmail(kicc.accessToken, "county@kicc.go.ke")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$countyId,"role":"KICC","scopeType":"ALL"}""")
        ).andExpect(status().isBadRequest)
    }

    @Test
    fun auditTrailCapturesGrantAndRevoke() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val createRes = mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"email":"aud@kicc.go.ke","fullName":"Audit Admin","tier":"EXHIBITOR","password":"Aud@2026","boothId":3}"""
                )
        ).andExpect(status().isOk).andReturn()
        val audId = objectMapper.readTree(createRes.response.contentAsString)["id"].asLong()

        val grantRes = mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(kicc.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$audId,"role":"NATIONAL","scopeType":"ALL"}""")
        ).andExpect(status().isOk).andReturn()
        val delegationId = objectMapper.readTree(grantRes.response.contentAsString)["id"].asLong()

        mockMvc.perform(
            post("/api/admin/delegations/$delegationId/revoke")
                .headers(bearer(kicc.accessToken))
        ).andExpect(status().isOk)

        val auditRes = mockMvc.perform(
            get("/api/admin/audit-logs")
                .headers(bearer(kicc.accessToken))
        ).andExpect(status().isOk).andReturn()
        val logs: JsonNode = objectMapper.readTree(auditRes.response.contentAsString)
        val actions = logs.map { it["action"].asText() }
        assertTrue(actions.contains("DELEGATION_GRANTED"))
        assertTrue(actions.contains("DELEGATION_REVOKED"))
        assertTrue(actions.contains("USER_CREATED"))
    }
}
