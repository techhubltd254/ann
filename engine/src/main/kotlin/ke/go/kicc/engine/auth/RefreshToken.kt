package ke.go.kicc.engine.auth

import jakarta.persistence.*
import ke.go.kicc.engine.user.User
import java.time.Instant

@Entity
@Table(name = "refresh_tokens")
class RefreshToken(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "user_id", nullable = false)
    var user: User? = null,

    @Column(nullable = false, unique = true, length = 64)
    var tokenHash: String = "",

    @Column(nullable = false)
    var expiresAt: Instant = Instant.now(),

    var revokedAt: Instant? = null
)
