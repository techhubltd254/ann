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
class NotificationFlowTest {

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

    private fun notifications(token: String): List<Map<String, Any>> {
        val res = mockMvc.perform(get("/api/notifications").headers(bearer(token)))
            .andExpect(status().isOk)
            .andReturn()
        @Suppress("UNCHECKED_CAST")
        return objectMapper.readValue(res.response.contentAsString, List::class.java) as List<Map<String, Any>>
    }

    private fun unread(token: String): Long {
        val res = mockMvc.perform(get("/api/notifications/unread-count").headers(bearer(token)))
            .andExpect(status().isOk)
            .andReturn()
        return objectMapper.readTree(res.response.contentAsString).get("count").asLong()
    }

    private fun newUser(token: String, email: String): Long {
        val res = mockMvc.perform(
            post("/api/admin/users")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","fullName":"Notified User","tier":"EXHIBITOR","password":"Passwd123"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString).get("id").asLong()
    }

    private fun availableBoothId(token: String): Long {
        val create = mockMvc.perform(
            post("/api/data/booths")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"exhibitionId":1,"boothNumber":"NOTIF-${System.nanoTime() % 100000}","name":"Notification Booth","price":5000,"maxQuantity":5,"bookedQuantity":0,"status":"available"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(create.response.contentAsString).get("id").asLong()
    }

    private fun bookBooth(token: String, boothId: Long) {
        mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(token))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1}""")
        ).andExpect(status().isOk)
    }

    @Test
    fun newBookingNotifiesAdminsOnly() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val boothId = availableBoothId(admin.accessToken)

        mockMvc.perform(
            post("/api/bookings")
                .headers(bearer(exhibitor.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"boothId":$boothId,"quantity":1,"notes":"notification test"}""")
        ).andExpect(status().isOk)

        val adminNotes = notifications(admin.accessToken)
        assertTrue(adminNotes.any { it["type"] == "BOOKING_CREATED" && (it["title"] as String).contains("KICC-") })

        val exhibitorNotes = notifications(exhibitor.accessToken)
        assertTrue(exhibitorNotes.none { it["type"] == "BOOKING_CREATED" })
    }

    @Test
    fun unreadCountAndMarkRead() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val boothId = availableBoothId(admin.accessToken)

        bookBooth(exhibitor.accessToken, boothId)

        val before = unread(admin.accessToken)
        assertTrue(before > 0)

        mockMvc.perform(post("/api/notifications/read-all").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)

        assertEquals(0, unread(admin.accessToken))

        val notes = notifications(admin.accessToken)
        assertTrue(notes.all { it["readAt"] != null })
    }

    @Test
    fun delegationGrantAndRevokeNotifySubject() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val subjectId = newUser(admin.accessToken, "notified@kicc.go.ke")

        val grantRes = mockMvc.perform(
            post("/api/admin/delegations")
                .headers(bearer(admin.accessToken))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"subjectUserId":$subjectId,"role":"EXHIBITOR","scopeType":"ALL"}""")
        ).andExpect(status().isOk).andReturn()
        val delegationId = objectMapper.readTree(grantRes.response.contentAsString).get("id").asLong()

        val subject = login("notified@kicc.go.ke", "Passwd123")
        val grantNotes = notifications(subject.accessToken)
        assertTrue(grantNotes.any { it["type"] == "DELEGATION_GRANTED" })

        mockMvc.perform(
            post("/api/admin/delegations/$delegationId/revoke")
                .headers(bearer(admin.accessToken))
        ).andExpect(status().isOk)

        val revokeNotes = notifications(subject.accessToken)
        assertTrue(revokeNotes.any { it["type"] == "DELEGATION_REVOKED" })
    }

    @Test
    fun notificationsAreScopedToOwner() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")

        val adminNotes = notifications(admin.accessToken)
        val exhibitorNotes = notifications(exhibitor.accessToken)

        adminNotes.forEach { assertEquals(1L, (it["userId"] as Number).toLong()) }
        exhibitorNotes.forEach { assertEquals(4L, (it["userId"] as Number).toLong()) }
    }

    @Test
    fun cannotReadSomeoneElsesNotification() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        mockMvc.perform(
            post("/api/notifications/1/read").headers(bearer(exhibitor.accessToken))
        ).andExpect(status().isNotFound)
    }
}
