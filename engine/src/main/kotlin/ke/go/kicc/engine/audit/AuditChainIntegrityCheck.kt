package ke.go.kicc.engine.audit

import org.slf4j.LoggerFactory
import org.springframework.boot.CommandLineRunner
import org.springframework.core.Ordered
import org.springframework.core.annotation.Order
import org.springframework.stereotype.Component

/**
 * Startup integrity check for the audit hash chain: logs a warning when the
 * chain is broken (tampered rows) so operators notice before relying on it.
 */
@Component
@Order(Ordered.LOWEST_PRECEDENCE - 1)
class AuditChainIntegrityCheck(private val auditLogService: AuditLogService) : CommandLineRunner {

    private val log = LoggerFactory.getLogger(javaClass)

    override fun run(vararg args: String?) {
        val status = auditLogService.verify()
        if (status.valid) {
            log.info(
                "Audit hash chain verified: {} hashed rows checked ({} legacy rows before the chain, tail hash {})",
                status.checked, status.legacyRows, status.tailHash?.take(12) ?: "none"
            )
        } else {
            log.error(
                "AUDIT CHAIN BROKEN: hash mismatch at audit_logs row {} — possible tampering; {} rows checked",
                status.brokenAtId, status.checked
            )
        }
    }
}
