package ke.go.kicc.engine.onboarding

import jakarta.persistence.*

enum class ApplicantRoute {
    OWN_SERVER,
    TEMPLATE
}

enum class ApplicantStatus {
    PENDING,
    APPROVED,
    REJECTED
}

@Entity
@Table(name = "applicants")
class Applicant(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var orgName: String = "",

    @Column(nullable = false)
    var contactName: String = "",

    @Column(nullable = false)
    var email: String = "",

    var phone: String? = null,

    var countySlug: String? = null,

    @Column(nullable = false)
    var score: Int = 0,

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    var route: ApplicantRoute = ApplicantRoute.TEMPLATE,

    @Column(length = 4000)
    var answers: String? = null,

    @Enumerated(EnumType.STRING)
    @Column(nullable = false)
    var status: ApplicantStatus = ApplicantStatus.PENDING,

    @Column(nullable = false)
    var createdAt: java.time.Instant = java.time.Instant.now(),

    var reviewedAt: java.time.Instant? = null,

    var reviewedByUserId: Long? = null,

    var provisionedUserId: Long? = null
)
