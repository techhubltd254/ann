package ke.go.kicc.engine.rbac

import jakarta.persistence.*
import ke.go.kicc.engine.user.User
import java.time.Instant

@Entity
@Table(name = "delegations")
class Delegation(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "subject_user_id", nullable = false)
    var subject: User? = null,

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    var role: Role = Role.NATIONAL,

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    var scopeType: ScopeType = ScopeType.ALL,

    var scopeValue: String? = null,

    var expiresAt: Instant? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "granted_by", nullable = false)
    var grantedBy: User? = null,

    @Column(nullable = false)
    var grantedAt: Instant = Instant.now(),

    var revokedAt: Instant? = null,

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "revoked_by")
    var revokedBy: User? = null,

    @ElementCollection(fetch = FetchType.EAGER)
    @CollectionTable(name = "delegation_extra_privileges", joinColumns = [JoinColumn(name = "delegation_id")])
    @Enumerated(EnumType.STRING)
    @Column(name = "privilege")
    var extraPrivileges: MutableSet<Privilege> = mutableSetOf()
)
