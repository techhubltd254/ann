package ke.go.kicc.engine.auth

import org.springframework.beans.factory.annotation.Value
import org.springframework.http.HttpStatus
import org.springframework.stereotype.Service
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import java.security.SecureRandom
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

/**
 * TOTP (RFC 6238) MFA — mandatory for KICC tier and any account holding
 * PAYMENTS_MANAGE / DELEGATE. Self-contained HMAC-SHA1; no external dependency.
 *
 * Flow:
 *  1. POST /api/auth/mfa/enroll   → returns secret + otpauth:// URI (scan into Authenticator)
 *  2. POST /api/auth/mfa/enable   → verify first code → mfaEnabled=true
 *  3. login with MFA enabled      → 200 { mfaRequired:true, mfaToken } — no session yet
 *  4. POST /api/auth/mfa/verify   → { mfaToken, code } → full session tokens
 */
@Service
class TotpService(
    @Value("\${kicc.mfa.issuer:KICC Admin}") private val issuer: String,
) {
    private val random = SecureRandom()

    fun generateSecret(): String {
        val bytes = ByteArray(20)
        random.nextBytes(bytes)
        return Base32.encodeToString(bytes).trimEnd('=')
    }

    fun otpauthUri(account: String, secret: String): String =
        "otpauth://totp/${issuer}:${account}?secret=${secret}&issuer=${issuer}&digits=6&period=30"

    /** Current TOTP code with ±1 step tolerance (30 s window each side). */
    fun verify(secret: String, code: String): Boolean {
        val clean = code.trim()
        if (!clean.matches(Regex("\\d{6}"))) return false
        val step = System.currentTimeMillis() / 1000 / 30
        return (-1..1).any { totp(secret, step + it) == clean }
    }

    private fun totp(secret: String, timeStep: Long): String {
        val key = Base32.decode(pad(secret))
        val msg = timeStep.toString(16).padStart(16, '0').chunked(2).map { it.toInt(16).toByte() }.toByteArray()
        val hash = Mac.getInstance("HmacSHA1").apply { init(SecretKeySpec(key, "HmacSHA1")) }.doFinal(msg)
        val offset = hash[hash.size - 1].toInt() and 0x0f
        val binary = ((hash[offset].toInt() and 0x7f) shl 24) or
            ((hash[offset + 1].toInt() and 0xff) shl 16) or
            ((hash[offset + 2].toInt() and 0xff) shl 8) or
            (hash[offset + 3].toInt() and 0xff)
        return (binary % 1_000_000).toString().padStart(6, '0')
    }

    private fun pad(s: String) = s.trimEnd('=').let { it + "=".repeat((8 - it.length % 8) % 8) }
}

// Minimal RFC4648 Base32 (no dependency)
private object Base32 {
    private val ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567".toCharArray()
    private val LOOKUP = ALPHABET.withIndex().associate { it.value to it.index }

    fun encodeToString(data: ByteArray): String {
        val sb = StringBuilder()
        var buffer = 0; var bits = 0
        for (b in data) {
            buffer = (buffer shl 8) or (b.toInt() and 0xFF); bits += 8
            while (bits >= 5) { sb.append(ALPHABET[(buffer shr (bits - 5)) and 31]); bits -= 5 }
        }
        if (bits > 0) sb.append(ALPHABET[(buffer shl (5 - bits)) and 31])
        return sb.toString()
    }

    fun decode(s: String): ByteArray {
        var buffer = 0; var bits = 0
        val out = mutableListOf<Byte>()
        for (c in s.uppercase()) {
            val v = LOOKUP[c] ?: continue
            buffer = (buffer shl 5) or v; bits += 5
            if (bits >= 8) { out.add(((buffer shr (bits - 8)) and 0xFF).toByte()); bits -= 8 }
        }
        return out.toByteArray()
    }
}
