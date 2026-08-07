package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import org.springframework.beans.factory.annotation.Autowired
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc
import org.springframework.boot.test.context.SpringBootTest
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.test.context.ActiveProfiles
import org.springframework.test.web.servlet.MockMvc
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class BookingFlowTest {

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

    private fun createExhibitor(kicc: String, email: String): Long {
        val res = mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","fullName":"Booth Buyer","tier":"EXHIBITOR","password":"Buyer@2026","boothId":9}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)["id"].asLong()
    }

    @Test
    fun exhibitorBooksBooth() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")

        val boothRes = mockMvc.perform(get("/api/data/booths?exhibitionId=1").headers(bearer(exhibitor)))
            .andExpect(status().isOk).andReturn()
        val booth = objectMapper.readTree(boothRes.response.contentAsString)[0]
        val boothId = booth["id"].asLong()

        val bookingRes = mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1,"notes":"priority corner"}""")
        ).andExpect(status().isOk).andReturn()
        val booking = objectMapper.readTree(bookingRes.response.contentAsString)
        assertTrue(booking["bookingReference"].asText().startsWith("KICC-"))
        assertEquals("PENDING", booking["status"].asText())
        assertEquals("KES", booking["currency"].asText())
        assertTrue(booking["total"].asDouble() > 0)

        mockMvc.perform(get("/api/data/booths/$boothId").headers(bearer(kicc)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.bookedQuantity").value(1))
    }

    @Test
    fun overbookingRejected() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothRes = mockMvc.perform(get("/api/data/booths?exhibitionId=1").headers(bearer(exhibitor)))
            .andExpect(status().isOk).andReturn()
        val booth = objectMapper.readTree(boothRes.response.contentAsString)[0]
        val boothId = booth["id"].asLong()
        val maxQty = booth["maxQuantity"].asInt()

        mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":${maxQty + 1}}""")
        ).andExpect(status().isBadRequest)
    }

    @Test
    fun exhibitorSeesOnlyOwnBookings() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        createExhibitor(kicc, "buyer2@kicc.go.ke")

        val buyer2 = login("buyer2@kicc.go.ke", "Buyer@2026")
        val boothRes = mockMvc.perform(get("/api/data/booths?exhibitionId=1").headers(bearer(buyer2)))
            .andExpect(status().isOk).andReturn()
        val boothId = objectMapper.readTree(boothRes.response.contentAsString)[1]["id"].asLong()

        mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(buyer2))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1}""")
        ).andExpect(status().isOk)

        val own = objectMapper.readTree(
            mockMvc.perform(get("/api/bookings").headers(bearer(buyer2)))
                .andExpect(status().isOk).andReturn().response.contentAsString
        )
        assertTrue(own.size() >= 1)
        own.forEach { assertTrue(it["bookingReference"].isTextual) }
    }

    @Test
    fun adminConfirmsThenCancelRestoresCapacity() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")

        val boothRes = mockMvc.perform(get("/api/data/booths?exhibitionId=1").headers(bearer(exhibitor)))
            .andExpect(status().isOk).andReturn()
        val boothId = objectMapper.readTree(boothRes.response.contentAsString)[2]["id"].asLong()

        val bookingRes = mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1}""")
        ).andExpect(status().isOk).andReturn()
        val bookingId = objectMapper.readTree(bookingRes.response.contentAsString)["id"].asLong()

        mockMvc.perform(
            patch("/api/bookings/$bookingId/status")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"CONFIRMED"}""")
        ).andExpect(status().isOk).andExpect(jsonPath("$.status").value("CONFIRMED"))

        mockMvc.perform(
            patch("/api/bookings/$bookingId/status")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"CANCELLED"}""")
        ).andExpect(status().isOk).andExpect(jsonPath("$.status").value("CANCELLED"))

        mockMvc.perform(get("/api/data/booths/$boothId").headers(bearer(kicc)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.bookedQuantity").value(0))
    }

    @Test
    fun exhibitorCanOnlyCancelOwnPendingBooking() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")

        val boothRes = mockMvc.perform(get("/api/data/booths?exhibitionId=1").headers(bearer(exhibitor)))
            .andExpect(status().isOk).andReturn()
        val boothId = objectMapper.readTree(boothRes.response.contentAsString)[3]["id"].asLong()

        val bookingRes = mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1}""")
        ).andExpect(status().isOk).andReturn()
        val bookingId = objectMapper.readTree(bookingRes.response.contentAsString)["id"].asLong()

        mockMvc.perform(
            patch("/api/bookings/$bookingId/status")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"CONFIRMED"}""")
        ).andExpect(status().isForbidden)

        mockMvc.perform(
            patch("/api/bookings/$bookingId/status")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"status":"CANCELLED"}""")
        ).andExpect(status().isOk)
    }

    @Test
    fun unauthenticatedRequestsRejected() {
        mockMvc.perform(get("/api/data/counties")).andExpect(status().isUnauthorized)
        mockMvc.perform(get("/api/bookings")).andExpect(status().isUnauthorized)
    }
}
