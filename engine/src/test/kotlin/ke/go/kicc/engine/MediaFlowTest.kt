package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.auth.TokenResponse
import org.junit.jupiter.api.Assertions.assertEquals
import org.junit.jupiter.api.Assertions.assertNotNull
import org.junit.jupiter.api.Test
import org.springframework.beans.factory.annotation.Autowired
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc
import org.springframework.boot.test.context.SpringBootTest
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.mock.web.MockMultipartFile
import org.springframework.test.context.ActiveProfiles
import org.springframework.test.web.servlet.MockMvc
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.delete
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.get
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.multipart
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.post
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.*
import java.awt.image.BufferedImage
import java.io.ByteArrayOutputStream
import javax.imageio.ImageIO

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class MediaFlowTest {

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

    private fun pngBytes(w: Int = 1200, h: Int = 800): ByteArray {
        val image = BufferedImage(w, h, BufferedImage.TYPE_INT_RGB)
        val out = ByteArrayOutputStream()
        ImageIO.write(image, "png", out)
        return out.toByteArray()
    }

    private fun upload(token: String, file: MockMultipartFile): Map<String, Any> {
        val res = mockMvc.perform(
            multipart("/api/media").file(file).header(HttpHeaders.AUTHORIZATION, "Bearer $token")
        )
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.key").exists())
            .andReturn()
        @Suppress("UNCHECKED_CAST")
        return objectMapper.readValue(res.response.contentAsString, Map::class.java) as Map<String, Any>
    }

    @Test
    fun uploadServesAndDeletesImageWithThumbnail() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        val media = upload(
            admin.accessToken,
            MockMultipartFile("file", "venue.png", "image/png", pngBytes())
        )

        val key = media["key"] as String
        val thumbKey = media["thumbKey"] as String
        assertEquals(1200, media["width"])
        assertEquals(800, media["height"])
        assertNotNull(thumbKey)

        mockMvc.perform(get("/api/media/$key"))
            .andExpect(status().isOk)
            .andExpect(header().string("Content-Type", "image/png"))
            .andExpect(content().contentType("image/png"))

        mockMvc.perform(get("/api/media/$thumbKey"))
            .andExpect(status().isOk)
            .andExpect(header().string("Content-Type", "image/jpeg"))

        mockMvc.perform(delete("/api/media/$key").headers(bearer(admin.accessToken)))
            .andExpect(status().isNoContent)

        mockMvc.perform(get("/api/media/$key"))
            .andExpect(status().isNotFound)
    }

    @Test
    fun uploadRequiresAuth() {
        mockMvc.perform(
            multipart("/api/media").file(MockMultipartFile("file", "a.png", "image/png", pngBytes()))
        ).andExpect(status().isForbidden)
    }

    @Test
    fun disallowedFileTypeIsRejected() {
        val admin = login("admin@kicc.go.ke", "Admin@2026")
        mockMvc.perform(
            multipart("/api/media")
                .file(MockMultipartFile("file", "malware.exe", "application/x-msdownload", byteArrayOf(0x4D, 0x5A)))
                .header(HttpHeaders.AUTHORIZATION, "Bearer ${admin.accessToken}")
        ).andExpect(status().isUnsupportedMediaType)
    }

    @Test
    fun everyTierCanUploadButNoOneCanDeleteOthers() {
        val county = login("county@kicc.go.ke", "county@2026")
        val media = upload(
            county.accessToken,
            MockMultipartFile("file", "county.jpg", "image/jpeg", pngBytes())
        )
        val key = media["key"] as String

        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        mockMvc.perform(delete("/api/media/$key").headers(bearer(exhibitor.accessToken)))
            .andExpect(status().isNoContent)
    }

    @Test
    fun unknownMediaReturns404() {
        mockMvc.perform(get("/api/media/ab/does-not-exist.png"))
            .andExpect(status().isNotFound)
    }
}
