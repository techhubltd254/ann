package ke.go.kicc.engine.onboarding

import org.springframework.data.jpa.repository.JpaRepository

interface ApplicantRepository : JpaRepository<Applicant, Long> {
    fun findByEmailIgnoreCase(email: String): Applicant?
    fun findAllByOrderByCreatedAtDesc(): List<Applicant>
}
