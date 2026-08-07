package ke.go.kicc.engine.onboarding

import com.fasterxml.jackson.databind.ObjectMapper
import jakarta.validation.constraints.Email
import jakarta.validation.constraints.NotBlank
import jakarta.validation.constraints.Size

data class ApplyRequest(
    @field:NotBlank
    val orgName: String,

    @field:NotBlank
    val contactName: String,

    @field:Email
    @field:NotBlank
    val email: String,

    val phone: String? = null,

    val countySlug: String? = null,

    val answers: Map<String, String> = emptyMap()
)

data class ApplicantView(
    val id: Long,
    val orgName: String,
    val contactName: String,
    val email: String,
    val phone: String?,
    val countySlug: String?,
    val score: Int,
    val route: ApplicantRoute,
    val answers: Map<String, String>,
    val status: ApplicantStatus,
    val createdAt: String,
    val reviewedAt: String?,
    val provisionedUserId: Long?
) {
    companion object {
        fun from(a: Applicant, mapper: ObjectMapper): ApplicantView {
            val answers = try {
                mapper.readValue(a.answers ?: "{}", object : com.fasterxml.jackson.core.type.TypeReference<Map<String, String>>() {})
            } catch (_: Exception) {
                emptyMap()
            }
            return ApplicantView(
                id = a.id!!,
                orgName = a.orgName,
                contactName = a.contactName,
                email = a.email,
                phone = a.phone,
                countySlug = a.countySlug,
                score = a.score,
                route = a.route,
                answers = answers,
                status = a.status,
                createdAt = a.createdAt.toString(),
                reviewedAt = a.reviewedAt?.toString(),
                provisionedUserId = a.provisionedUserId
            )
        }
    }
}

data class ApplyResponse(
    val applicantId: Long,
    val route: ApplicantRoute,
    val score: Int,
    val message: String
)

data class ReviewResponse(
    val applicantId: Long,
    val status: ApplicantStatus,
    val provisionedEmail: String?,
    val provisionedPassword: String?
)

object Qualification {
    private val rules: Map<String, Map<String, Int>> = mapOf(
        "employees" to mapOf("<10" to 0, "10-50" to 1, "50-200" to 2, "200+" to 3),
        "eventsPerYear" to mapOf("0" to 0, "1-3" to 1, "4+" to 2),
        "listings" to mapOf("1-10" to 0, "11-50" to 1, "50+" to 2),
        "itStaff" to mapOf("none" to 0, "part-time" to 1, "dedicated" to 2),
        "connectivity" to mapOf("poor" to 0, "average" to 1, "good" to 2)
    )

    fun score(answers: Map<String, String>): Int =
        rules.entries.sumOf { (key, levels) -> levels[answers[key]] ?: 0 }

    fun route(score: Int): ApplicantRoute =
        if (score >= 6) ApplicantRoute.OWN_SERVER else ApplicantRoute.TEMPLATE
}
