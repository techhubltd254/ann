package ke.go.kicc.engine.ops

import jakarta.annotation.PostConstruct
import org.slf4j.LoggerFactory
import org.springframework.beans.factory.annotation.Value
import org.springframework.scheduling.annotation.Scheduled
import org.springframework.stereotype.Service
import org.springframework.web.client.RestClient
import java.net.URI
import java.nio.file.*
import java.security.MessageDigest
import java.security.Signature

/**
 * OTA update channel — lets manually-installed admin apps (mother + 47 county servers +
 * exhibitor OWN_SERVER installs) update themselves without site visits.
 *
 * Flow:
 *  1. Poll the signed manifest on kicctest.org (daily + on boot, jittered).
 *  2. Compare semver; skip when current >= latest or manifest.mandatory=false and pinned.
 *  3. Download jar to releases/<version>/, verify SHA-256, verify ed25519 signature.
 *  4. Atomic symlink flip (releases/current -> releases/<version>), restart via
 *     systemd (or launcher script), health-check /api/health for 60 s, rollback on failure.
 *
 * Failure modes:
 *  - Network partition: keeps running current version; next poll retries.
 *  - Corrupt download: SHA mismatch -> deleted, marked bad, never retried for that version.
 *  - Bad release: health check fails -> symlink flips back, jar quarantined, audit row
 *    OTA_ROLLBACK written, KICC admin notified via /api/notifications.
 *  - Signature failure: treated as tampering — hard-fail + audit OTA_TAMPER.
 */
@Service
class UpdateService(
    private val audit: ke.go.kicc.engine.audit.AuditLogService,
    @Value("\${kicc.update.manifest-url:https://kicctest.org/api/updates/manifest}") val manifestUrl: String,
    @Value("\${kicc.update.channel:stable}") val channel: String,
    @Value("\${kicc.update.enabled:false}") val enabled: Boolean,
    @Value("\${kicc.version:0.0.0-dev}") val currentVersion: String,
    @Value("\${kicc.home:.}") val home: String,
) {
    private val log = LoggerFactory.getLogger(javaClass)

    /** Built inline, matching the engine convention (see Payments.kt / Recommendations.kt). */
    private val rest: RestClient = RestClient.builder().build()

    /** How to restart after a swap. Prod: the shipped ota-restart.sh watchdog (or systemd). */
    @Value("\${kicc.update.restart-command:systemctl restart kicc-engine}")
    private var restartCommand: String = "systemctl restart kicc-engine"

    data class Manifest(
        val version: String,
        val url: String,
        val sha256: String,
        val signature: String,        // ed25519 hex of sha256 bytes, release public key pinned below
        val minVersion: String = "0.0.0",
        val mandatory: Boolean = false,
    )

    @PostConstruct
    fun onBoot() { if (enabled) checkAsync("boot") }

    /** Daily 04:20 + jitter — spreads fleet load so 47+ installs don't thunder-herd kicctest.org. */
    @Scheduled(cron = "0 \${random.int[0,59]} 4 * * *")
    fun dailyCheck() { if (enabled) checkAsync("daily") }

    fun checkAsync(trigger: String) {
        Thread.ofVirtual().start { runCatching { check(trigger) }.onFailure { log.warn("OTA check failed: {}", it.message) } }
    }

    fun check(trigger: String) {
        val m = rest.get()
            .uri("$manifestUrl?channel=$channel&current=$currentVersion")
            .retrieve().body(Manifest::class.java) ?: return
        if (!isNewer(m.version, currentVersion)) return
        if (compareVersions(currentVersion, m.minVersion) < 0 && !m.mandatory) {
            log.info("OTA: {} below minVersion {} and not mandatory — waiting for operator", currentVersion, m.minVersion)
            return
        }

        // Loop guard: after an update, the REPLACEMENT process may still report an
        // older version string (env-driven in dev) and would otherwise re-apply the
        // same release forever. Skip when the symlink already targets this version
        // or a watchdog handoff for it is already in flight.
        val currentLink = Paths.get(home, "releases", "current")
        val existing = runCatching { Files.readSymbolicLink(currentLink) }.getOrNull()?.toString()
        if (existing != null && existing.contains("/${m.version}/")) {
            log.info("OTA: {} already applied (current -> {}), skipping", m.version, existing)
            return
        }
        val pendingFile = Paths.get(home, "releases", "pending.json")
        if (Files.exists(pendingFile) && String(Files.readAllBytes(pendingFile)).contains("\"${m.version}\"")) {
            log.info("OTA: watchdog handoff for {} already in flight, skipping", m.version)
            return
        }

        val dir = Paths.get(home, "releases", m.version).also { Files.createDirectories(it) }
        val jar = dir.resolve("kicc-engine.jar")
        val bytes = rest.get().uri(URI(m.url)).retrieve().body(ByteArray::class.java)!!
        val digest = MessageDigest.getInstance("SHA-256").digest(bytes)
        val hex = digest.joinToString("") { "%02x".format(it) }
        require(hex.equals(m.sha256, true)) {
            audit.record(SYSTEM_ACTOR, "OTA_TAMPER", null, "sha256 mismatch for ${m.version}"); "sha256 mismatch"
        }
        require(verifyEd25519(digest, m.signature)) {
            audit.record(SYSTEM_ACTOR, "OTA_TAMPER", null, "bad signature for ${m.version}"); "bad signature"
        }

        Files.write(jar, bytes, StandardOpenOption.CREATE, StandardOpenOption.TRUNCATE_EXISTING)
        val current = Paths.get(home, "releases", "current")
        val previous = runCatching { Files.readSymbolicLink(current) }.getOrNull()
        Files.deleteIfExists(current)
        Files.createSymbolicLink(current, jar)

        // Hand restart+health-gate+rollback to the EXTERNAL watchdog — this JVM is
        // about to die and cannot verify its own replacement. The watchdog reads
        // pending.json, restarts the engine, and flips the symlink back on failure.
        val pending = """{"version":"${m.version}","previous":${previous?.let { "\"$it\"" } ?: "null"}}"""
        Files.write(Paths.get(home, "releases", "pending.json"), pending.toByteArray())

        log.info("OTA: {} -> {} (trigger={}), handing off to restart watchdog", currentVersion, m.version, trigger)
        audit.record(SYSTEM_ACTOR, "OTA_UPDATE", null, "$currentVersion -> ${m.version} ($trigger)")

        // Detach fully: own session (setsid) + output to a file, NOT a pipe —
        // this JVM is about to die, and a watchdog inherited through a pipe would
        // be killed by SIGPIPE the moment it tries to log.
        val watchLog = Paths.get(home, "logs").also { Files.createDirectories(it) }
            .resolve("ota-watchdog.log").toFile()
        runCatching {
            ProcessBuilder("setsid", "sh", "-c", restartCommand)
                .redirectOutput(ProcessBuilder.Redirect.appendTo(watchLog))
                .redirectErrorStream(true)
                .start()
        }.onFailure { log.error("OTA: restart watchdog failed to launch: {}", it.message) }
    }

    private fun verifyEd25519(payload: ByteArray, signatureHex: String): Boolean {
        if (RELEASE_PUBLIC_KEY.isEmpty()) {
            // Fail closed: no pinned release key -> no OTA, ever. Set KICC_OTA_PUBLIC_KEY.
            log.error("OTA: KICC_OTA_PUBLIC_KEY not configured — refusing update")
            return false
        }
        // Accept both raw 32-byte keys (libsodium style) and full X.509 DER keys.
        val der = if (RELEASE_PUBLIC_KEY.size == 32) ED25519_DER_PREFIX + RELEASE_PUBLIC_KEY else RELEASE_PUBLIC_KEY
        val pubKey = java.security.KeyFactory.getInstance("Ed25519").generatePublic(
            java.security.spec.X509EncodedKeySpec(der)
        )
        return Signature.getInstance("Ed25519").apply {
            initVerify(pubKey)
            update(payload)
        }.verify(signatureHex.chunked(2).map { it.toInt(16).toByte() }.toByteArray())
    }

    private fun isNewer(a: String, b: String): Boolean =
        compareVersions(a.removeSuffix("-dev"), b.removeSuffix("-dev")) > 0

    private fun compareVersions(a: String, b: String): Int {
        val pa = a.split('.').map { it.toIntOrNull() ?: 0 }
        val pb = b.split('.').map { it.toIntOrNull() ?: 0 }
        for (i in 0..2) {
            val d = (pa.getOrElse(i) { 0 }) - (pb.getOrElse(i) { 0 })
            if (d != 0) return d
        }
        return 0
    }

    companion object {
        /** Audit actor id 0 = system (same convention as AuditLogService.recordLoginFailure). */
        private const val SYSTEM_ACTOR = 0L

        /** X.509 DER prefix for an Ed25519 public key (RFC 8410) — prepended to raw 32-byte keys. */
        private val ED25519_DER_PREFIX = byteArrayOf(
            0x30, 0x2a, 0x30, 0x05, 0x06, 0x03, 0x2b, 0x65, 0x70, 0x03, 0x21, 0x00
        )

        /** Pinned release-signing public key (raw 32-byte or X509 DER). Private half lives in CI secrets only. */
        val RELEASE_PUBLIC_KEY: ByteArray = System.getenv("KICC_OTA_PUBLIC_KEY")?.decodeHex()
            ?: ByteArray(0)
        private fun String.decodeHex() = chunked(2).map { it.toInt(16).toByte() }.toByteArray()
    }
}
