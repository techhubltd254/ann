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
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.*

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class OnboardingFlowTest {

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

    private fun apply(orgName: String, email: String, answers: Map<String, String>) =
        mockMvc.perform(
            post("/api/onboarding/apply")
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    objectMapper.writeValueAsString(
                        mapOf(
                            "orgName" to orgName,
                            "contactName" to "Jane Doe",
                            "email" to email,
                            "answers" to answers
                        )
                    )
                )
        )

    @Test
    fun smallOrgIsRoutedToTemplate() {
        val res = apply(
            "Village Crafts Co-op",
            "crafts@example.com",
            mapOf(
                "employees" to "<10",
                "eventsPerYear" to "0",
                "listings" to "1-10",
                "itStaff" to "none",
                "connectivity" to "poor"
            )
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.route").value("TEMPLATE"))
            .andReturn()
        val body = objectMapper.readTree(res.response.contentAsString)
        assertTrue(body["score"].asInt() < 6)
    }

    @Test
    fun largeDigitalOrgIsRoutedToOwnServer() {
        val res = apply(
            "Coastal Dhow Lines Ltd",
            "ops@dhow.co.ke",
            mapOf(
                "employees" to "200+",
                "eventsPerYear" to "4+",
                "listings" to "50+",
                "itStaff" to "dedicated",
                "connectivity" to "good"
            )
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.route").value("OWN_SERVER"))
            .andReturn()
        val body = objectMapper.readTree(res.response.contentAsString)
        assertTrue(body["score"].asInt() >= 6)
    }

    @Test
    fun duplicateApplyReturnsSameApplicationIdempotently() {
        val answers = mapOf("employees" to "<10", "eventsPerYear" to "0", "listings" to "1-10", "itStaff" to "none", "connectivity" to "poor")
        val first = apply("Twice Traders", "twice@example.com", answers).andExpect(status().isOk).andReturn()
        val second = apply("Twice Traders", "twice@example.com", answers).andExpect(status().isOk).andReturn()
        val a = objectMapper.readTree(first.response.contentAsString)
        val b = objectMapper.readTree(second.response.contentAsString)
        assertEquals(a["applicantId"].asLong(), b["applicantId"].asLong())
    }

    @Test
    fun applyRequiresOrgNameAndEmail() {
        apply("", "bad@example.com", emptyMap()).andExpect(status().isBadRequest)
        apply("No Email", "", emptyMap()).andExpect(status().isBadRequest)
    }

    @Test
    fun reviewEndpointsRequireUsersManageAndApproveProvisionsAccount() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")

        val answers = mapOf("employees" to "<10", "eventsPerYear" to "0", "listings" to "1-10", "itStaff" to "none", "connectivity" to "poor")
        val applyRes = apply("Approved Org", "approved@example.com", answers).andExpect(status().isOk).andReturn()
        val applicantId = objectMapper.readTree(applyRes.response.contentAsString)["applicantId"].asLong()

        // exhibitor (no USERS_MANAGE) cannot list or approve
        mockMvc.perform(get("/api/onboarding/applicants").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isForbidden)
        mockMvc.perform(post("/api/onboarding/applicants/$applicantId/approve").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isForbidden)

        // admin lists, approves -> provisions an EXHIBITOR user with password
        mockMvc.perform(get("/api/onboarding/applicants").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$[0].orgName").value("Approved Org"))

        val approveRes = mockMvc.perform(post("/api/onboarding/applicants/$applicantId/approve").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("APPROVED"))
            .andExpect(jsonPath("$.provisionedEmail").value("approved@example.com"))
            .andExpect(jsonPath("$.provisionedPassword").exists())
            .andReturn()
        val review = objectMapper.readTree(approveRes.response.contentAsString)
        val email = review["provisionedEmail"].asText()
        val password = review["provisionedPassword"].asText()

        // provisioned account can log in as EXHIBITOR
        mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        )
            .andExpect(status().isOk)

        // double approve conflicts
        mockMvc.perform(post("/api/onboarding/applicants/$applicantId/approve").headers(bearer(admin.accessToken)))
            .andExpect(status().isConflict)
    }

    @Test
    fun rejectMarksApplicationRejected() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val answers = mapOf("employees" to "<10", "eventsPerYear" to "0", "listings" to "1-10", "itStaff" to "none", "connectivity" to "poor")
        val applyRes = apply("Reject Me", "reject@example.com", answers).andExpect(status().isOk).andReturn()
        val applicantId = objectMapper.readTree(applyRes.response.contentAsString)["applicantId"].asLong()

        mockMvc.perform(post("/api/onboarding/applicants/$applicantId/reject").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("REJECTED"))
    }

    @Test
    fun applyIsPublicWithoutAuth() {
        mockMvc.perform(
            post("/api/onboarding/apply")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"orgName":"Public Org","contactName":"P O","email":"public@example.com","answers":{"employees":"<10"}}""")
        ).andExpect(status().isOk)
    }
}
