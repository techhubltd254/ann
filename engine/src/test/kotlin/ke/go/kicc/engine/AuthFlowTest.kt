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
class AuthFlowTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    private val tiers = listOf(
        "admin@kicc.go.ke" to "Admin@2026",
        "national@kicc.go.ke" to "national@2026",
        "county@kicc.go.ke" to "county@2026",
        "exhibitor@kicc.go.ke" to "exhibitor@2026"
    )

    private fun login(email: String, password: String): TokenResponse {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.accessToken").exists())
            .andExpect(jsonPath("$.refreshToken").exists())
            .andReturn()
        return objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
    }

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    @Test
    fun allFourTiersCanLogin() {
        tiers.forEach { (email, password) ->
            val res = mockMvc.perform(
                post("/api/auth/login")
                    .contentType(MediaType.APPLICATION_JSON)
                    .content("""{"email":"$email","password":"$password"}""")
            )
                .andExpect(status().isOk)
                .andExpect(jsonPath("$.accessToken").exists())
                .andReturn()
            assertTrue(res.response.contentAsString.contains("refreshToken"))
        }
    }

    @Test
    fun wrongPasswordIsRejected() {
        mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"admin@kicc.go.ke","password":"wrong-password"}""")
        ).andExpect(status().isUnauthorized)
    }

    @Test
    fun kiccHasDelegatePrivilegeOthersDoNot() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        mockMvc.perform(get("/api/me").headers(bearer(kicc.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.privileges").value(org.hamcrest.Matchers.hasItem("DELEGATE")))

        val county = login("county@kicc.go.ke", "county@2026")
        mockMvc.perform(get("/api/me").headers(bearer(county.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.privileges").value(org.hamcrest.Matchers.not(org.hamcrest.Matchers.hasItem("DELEGATE"))))
            .andExpect(jsonPath("$.tenant.countySlug").value("kilifi"))
    }

    @Test
    fun refreshRotatesTokens() {
        val first = login("national@kicc.go.ke", "national@2026")
        mockMvc.perform(
            post("/api/auth/refresh")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"refreshToken":"${first.refreshToken}"}""")
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.accessToken").exists())

        mockMvc.perform(
            post("/api/auth/refresh")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"refreshToken":"${first.refreshToken}"}""")
        ).andExpect(status().isUnauthorized)
    }

    @Test
    fun protectedEndpointRequiresToken() {
        mockMvc.perform(get("/api/me")).andExpect(status().isUnauthorized)
    }

    @Test
    fun cookieAuthWorksAndIsHttpOnly() {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"admin@kicc.go.ke","password":"Admin@2026"}""")
        )
            .andExpect(status().isOk)
            .andReturn()
        val cookies = res.response.getHeaders(HttpHeaders.SET_COOKIE)
        assertTrue(cookies.any { it.startsWith("kicc_access=") && it.contains("HttpOnly") && it.contains("SameSite=Strict") })
        assertTrue(cookies.any { it.startsWith("kicc_refresh=") && it.contains("HttpOnly") })

        val access = cookies.first { it.startsWith("kicc_access=") }.substringBefore(";")
        val name = access.substringBefore("=")
        val value = access.substringAfter("=")

        mockMvc.perform(get("/api/me").cookie(org.springframework.mock.web.MockCookie(name, value)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.email").value("admin@kicc.go.ke"))
    }

    @Test
    fun refreshViaCookieRotatesTokens() {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"county@kicc.go.ke","password":"county@2026"}""")
        )
            .andExpect(status().isOk)
            .andReturn()
        val refresh = res.response.getHeaders(HttpHeaders.SET_COOKIE)
            .first { it.startsWith("kicc_refresh=") }.substringBefore(";")
        val name = refresh.substringBefore("=")
        val value = refresh.substringAfter("=")

        mockMvc.perform(
            post("/api/auth/refresh")
                .cookie(org.springframework.mock.web.MockCookie(name, value))
                .contentType(MediaType.APPLICATION_JSON)
                .content("{}")
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.accessToken").exists())
    }

    @Test
    fun exhibitorHasNoUsersManageByDefault() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        mockMvc.perform(get("/api/me").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.privileges").value(org.hamcrest.Matchers.not(org.hamcrest.Matchers.hasItem("USERS_MANAGE"))))
    }

    @Test
    fun unknownApiPathReturns404Not500() {
        val county = login("county@kicc.go.ke", "county@2026")
        mockMvc.perform(get("/api/sync/bundles/999").headers(bearer(county.accessToken)))
            .andExpect(status().isNotFound)
            .andExpect(jsonPath("$.error").value("Not found"))
    }
}
