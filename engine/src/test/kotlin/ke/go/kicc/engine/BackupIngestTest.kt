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
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status
import java.nio.file.Files
import java.nio.file.Path

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class BackupIngestTest {

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

    private fun backupBytes() = byteArrayOf(0x50, 0x4b, 0x03, 0x04, 1, 2, 3, 4)

    @Test
    fun ingestRejectsWrongKey() {
        // Key-guarded only — county servers authenticate with the key, not a session.
        mockMvc.perform(
            post("/api/ops/backups/ingest")
                .contentType(MediaType.APPLICATION_OCTET_STREAM)
                .content(backupBytes())
                .header("X-Backup-Key", "wrong-key")
                .header("X-Backup-Source", "muranga")
                .header("X-Backup-Filename", "20260801-daily.zip")
        ).andExpect(status().isUnauthorized)
    }

    @Test
    fun ingestStoresOffHostFileWithAudit() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val res = mockMvc.perform(
            post("/api/ops/backups/ingest")
                .contentType(MediaType.APPLICATION_OCTET_STREAM)
                .content(backupBytes())
                .header("X-Backup-Key", "test-ingest-key")
                .header("X-Backup-Source", "muranga")
                .header("X-Backup-Filename", "20260801-daily.zip")
        ).andExpect(status().isOk).andReturn()
        val fileName = objectMapper.readTree(res.response.contentAsString).get("file").asText()
        assertTrue(fileName.startsWith("muranga-"), "source-prefixed filename: $fileName")

        val stored = Path.of("data/backups/offhost").resolve(fileName)
        assertTrue(Files.exists(stored), "off-host file must exist at $stored")
        assertEquals(8, Files.size(stored))
        Files.deleteIfExists(stored)

        mockMvc.perform(get("/api/admin/audit-logs").headers(bearer(admin.accessToken)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$[?(@.action=='BACKUP_INGESTED')]").exists())
    }

    @Test
    fun missingKeyIsRejected() {
        // Key-only auth: no key at all must be rejected (the endpoint is not session-gated).
        mockMvc.perform(
            post("/api/ops/backups/ingest")
                .contentType(MediaType.APPLICATION_OCTET_STREAM)
                .content(backupBytes())
                .header("X-Backup-Source", "muranga")
        ).andExpect(status().isUnauthorized)
    }
}
