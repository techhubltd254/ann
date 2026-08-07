package ke.go.kicc.engine.ops

import org.slf4j.LoggerFactory
import org.springframework.beans.factory.annotation.Value
import org.springframework.boot.CommandLineRunner
import org.springframework.core.Ordered
import org.springframework.core.annotation.Order
import org.springframework.jdbc.core.JdbcTemplate
import org.springframework.scheduling.annotation.Scheduled
import org.springframework.stereotype.Service
import org.springframework.web.client.RestClient
import org.springframework.web.multipart.MultipartFile
import java.net.InetAddress
import java.nio.file.Files
import java.nio.file.Path
import java.time.LocalDate
import java.time.format.DateTimeFormatter

@Service
@Order(Ordered.LOWEST_PRECEDENCE)
class BackupService(
    private val jdbc: JdbcTemplate,
    @Value("\${kicc.backup.sink-url:}") private val sinkUrl: String,
    @Value("\${kicc.backup.sink-key:}") private val sinkKey: String,
    @Value("\${kicc.backup.sink-source:}") private val sinkSource: String
) : CommandLineRunner {

    private val log = LoggerFactory.getLogger(javaClass)
    private val dir: Path = Path.of(System.getenv("KICC_BACKUP_DIR") ?: "./data/backups")
    private val source: String = sinkSource.ifBlank {
        runCatching { InetAddress.getLocalHost().hostName }.getOrDefault("unknown")
    }

    override fun run(vararg args: String?) {
        try {
            pushToSink(backup("startup"))
        } catch (e: Exception) {
            log.warn("startup backup failed: {}", e.message)
        }
    }

    @Scheduled(cron = "0 5 2 * * *")
    fun daily() = pushToSink(backup("daily"))

    fun manual(): Path = pushToSink(backup("manual"))

    private fun backup(kind: String): Path {
        // H2's BACKUP TO only works on H2 file databases. On MySQL/TiDB (prod
        // mother), local file backups are skipped — TiDB Cloud PITR snapshots
        // cover the mother; county servers (H2) back up and push here.
        if (!isH2()) {
            log.info("backup '{}': non-H2 datasource — relying on TiDB Cloud snapshots (PITR)", kind)
            return dir
        }
        Files.createDirectories(dir)
        val name = "${LocalDate.now().format(DateTimeFormatter.BASIC_ISO_DATE)}-${kind}.zip"
        val target = dir.resolve(name)
        jdbc.execute("BACKUP TO '${target.toAbsolutePath()}'")
        val all = Files.list(dir).use { it.sorted().toList() }
        if (all.size > 7) Files.deleteIfExists(all.first())
        log.info("H2 backup {} -> {} ({} files kept)", kind, target, all.size)
        return target
    }

    private fun isH2(): Boolean = runCatching {
        jdbc.queryForObject("SELECT 1", Int::class.java)
        jdbc.dataSource?.connection?.metaData?.databaseProductName?.contains("H2", true) == true
    }.getOrDefault(false)

    /**
     * Off-host copy: push the backup zip to the configured sink (mother
     * engine's ingest endpoint or any HTTP receiver) guarded by a shared key.
     * Failure is logged, never fatal — local backup still exists.
     */
    private fun pushToSink(target: Path): Path {
        if (sinkUrl.isBlank() || sinkKey.isBlank()) return target
        if (Files.isDirectory(target)) return target // non-H2 datasource: nothing local to push
        return try {
            val client = RestClient.builder().build()
            val body = client.post()
                .uri(sinkUrl)
                .header("X-Backup-Key", sinkKey)
                .header("X-Backup-Source", source)
                .header("X-Backup-Filename", target.fileName.toString())
                .contentType(org.springframework.http.MediaType.APPLICATION_OCTET_STREAM)
                .body(Files.readAllBytes(target))
                .retrieve()
                .toBodilessEntity()
            log.info("Backup {} pushed to sink {} ({})", target.fileName, sinkUrl, body.statusCode)
            target
        } catch (e: Exception) {
            log.warn("Backup sink push failed for {}: {}", target.fileName, e.message)
            target
        }
    }

    fun ingest(sourceName: String, filename: String, bytes: ByteArray): Path {
        Files.createDirectories(dir.resolve("offhost"))
        val safeSource = sourceName.replace(Regex("[^A-Za-z0-9._-]"), "_").take(60)
        val safeFile = filename.replace(Regex("[^A-Za-z0-9._-]"), "_").take(80).ifBlank { "backup.zip" }
        val target = dir.resolve("offhost").resolve("$safeSource-$safeFile")
        Files.write(target, bytes)
        log.info("Ingested off-host backup {} from {} ({} bytes)", target.fileName, safeSource, bytes.size)
        return target
    }
}
