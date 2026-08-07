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
class PaymentFlowTest {

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

    private fun createBooth(token: String): Long {
        val res = mockMvc.perform(
            post("/api/data/booths")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"exhibitionId":1,"boothNumber":"PAY-${System.nanoTime() % 100000}","name":"Pay Booth","price":10000,"maxQuantity":5,"bookedQuantity":0,"status":"available"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString).get("id").asLong()
    }

    private fun bookBooth(token: String, boothId: Long): Long {
        val res = mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1,"notes":"pay test"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString).get("id").asLong()
    }

    private fun initiate(token: String, bookingId: Long, method: String = "mock", phone: String? = null): Long {
        val res = mockMvc.perform(
            post("/api/payments")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"bookingId":$bookingId,"method":"$method","phone":${phone?.let { "\"$it\"" } ?: "null"}}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString).get("id").asLong()
    }

    private fun bookingStatus(token: String, bookingId: Long): String {
        val res = mockMvc.perform(get("/api/bookings").headers(bearer(token))).andExpect(status().isOk).andReturn()
        val bookings = objectMapper.readTree(res.response.contentAsString)
        return bookings.first { it.get("id").asLong() == bookingId }.get("status").asText()
    }

    @Test
    fun mockPaymentCompletesAndMarksBookingPaid() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)

        val paymentId = initiate(exhibitor.accessToken, bookingId, "mock", "254722000111")

        val detail = mockMvc.perform(get("/api/payments/$paymentId").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("SUCCESS"))
            .andExpect(jsonPath("$.providerRef").value(org.hamcrest.Matchers.startsWith("MOCK-")))
            .andReturn()
        val tree = objectMapper.readTree(detail.response.contentAsString)
        val total = tree.get("amount").asDouble()
        val share = tree.get("revenueShare")
        assertEquals(total, share.get("kiccAmount").asDouble() + share.get("countyAmount").asDouble(), 0.01)

        assertEquals("PAID", bookingStatus(exhibitor.accessToken, bookingId))
    }

    @Test
    fun pendingPaymentCompletesOnVerify() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)

        val paymentId = initiate(exhibitor.accessToken, bookingId, "mock", "2547-PENDING-WAIT")

        mockMvc.perform(get("/api/payments/$paymentId").headers(bearer(exhibitor.accessToken)))
            .andExpect(jsonPath("$.status").value("PENDING"))

        mockMvc.perform(post("/api/payments/$paymentId/verify").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("SUCCESS"))

        assertEquals("PAID", bookingStatus(exhibitor.accessToken, bookingId))
    }

    @Test
    fun failingPhoneIsDeclined() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)

        val paymentId = initiate(exhibitor.accessToken, bookingId, "mock", "254700000000")

        mockMvc.perform(get("/api/payments/$paymentId").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("FAILED"))

        assertEquals("PENDING", bookingStatus(exhibitor.accessToken, bookingId))
    }

    @Test
    fun adminRefundFreesCapacityAndRefundsBooking() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)
        val paymentId = initiate(exhibitor.accessToken, bookingId)

        mockMvc.perform(post("/api/payments/$paymentId/refund").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.status").value("REFUNDED"))

        assertEquals("REFUNDED", bookingStatus(exhibitor.accessToken, bookingId))

        mockMvc.perform(get("/api/data/booths/$boothId").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.bookedQuantity").value(0))
    }

    @Test
    fun exhibitorCannotRefund() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)
        val paymentId = initiate(exhibitor.accessToken, bookingId)

        mockMvc.perform(post("/api/payments/$paymentId/refund").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun exhibitorSeesOnlyOwnPayments() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)
        initiate(exhibitor.accessToken, bookingId)

        val res = mockMvc.perform(get("/api/payments").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isOk)
            .andReturn()
        val list = objectMapper.readTree(res.response.contentAsString)
        assertTrue(list.size() >= 1)
        list.forEach { assertEquals(4L, it.get("initiatedByUserId").asLong()) }
    }

    @Test
    fun unconfiguredRealProviderIsRejected() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken)
        val bookingId = bookBooth(exhibitor.accessToken, boothId)

        mockMvc.perform(
            post("/api/payments")
                .headers(bearer(exhibitor.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"bookingId":$bookingId,"method":"mpesa"}""")
        ).andExpect(status().isServiceUnavailable)

        mockMvc.perform(
            post("/api/payments")
                .headers(bearer(exhibitor.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"bookingId":$bookingId,"method":"stripe"}""")
        ).andExpect(status().isServiceUnavailable)
    }
}
