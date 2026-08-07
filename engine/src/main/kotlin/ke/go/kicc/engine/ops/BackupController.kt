package ke.go.kicc.engine.ops

import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.user.User
import org.springframework.beans.factory.annotation.Value
import org.springframework.http.HttpStatus
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.PostMapping
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestBody
import org.springframework.web.bind.annotation.RequestHeader
import org.springframework.web.bind.annotation.RestController
import org.springframework.web.multipart.MultipartFile
import org.springframework.web.server.ResponseStatusException
import java.lang.management.ManagementFactory
import java.time.Instant

@RestController
class BackupController(
    private val backupService: BackupService,
    private val auditLogService: AuditLogService,
    @Value("\${kicc.backup.ingest-key:}") private val ingestKey: String
) {

    @PostMapping("/api/admin/backups")
    @PreAuthorize("hasAuthority('DELEGATE')")
    fun createBackup(@AuthenticationPrincipal actor: User): Map<String, String> {
        val target = try {
            backupService.manual()
        } catch (e: Exception) {
            throw ResponseStatusException(HttpStatus.INTERNAL_SERVER_ERROR, "Backup failed: ${e.message}")
        }
        auditLogService.record(actorId = actor.id!!, action = "BACKUP_CREATED", targetUserId = null, detail = "file=${target.fileName}")
        return mapOf("file" to target.fileName.toString(), "path" to target.toString())
    }

    /**
     * Off-host backup sink receiver: county engines push nightly backups here.
     * Guarded by the shared X-Backup-Key header only — county servers authenticate
     * with the key, not a user session. Rate-limited + 50 MB cap.
     */
    @PostMapping("/api/ops/backups/ingest")
    fun ingestBackup(
        @RequestHeader("X-Backup-Key") key: String?,
        @RequestHeader(value = "X-Backup-Source", defaultValue = "unknown") source: String,
        @RequestHeader(value = "X-Backup-Filename", defaultValue = "backup.zip") filename: String,
        @RequestBody bytes: ByteArray
    ): Map<String, String> {
        if (ingestKey.isBlank()) {
            throw ResponseStatusException(HttpStatus.SERVICE_UNAVAILABLE, "Backup ingest is not configured")
        }
        if (key == null || key != ingestKey) {
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid backup key")
        }
        if (bytes.size > 50 * 1024 * 1024) {
            throw ResponseStatusException(HttpStatus.PAYLOAD_TOO_LARGE, "Backup exceeds 50 MB cap")
        }
        val target = backupService.ingest(source, filename, bytes)
        auditLogService.record(
            actorId = 0, action = "BACKUP_INGESTED", targetUserId = null,
            detail = "file=${target.fileName}, source=$source, bytes=${bytes.size}"
        )
        return mapOf("file" to target.fileName.toString())
    }

    @GetMapping("/api/ops/heartbeat")
    fun heartbeat(): Map<String, Any> {
        val runtime = Runtime.getRuntime()
        val uptime = ManagementFactory.getRuntimeMXBean().uptime
        return mapOf(
            "status" to "ok",
            "serverTime" to Instant.now().toString(),
            "uptime" to uptime,
            "freeMemory" to runtime.freeMemory(),
            "totalMemory" to runtime.totalMemory(),
            "maxMemory" to runtime.maxMemory(),
            "cores" to runtime.availableProcessors()
        )
    }
}
