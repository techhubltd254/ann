package ke.go.kicc.engine.audit

import org.springframework.data.jpa.repository.JpaRepository

interface AuditLogRepository : JpaRepository<AuditLog, Long> {
    fun findByTargetUserIdOrderByCreatedAtDesc(targetUserId: Long): List<AuditLog>

    fun findAllByOrderByCreatedAtDesc(): List<AuditLog>

    fun findAllByOrderByIdAsc(): List<AuditLog>

    fun findTopByOrderByIdDesc(): AuditLog?
}
