package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.auth.TokenResponse
import ke.go.kicc.engine.payment.PaymentService
import org.junit.jupiter.api.Assertions.assertEquals
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
class SettlementFlowTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    @Autowired
    lateinit var paymentService: PaymentService

    private fun login(email: String, password: String): TokenResponse {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readValue(res.response.contentAsString, TokenResponse::class.java)
    }

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun createBooth(token: String, price: Int): Long {
        val res = mockMvc.perform(
            post("/api/data/booths")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"exhibitionId":1,"boothNumber":"STL-${System.nanoTime() % 100000}","name":"Settle Booth","price":$price,"maxQuantity":5,"bookedQuantity":0,"status":"available"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString).get("id").asLong()
    }

    private fun bookAndPay(exhibitorToken: String, boothId: Long): Pair<Long, String> {
        val bookingRes = mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(exhibitorToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1,"notes":"settlement test"}""")
        ).andExpect(status().isOk).andReturn()
        val booking = objectMapper.readTree(bookingRes.response.contentAsString)
        val bookingId = booking.get("id").asLong()
        val reference = booking.get("bookingReference").asText()

        val payRes = mockMvc.perform(
            post("/api/payments")
                .headers(bearer(exhibitorToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"bookingId":$bookingId,"method":"mock","phone":"254722000222"}""")
        ).andExpect(status().isOk).andReturn()
        assertEquals("SUCCESS", objectMapper.readTree(payRes.response.contentAsString).get("status").asText())
        return bookingId to reference
    }

    private fun runSettlements() = paymentService.runDailySettlements()

    private fun latestSettlement(token: String): Map<String, Any?> {
        val res = mockMvc.perform(get("/api/payments/settlements").headers(bearer(token)))
            .andExpect(status().isOk).andReturn()
        val list = objectMapper.readTree(res.response.contentAsString)
        val tree = list.first()
            ?: throw AssertionError("no settlements created")
        return mapOf("id" to tree.get("id").asLong(), "status" to tree.get("status").asText())
    }

    @Test
    fun settlementLifecycleApprovedThenPaid() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken, 14777)
        bookAndPay(exhibitor.accessToken, boothId)
        runSettlements()

        val settlement = latestSettlement(admin.accessToken)
        assertEquals("PENDING", settlement["status"])

        val approved = mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/approve").headers(bearer(admin.accessToken))
        ).andExpect(status().isOk).andReturn()
        val approvedTree = objectMapper.readTree(approved.response.contentAsString)
        assertEquals("APPROVED", approvedTree.get("status").asText())
        assertNotNull(approvedTree.get("approvedAt").asText())

        val paid = mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/pay")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"payoutRef":"PAYOUT-2026-0001"}""")
        ).andExpect(status().isOk).andReturn()
        val paidTree = objectMapper.readTree(paid.response.contentAsString)
        assertEquals("PAID", paidTree.get("status").asText())
        assertEquals("PAYOUT-2026-0001", paidTree.get("payoutRef").asText())
        assertNotNull(paidTree.get("paidAt").asText())
    }

    @Test
    fun payRequiresApprovalFirst() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken, 13777)
        bookAndPay(exhibitor.accessToken, boothId)
        runSettlements()

        val settlement = latestSettlement(admin.accessToken)

        mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/pay")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"payoutRef":"PAYOUT-EARLY"}""")
        ).andExpect(status().isBadRequest)

        mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/approve").headers(bearer(admin.accessToken))
        ).andExpect(status().isOk)

        mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/approve").headers(bearer(admin.accessToken))
        ).andExpect(status().isBadRequest)
    }

    @Test
    fun failedSettlementCannotBePaid() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken, 12777)
        bookAndPay(exhibitor.accessToken, boothId)
        runSettlements()

        val settlement = latestSettlement(admin.accessToken)

        val failed = mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/fail")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"reason":"Bank details incorrect"}""")
        ).andExpect(status().isOk).andReturn()
        assertEquals("FAILED", objectMapper.readTree(failed.response.contentAsString).get("status").asText())

        mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/pay")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"payoutRef":"PAYOUT-NO"}""")
        ).andExpect(status().isBadRequest)
    }

    @Test
    fun exhibitorCannotManageSettlements() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val boothId = createBooth(admin.accessToken, 11777)
        bookAndPay(exhibitor.accessToken, boothId)
        runSettlements()

        val settlement = latestSettlement(admin.accessToken)

        mockMvc.perform(get("/api/payments/settlements").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isForbidden)
        mockMvc.perform(
            post("/api/payments/settlements/${settlement["id"]}/approve").headers(bearer(exhibitor.accessToken))
        ).andExpect(status().isForbidden)
        mockMvc.perform(get("/api/payments/statements").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun statementsAreCountyScopedWithRevenueShare() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val county = login("county@kicc.go.ke", "county@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")

        val boothId = createBooth(admin.accessToken, 15777)
        val (_, reference) = bookAndPay(exhibitor.accessToken, boothId)

        val kiccStatement = mockMvc.perform(
            get("/api/payments/statements?countySlug=trans-nzoia").headers(bearer(admin.accessToken))
        ).andExpect(status().isOk).andReturn()
        val kiccTree = objectMapper.readTree(kiccStatement.response.contentAsString)
        assertTrue(kiccTree.size() == 1, "exactly one county in list")
        val tz = kiccTree[0]
        assertEquals("trans-nzoia", tz.get("countySlug").asText())
        val gross = tz.get("grossAmount").asDouble()
        assertTrue(gross > 0)
        assertEquals(gross * 0.8, tz.get("kiccAmount").asDouble(), 0.01)
        assertEquals(gross * 0.2, tz.get("countyAmount").asDouble(), 0.01)
        assertTrue(tz.get("paymentCount").asInt() >= 1)
        assertTrue(
            tz.get("payments").any { it.get("bookingReference").asText() == reference },
            "statement must include the new booking $reference"
        )

        val kilifiList = mockMvc.perform(get("/api/payments/statements").headers(bearer(county.accessToken)))
            .andExpect(status().isOk).andReturn()
        val kilifiTree = objectMapper.readTree(kilifiList.response.contentAsString)
        assertTrue(kilifiTree.size() == 0, "kilifi admin sees no statements (payment is in trans-nzoia)")

        mockMvc.perform(
            get("/api/payments/statements?countySlug=trans-nzoia").headers(bearer(county.accessToken))
        ).andExpect(status().isForbidden)
    }
}
