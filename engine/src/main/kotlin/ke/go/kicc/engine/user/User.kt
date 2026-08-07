package ke.go.kicc.engine.user

import jakarta.persistence.*
import ke.go.kicc.engine.rbac.Role

@Entity
@Table(name = "users")
class User(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false, unique = true)
    var email: String = "",

    @Column(nullable = false)
    var passwordHash: String = "",

    @Column(nullable = false)
    var fullName: String = "",

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    var tier: Role = Role.COUNTY,

    var countySlug: String? = null,

    var sectorId: Long? = null,

    var boothId: Long? = null,

    @Column(nullable = false)
    var active: Boolean = true,

    /** TOTP MFA — mandatory for KICC tier & financial privileges. */
    @Column(nullable = false)
    var mfaEnabled: Boolean = false,

    var totpSecret: String? = null,

    // ---- Dual-schema bridge: Laravel's snake/plain columns on the shared users table.
    // Laravel writes name/password; the engine writes fullName/passwordHash.
    // Keep both sides in sync so accounts work across both systems (bcrypt-compatible).
    @Column(name = "name", nullable = false)
    var legacyName: String = "",

    @Column(name = "password", nullable = false)
    var legacyPassword: String = ""
) {
    @PostLoad
    fun adoptLegacyColumns() {
        if (fullName.isBlank()) fullName = legacyName
        if (passwordHash.isBlank()) passwordHash = legacyPassword
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyName = fullName
        legacyPassword = passwordHash
    }
}
