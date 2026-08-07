package ke.go.kicc.engine

import com.fasterxml.jackson.databind.JsonNode
import com.fasterxml.jackson.databind.ObjectMapper
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
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.delete
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class ScopeEnforcementTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun login(email: String, password: String): String {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)["accessToken"].asText()
    }

    private fun createUser(kicc: String, email: String, tier: String, countySlug: String?): Long {
        val body = buildString {
            append("""{"email":"$email","fullName":"Scope Test User","tier":"$tier","password":"Scope@2026"""")
            if (countySlug != null) append(""","countySlug":"$countySlug"""")
            append("}")
        }
        val res = mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content(body)
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)["id"].asLong()
    }

    private fun grant(kicc: String, subjectId: Long, role: String, scopeType: String, scopeValue: String?, extras: String = "[]"): Long {
        val value = scopeValue?.let { ""","scopeValue":"$it"""" } ?: ""
        val res = mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"subjectUserId":$subjectId,"role":"$role","scopeType":"$scopeType"$value,"extraPrivileges":$extras}"""
                )
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)["id"].asLong()
    }

    private fun list(resource: String, token: String, countySlug: String?): JsonNode {
        val url = if (countySlug != null) "$resource?countySlug=$countySlug" else resource
        val res = mockMvc.perform(get(url).headers(bearer(token))).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)
    }

    @Test
    fun countyScopedDelegationExtendsDataReachAndRevocationDropsIt() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val subjectId = createUser(kicc, "reach@kicc.go.ke", "EXHIBITOR", "kilifi")
        val subject = login("reach@kicc.go.ke", "Scope@2026")

        mockMvc.perform(get("/api/data/attractions?countySlug=muranga").headers(bearer(subject)))
            .andExpect(status().isNotFound)

        val delegationId = grant(kicc, subjectId, "COUNTY", "COUNTY", "muranga")

        val after = login("reach@kicc.go.ke", "Scope@2026")
        mockMvc.perform(get("/api/data/attractions?countySlug=muranga").headers(bearer(after)))
            .andExpect(status().isOk)
        mockMvc.perform(get("/api/data/attractions?countySlug=kilifi").headers(bearer(after)))
            .andExpect(status().isOk)
        mockMvc.perform(get("/api/data/attractions?countySlug=nakuru").headers(bearer(after)))
            .andExpect(status().isNotFound)

        val syncMuranga = mockMvc.perform(get("/api/sync/pull?countySlug=muranga").headers(bearer(after)))
            .andExpect(status().isOk).andReturn()
        assertEquals("muranga", objectMapper.readTree(syncMuranga.response.contentAsString)["countySlug"].asText())
        mockMvc.perform(get("/api/sync/pull?countySlug=nakuru").headers(bearer(after)))
            .andExpect(status().isForbidden)

        mockMvc.perform(post("/api/admin/delegations/$delegationId/revoke").headers(bearer(kicc)))
            .andExpect(status().isOk)

        val revoked = login("reach@kicc.go.ke", "Scope@2026")
        mockMvc.perform(get("/api/data/attractions?countySlug=muranga").headers(bearer(revoked)))
            .andExpect(status().isNotFound)
    }

    @Test
    fun sectorScopedDelegationFiltersSectorEntities() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val allEntities = list("/api/data/sector-entities", kicc, "kilifi")
        assertTrue(allEntities.size() > 0)
        val sectorId = allEntities[0]["sectorId"].asLong()

        val subjectId = createUser(kicc, "sector@kicc.go.ke", "EXHIBITOR", "kilifi")
        grant(kicc, subjectId, "EXHIBITOR", "SECTOR", sectorId.toString())

        val subject = login("sector@kicc.go.ke", "Scope@2026")
        val visible = list("/api/data/sector-entities", subject, "kilifi")
        assertTrue(visible.size() > 0)
        visible.forEach { assertEquals(sectorId, it["sectorId"].asLong(), "sector-scoped delegate must only see its sector") }
    }

    @Test
    fun scopedDelegateCannotGrantWiderThanEnvelope() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val subjectId = createUser(kicc, "grantor@kicc.go.ke", "EXHIBITOR", "kilifi")
        val targetId = createUser(kicc, "grantee@kicc.go.ke", "EXHIBITOR", "kilifi")
        grant(kicc, subjectId, "NATIONAL", "COUNTY", "muranga", """["DELEGATE"]""")

        val scoped = login("grantor@kicc.go.ke", "Scope@2026")
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(scoped))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$targetId,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isForbidden)
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(scoped))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$targetId,"role":"EXHIBITOR","scopeType":"COUNTY","scopeValue":"mombasa"}""")
        ).andExpect(status().isForbidden)
        mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(scoped))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$targetId,"role":"EXHIBITOR","scopeType":"SECTOR","scopeValue":"not-a-number"}""")
        ).andExpect(status().isBadRequest)

        val within = mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(scoped))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$targetId,"role":"EXHIBITOR","scopeType":"COUNTY","scopeValue":"muranga"}""")
        ).andExpect(status().isOk).andReturn()
        val withinId = objectMapper.readTree(within.response.contentAsString)["id"].asLong()
        mockMvc.perform(post("/api/admin/delegations/$withinId/revoke").headers(bearer(scoped)))
            .andExpect(status().isOk)

        val kiccGrant = grant(kicc, subjectId, "NATIONAL", "ALL", null)
        mockMvc.perform(post("/api/admin/delegations/$kiccGrant/revoke").headers(bearer(scoped)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun userAdminScopedToEnvelope() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val county = login("county@kicc.go.ke", "county@2026")

        mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"inside@kilifi.go.ke","fullName":"Kilifi Officer","tier":"EXHIBITOR","password":"Scope@2026","countySlug":"kilifi"}""")
        ).andExpect(status().isOk)
        mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"outside@muranga.go.ke","fullName":"Muranga Officer","tier":"EXHIBITOR","password":"Scope@2026","countySlug":"muranga"}""")
        ).andExpect(status().isForbidden)
        mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"nation@kicc.go.ke","fullName":"National Officer","tier":"NATIONAL","password":"Scope@2026"}""")
        ).andExpect(status().isForbidden)

        val visible = list("/api/admin/users", county, null)
        visible.forEach { assertEquals("kilifi", it["countySlug"].asText(), "county admin list must be scoped to own county") }

        val murangaUserId = createUser(kicc, "muranga-op@kicc.go.ke", "COUNTY", "muranga")
        mockMvc.perform(delete("/api/admin/users/$murangaUserId").headers(bearer(county)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun auditLogScopedToEnvelope() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        createUser(kicc, "muranga-audit@kicc.go.ke", "COUNTY", "muranga")
        createUser(kicc, "kilifi-audit@kicc.go.ke", "EXHIBITOR", "kilifi")
        createUser(kicc, "nakuru-outsider@kicc.go.ke", "EXHIBITOR", "nakuru")
        val subjectId = createUser(kicc, "auditor@kicc.go.ke", "EXHIBITOR", "kilifi")
        grant(kicc, subjectId, "NATIONAL", "COUNTY", "muranga", """["DELEGATE"]""")

        val auditor = login("auditor@kicc.go.ke", "Scope@2026")
        val res = mockMvc.perform(get("/api/admin/audit-logs").headers(bearer(auditor)))
            .andExpect(status().isOk).andReturn()
        val logs = objectMapper.readTree(res.response.contentAsString)

        val targetUserIds = logs.mapNotNull { it["targetUserId"]?.takeIf { n -> !n.isNull }?.asLong() }.toSet()
        assertTrue(targetUserIds.isNotEmpty(), "scoped auditor should still see activity inside its envelope")
        val murangaId = userIdByEmail(kicc, "muranga-audit@kicc.go.ke")
        assertTrue(targetUserIds.contains(murangaId), "must see USER_CREATED targeting the muranga user (delegated county)")
        val kilifiId = userIdByEmail(kicc, "kilifi-audit@kicc.go.ke")
        assertTrue(targetUserIds.contains(kilifiId), "must see activity in own home county")
        val nakuruId = userIdByEmail(kicc, "nakuru-outsider@kicc.go.ke")
        assertTrue(!targetUserIds.contains(nakuruId), "must NOT see activity targeting users outside its envelope")
    }

    private fun userIdByEmail(kicc: String, email: String): Long {
        val res = mockMvc.perform(get("/api/admin/users").headers(bearer(kicc)))
            .andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)
            .find { it["email"].asText() == email }!!["id"].asLong()
    }
}
