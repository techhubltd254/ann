package ke.go.kicc.engine.media

import jakarta.persistence.*
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.http.MediaType
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Service
import org.springframework.web.bind.annotation.*
import org.springframework.web.multipart.MultipartFile
import org.springframework.web.server.ResponseStatusException
import java.awt.image.BufferedImage
import java.io.ByteArrayInputStream
import java.io.ByteArrayOutputStream
import java.nio.file.Files
import java.nio.file.Path
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.*
import javax.imageio.ImageIO
import jakarta.servlet.http.HttpServletResponse

@Entity
@Table(name = "media_assets")
class MediaAsset(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false, unique = true)
    var storageKey: String = "",

    @Column(nullable = false)
    var originalName: String = "",

    @Column(nullable = false)
    var contentType: String = "",

    @Column(nullable = false)
    var sizeBytes: Long = 0,

    var width: Int? = null,
    var height: Int? = null,
    var thumbKey: String? = null,

    @Column(nullable = false)
    var uploadedByUserId: Long = 0,

    @Column(nullable = false)
    var createdAt: String = ""
)

interface MediaAssetRepository : JpaRepository<MediaAsset, Long> {
    fun findByStorageKey(key: String): MediaAsset?

    fun findByStorageKeyOrThumbKey(key: String, thumbKey: String): MediaAsset?

    fun findAllByOrderByIdDesc(): List<MediaAsset>
}

interface StorageService {
    fun save(key: String, bytes: ByteArray): String

    fun load(key: String): ByteArray?

    fun delete(key: String)
}

@Service
class LocalStorageService(
    root: Path = Path.of(System.getenv("KICC_MEDIA_DIR") ?: "./data/media")
) : StorageService {

    private val root = root.toAbsolutePath().normalize()

    init {
        Files.createDirectories(root)
    }

    override fun save(key: String, bytes: ByteArray): String {
        val target = resolve(key)
        Files.createDirectories(target.parent)
        Files.write(target, bytes)
        return key
    }

    override fun load(key: String): ByteArray? {
        val target = resolve(key)
        return if (Files.exists(target)) Files.readAllBytes(target) else null
    }

    override fun delete(key: String) {
        Files.deleteIfExists(resolve(key))
    }

    private fun resolve(key: String): Path {
        if (!key.matches(Regex("[a-z0-9]{2}/[a-z0-9_\\-]+\\.(jpg|jpeg|png|webp|gif|pdf)"))) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid media key")
        }
        val resolved = root.resolve(key).normalize()
        if (!resolved.startsWith(root)) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid media key")
        }
        return resolved
    }
}

data class MediaView(
    val id: Long,
    val key: String,
    val thumbKey: String?,
    val contentType: String,
    val sizeBytes: Long,
    val width: Int?,
    val height: Int?,
    val url: String,
    val thumbUrl: String?,
    val uploadedByUserId: Long,
    val createdAt: String
) {
    companion object {
        fun from(a: MediaAsset) = MediaView(
            id = a.id!!,
            key = a.storageKey,
            thumbKey = a.thumbKey,
            contentType = a.contentType,
            sizeBytes = a.sizeBytes,
            width = a.width,
            height = a.height,
            url = "/api/media/${a.storageKey}",
            thumbUrl = a.thumbKey?.let { "/api/media/$it" },
            uploadedByUserId = a.uploadedByUserId,
            createdAt = a.createdAt
        )
    }
}

@Service
class MediaService(
    private val mediaAssetRepository: MediaAssetRepository,
    private val storage: StorageService,
    private val auditLogService: AuditLogService
) {

    private val allowedTypes = setOf(
        "image/jpeg", "image/png", "image/webp", "image/gif", "application/pdf"
    )

    private val maxBytes = 10L * 1024 * 1024

    private val extByType = mapOf(
        "image/jpeg" to "jpg",
        "image/png" to "png",
        "image/webp" to "webp",
        "image/gif" to "gif",
        "application/pdf" to "pdf"
    )

    fun upload(user: User, file: MultipartFile): MediaView {
        val contentType = file.contentType?.lowercase() ?: "application/octet-stream"
        if (contentType !in allowedTypes) {
            throw ResponseStatusException(HttpStatus.UNSUPPORTED_MEDIA_TYPE, "File type not allowed: $contentType")
        }
        if (file.size > maxBytes) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "File exceeds 10 MB limit")
        }
        val bytes = file.bytes
        if (bytes.isEmpty()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Empty file")
        }

        val now = Instant.now().atZone(ZoneOffset.UTC)
        val uuid = UUID.randomUUID().toString()
        val ext = extByType.getValue(contentType)
        val key = "${now.format(DateTimeFormatter.ofPattern("MM"))}/${uuid}.$ext"
        storage.save(key, bytes)

        var thumbKey: String? = null
        var width: Int? = null
        var height: Int? = null
        if (contentType.startsWith("image/")) {
            try {
                val image = ImageIO.read(ByteArrayInputStream(bytes))
                if (image != null) {
                    width = image.width
                    height = image.height
                    val thumb = scale(image, 400)
                    val out = ByteArrayOutputStream()
                    ImageIO.write(thumb, "jpg", out)
                    thumbKey = "${now.format(DateTimeFormatter.ofPattern("MM"))}/${uuid}_thumb.jpg"
                    storage.save(thumbKey, out.toByteArray())
                }
            } catch (ignored: Exception) {
                // thumbnail optional
            }
        }

        val asset = mediaAssetRepository.save(
            MediaAsset(
                storageKey = key,
                originalName = file.originalFilename ?: "upload",
                contentType = contentType,
                sizeBytes = file.size,
                width = width,
                height = height,
                thumbKey = thumbKey,
                uploadedByUserId = user.id!!,
                createdAt = Instant.now().toString()
            )
        )
        auditLogService.record(user.id!!, "MEDIA_UPLOADED", null, "key=$key, type=$contentType, bytes=${file.size}")
        return MediaView.from(asset)
    }

    fun serve(key: String, response: HttpServletResponse) {
        val asset = mediaAssetRepository.findByStorageKeyOrThumbKey(key, key)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "Media not found")
        val bytes = storage.load(key)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "Media not found")
        response.contentType = if (key == asset.thumbKey) "image/jpeg" else asset.contentType
        response.setContentLength(bytes.size.toInt())
        response.setHeader("Cache-Control", "public, max-age=86400")
        response.outputStream.write(bytes)
    }

    fun delete(user: User, key: String) {
        val asset = mediaAssetRepository.findByStorageKey(key)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "Media not found")
        storage.delete(key)
        asset.thumbKey?.let { storage.delete(it) }
        mediaAssetRepository.delete(asset)
        auditLogService.record(user.id!!, "MEDIA_DELETED", null, "key=$key")
    }

    fun list(): List<MediaView> = mediaAssetRepository.findAllByOrderByIdDesc().take(200).map { MediaView.from(it) }

    private fun scale(image: BufferedImage, max: Int): BufferedImage {
        val ratio = max.toDouble() / maxOf(image.width, image.height)
        if (ratio >= 1.0) return image
        val w = (image.width * ratio).toInt().coerceAtLeast(1)
        val h = (image.height * ratio).toInt().coerceAtLeast(1)
        val out = BufferedImage(w, h, BufferedImage.TYPE_INT_RGB)
        val g = out.createGraphics()
        g.drawImage(image, 0, 0, w, h, null)
        g.dispose()
        return out
    }
}

@RestController
@RequestMapping("/api/media")
class MediaController(
    private val mediaService: MediaService
) {

    @PostMapping(consumes = [MediaType.MULTIPART_FORM_DATA_VALUE])
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun upload(@AuthenticationPrincipal user: User, @RequestParam("file") file: MultipartFile): MediaView =
        mediaService.upload(user, file)

    @GetMapping
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun list(): List<MediaView> = mediaService.list()

    @GetMapping("/{*key}")
    fun serve(@PathVariable key: String, response: HttpServletResponse) = mediaService.serve(key.removePrefix("/"), response)

    @DeleteMapping("/{*key}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun delete(@AuthenticationPrincipal user: User, @PathVariable key: String, response: HttpServletResponse) {
        mediaService.delete(user, key.removePrefix("/"))
        response.status = HttpServletResponse.SC_NO_CONTENT
    }
}
