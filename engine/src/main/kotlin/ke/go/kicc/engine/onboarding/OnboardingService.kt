package ke.go.kicc.engine.onboarding

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.http.HttpStatus
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.server.ResponseStatusException
import java.security.SecureRandom

@Service
class OnboardingService(
    private val applicantRepository: ApplicantRepository,
    private val userRepository: UserRepository,
    private val auditLogService: AuditLogService,
    private val passwordEncoder: PasswordEncoder,
    private val objectMapper: ObjectMapper
) {
    private val random = SecureRandom()
    private val chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789"

    @Transactional
    fun apply(req: ApplyRequest): ApplyResponse {
        val email = req.email.lowercase()
        val existing = applicantRepository.findByEmailIgnoreCase(email)
        if (existing != null) {
            return ApplyResponse(
                applicantId = existing.id!!,
                route = existing.route,
                score = existing.score,
                message = if (existing.status == ApplicantStatus.PENDING) {
                    "Your application is already under review"
                } else {
                    "Your application has been ${existing.status.name.lowercase()}"
                }
            )
        }
        val score = Qualification.score(req.answers)
        val route = Qualification.route(score)
        val applicant = applicantRepository.save(
            Applicant(
                orgName = req.orgName,
                contactName = req.contactName,
                email = email,
                phone = req.phone,
                countySlug = req.countySlug,
                score = score,
                route = route,
                answers = objectMapper.writeValueAsString(req.answers)
            )
        )
        auditLogService.record(
            actorId = 0,
            action = "ONBOARDING_APPLY",
            targetUserId = null,
            detail = "org=${req.orgName}, email=$email, score=$score, route=$route"
        )
        return ApplyResponse(
            applicantId = applicant.id!!,
            route = route,
            score = score,
            message = when (route) {
                ApplicantRoute.OWN_SERVER -> "Qualified for a dedicated county server — an administrator will contact you"
                ApplicantRoute.TEMPLATE -> "Qualified for the shared portfolio template — your profile is queued for review"
            }
        )
    }

    fun list(): List<ApplicantView> = applicantRepository.findAllByOrderByCreatedAtDesc().map { ApplicantView.from(it, objectMapper) }

    @Transactional
    fun approve(actor: User, id: Long): ReviewResponse {
        val applicant = find(id)
        if (applicant.status != ApplicantStatus.PENDING) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "Application already ${applicant.status.name.lowercase()}")
        }
        applicant.status = ApplicantStatus.APPROVED
        applicant.reviewedAt = java.time.Instant.now()
        applicant.reviewedByUserId = actor.id

        val password = randomPassword()
        val user = userRepository.save(
            User(
                email = applicant.email,
                fullName = applicant.contactName,
                passwordHash = passwordEncoder.encode(password),
                tier = ke.go.kicc.engine.rbac.Role.EXHIBITOR,
                countySlug = applicant.countySlug,
                active = true
            )
        )
        applicant.provisionedUserId = user.id
        auditLogService.record(
            actorId = actor.id!!,
            action = "ONBOARDING_APPROVED",
            targetUserId = user.id,
            detail = "org=${applicant.orgName}, route=${applicant.route}"
        )
        return ReviewResponse(applicant.id!!, ApplicantStatus.APPROVED, user.email, password)
    }

    @Transactional
    fun reject(actor: User, id: Long): ApplicantView {
        val applicant = find(id)
        if (applicant.status != ApplicantStatus.PENDING) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "Application already ${applicant.status.name.lowercase()}")
        }
        applicant.status = ApplicantStatus.REJECTED
        applicant.reviewedAt = java.time.Instant.now()
        applicant.reviewedByUserId = actor.id
        auditLogService.record(
            actorId = actor.id!!,
            action = "ONBOARDING_REJECTED",
            targetUserId = null,
            detail = "org=${applicant.orgName}"
        )
        return ApplicantView.from(applicant, objectMapper)
    }

    private fun find(id: Long): Applicant =
        applicantRepository.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Application not found") }

    private fun randomPassword(): String = buildString(12) { repeat(12) { append(chars[random.nextInt(chars.length)]) } }
}
