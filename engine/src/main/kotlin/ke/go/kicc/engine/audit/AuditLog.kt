package ke.go.kicc.engine.audit

import jakarta.persistence.*
import java.time.Instant

@Entity
@Table(name = "audit_logs")
class AuditLog(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var actorUserId: Long = 0,

    @Column(nullable = false)
    var action: String = "",

    var targetUserId: Long? = null,

    @Column(length = 4000)
    var detail: String? = null,

    @Column(nullable = false)
    var createdAt: Instant = Instant.now(),

    var prevHash: String? = null,

    var hash: String? = null
)
