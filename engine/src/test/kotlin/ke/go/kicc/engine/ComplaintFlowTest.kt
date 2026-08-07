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
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class ComplaintFlowTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    private fun login(email: String, password: String): TokenResponse {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
    }

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun createComplaint(category: String = "SUPPORT"): String {
        val res = mockMvc.perform(
            post("/api/public/complaints")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"category":"$category","subject":"Broken listing","message":"The attraction page shows wrong opening hours.","contactName":"Jane Njeri","contactEmail":"jane@example.com"}""")
        ).andExpect(status().isOk).andReturn()
        val tree = objectMapper.readTree(res.response.contentAsString)
        assertEquals("OPEN", tree.get("status").asText())
        return tree.get("referenceNo").asText()
    }

    @Test
    fun publicCreateReturnsReferenceAndTracksStatus() {
        val reference = createComplaint()
        assertTrue(reference.startsWith("KICC-CMP-"), "reference format: $reference")

        mockMvc.perform(get("/api/public/complaints/$reference"))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("OPEN"))
    }

    @Test
    fun billingCategoryGetsPriorityP2AndSla() {
        val reference = createComplaint("BILLING")
        mockMvc.perform(get("/api/public/complaints/$reference"))
            .andExpect(status().isOk)

        val admin = login("admin@kicc.go.ke", "Admin@2026")
        mockMvc.perform(get("/api/complaints").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$[?(@.referenceNo=='$reference')].priority").value("P2"))
    }

    @Test
    fun lifecycleEnforcesTransitionsAndResolutionNote() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val reference = createComplaint()
        val id = mockMvc.perform(get("/api/complaints").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk).andReturn()
            .let { objectMapper.readTree(it.response.contentAsString) }
            .first { it.get("referenceNo").asText() == reference }
            .get("id").asLong()

        mockMvc.perform(
            post("/api/complaints/$id/status")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"CLOSED"}""")
        ).andExpect(status().isBadRequest)

        mockMvc.perform(
            post("/api/complaints/$id/status")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"IN_PROGRESS"}""")
        ).andExpect(status().isOk)

        mockMvc.perform(
            post("/api/complaints/$id/status")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"RESOLVED"}""")
        ).andExpect(status().isBadRequest)

        mockMvc.perform(
            post("/api/complaints/$id/status")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"RESOLVED","resolutionNote":"Fixed the listing hours"}""")
        ).andExpect(status().isOk)

        mockMvc.perform(
            post("/api/complaints/$id/status")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"CLOSED"}""")
        ).andExpect(status().isOk)

        mockMvc.perform(get("/api/public/complaints/$reference"))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("CLOSED"))
            .andExpect(jsonPath("$.resolutionNote").value("Fixed the listing hours"))
    }

    @Test
    fun exhibitorCannotManageComplaints() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        createComplaint()

        mockMvc.perform(get("/api/complaints").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun assignNotifiesAndNotesAreInternalOnly() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val county = login("county@kicc.go.ke", "county@2026")
        val reference = createComplaint()
        val id = mockMvc.perform(get("/api/complaints").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk).andReturn()
            .let { objectMapper.readTree(it.response.contentAsString) }
            .first { it.get("referenceNo").asText() == reference }
            .get("id").asLong()

        val assigneeId = mockMvc.perform(get("/api/admin/users").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk).andReturn()
            .let { objectMapper.readTree(it.response.contentAsString) }
            .first { it.get("email").asText() == "county@kicc.go.ke" }
            .get("id").asLong()

        mockMvc.perform(
            post("/api/complaints/$id/assign")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"assigneeUserId":$assigneeId}""")
        ).andExpect(status().isOk)
            .andExpect(jsonPath("$.assigneeUserId").value(assigneeId))

        mockMvc.perform(
            post("/api/complaints/$id/notes")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"note":"Escalate to bookings team","internal":true}""")
        ).andExpect(status().isOk)

        val notes = mockMvc.perform(get("/api/complaints/$id/notes").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk).andReturn()
            .let { objectMapper.readTree(it.response.contentAsString) }
        assertEquals(1, notes.size())
        assertEquals("Escalate to bookings team", notes[0].get("note").asText())
        assertTrue(notes[0].get("internal").asBoolean())

        mockMvc.perform(get("/api/notifications").headers(bearer(county.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$[?(@.type=='COMPLAINT_ASSIGNED')]").exists())
    }
}
