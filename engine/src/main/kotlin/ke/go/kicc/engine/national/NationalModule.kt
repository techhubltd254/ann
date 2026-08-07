package ke.go.kicc.engine.national

import jakarta.persistence.*
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.stereotype.Service
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import java.time.LocalDateTime
import java.time.format.DateTimeFormatter

// ---------------------------------------------------------------------------
// NationalModule — the NATIONAL government admin's domain.
//
// The national tier is independent of the county sector structure: it manages
// national-level government content (ministries, agencies and their public
// profiles) and views national aggregates. It does NOT touch county data,
// payments, marketplace or users (see RoleDefaults + ScopeResolver).
// ---------------------------------------------------------------------------

@Entity
@Table(name = "ministries")
class Ministry(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false, unique = true)
    var slug: String = "",

    @Column(nullable = false, length = 10)
    var code: String = "",

    var logo: String? = null,
    var color: String? = null,

    @Column(columnDefinition = "text")
    var description: String? = null,

    var website: String? = null,

    @Column(name = "contact_email")
    var contactEmail: String? = null,

    @Column(name = "contact_phone")
    var contactPhone: String? = null,

    @Column(name = "is_active", nullable = false)
    var isActive: Boolean = true,

    @Column(name = "created_at")
    var createdAt: String? = null,

    @Column(name = "updated_at")
    var updatedAt: String? = null
)

@Entity
@Table(name = "agencies")
class Agency(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(name = "ministry_id", nullable = false)
    var ministryId: Long = 0,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false, unique = true)
    var slug: String = "",

    @Column(nullable = false, length = 10)
    var code: String = "",

    var logo: String? = null,

    @Column(columnDefinition = "text")
    var description: String? = null,

    var website: String? = null,

    @Column(name = "contact_email")
    var contactEmail: String? = null,

    @Column(name = "is_active", nullable = false)
    var isActive: Boolean = true,

    @Column(name = "created_at")
    var createdAt: String? = null,

    @Column(name = "updated_at")
    var updatedAt: String? = null
)

interface MinistryRepository : JpaRepository<Ministry, Long> {
    fun findBySlug(slug: String): Ministry?
    fun existsBySlug(slug: String): Boolean
    fun findAllByOrderByNameAsc(): List<Ministry>
}

interface AgencyRepository : JpaRepository<Agency, Long> {
    fun findBySlug(slug: String): Agency?
    fun existsBySlug(slug: String): Boolean
    fun findByMinistryIdOrderByNameAsc(ministryId: Long): List<Agency>
    fun findAllByOrderByNameAsc(): List<Agency>
}

@Service
class NationalContentService(
    private val ministries: MinistryRepository,
    private val agencies: AgencyRepository,
    private val audit: AuditLogService,
) {
    private val ts = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")
    private fun now() = LocalDateTime.now().format(ts)

    /** Only KICC + NATIONAL may manage national government content. */
    private fun requireNationalWriter(user: User) {
        if (user.tier != Role.KICC && user.tier != Role.NATIONAL) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "National content is managed by KICC or the national government admin")
        }
    }

    // ---- validation -------------------------------------------------------

    private fun validateBody(body: Map<String, Any?>, requireName: Boolean): String {
        val name = (body["name"] as? String)?.trim().orEmpty()
        if (requireName && name.length < 3) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "name is required (min 3 chars)")
        }
        (body["contact_email"] as? String)?.takeIf { it.isNotBlank() }?.let {
            if (!it.matches(Regex("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$")))
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "contact_email is not a valid email")
        }
        (body["website"] as? String)?.takeIf { it.isNotBlank() }?.let {
            if (!it.matches(Regex("^https?://.+")))
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "website must start with http:// or https://")
        }
        (body["color"] as? String)?.takeIf { it.isNotBlank() }?.let {
            if (!it.matches(Regex("^#[0-9a-fA-F]{6}$")))
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "color must be #RRGGBB")
        }
        (body["code"] as? String)?.takeIf { it.isNotBlank() }?.let {
            if (!it.matches(Regex("^[A-Z0-9-]{2,10}$")))
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "code must be 2-10 chars: A-Z, 0-9, dash")
        }
        return name
    }

    private fun slugOf(name: String): String =
        name.lowercase().replace(Regex("[^a-z0-9]+"), "-").trim('-').take(240)
            .ifBlank { throw ResponseStatusException(HttpStatus.BAD_REQUEST, "cannot derive a slug from name") }

    private fun uniqueSlug(base: String, exists: (String) -> Boolean): String {
        var candidate = base
        var n = 2
        while (exists(candidate)) {
            candidate = "$base-${n++}"
            if (n > 50) throw ResponseStatusException(HttpStatus.CONFLICT, "slug collision for '$base'")
        }
        return candidate
    }

    // ---- ministries -------------------------------------------------------

    fun listMinistries(): List<Ministry> = ministries.findAllByOrderByNameAsc()

    fun createMinistry(user: User, body: Map<String, Any?>): Ministry {
        requireNationalWriter(user)
        val name = validateBody(body, requireName = true)
        val m = Ministry().apply {
            this.name = name
            slug = uniqueSlug((body["slug"] as? String)?.trim().orEmpty().ifBlank { slugOf(name) }) { ministries.existsBySlug(it) }
            code = ((body["code"] as? String)?.trim()?.uppercase().orEmpty().ifBlank {
                "M" + slugOf(name).take(4).uppercase().replace("-", "")
            })
            logo = (body["logo"] as? String)?.trim()?.ifBlank { null }
            color = (body["color"] as? String)?.trim()?.ifBlank { null }
            description = (body["description"] as? String)?.trim()?.ifBlank { null }
            website = (body["website"] as? String)?.trim()?.ifBlank { null }
            contactEmail = (body["contact_email"] as? String)?.trim()?.ifBlank { null }
            contactPhone = (body["contact_phone"] as? String)?.trim()?.ifBlank { null }
            isActive = body["is_active"] as? Boolean ?: true
            createdAt = now()
            updatedAt = now()
        }
        val saved = ministries.save(m)
        audit.record(user.id ?: 0, "NATIONAL_MINISTRY_CREATE", null, "id=${saved.id} slug=${saved.slug} name=${saved.name}")
        return saved
    }

    fun updateMinistry(user: User, id: Long, body: Map<String, Any?>): Ministry {
        requireNationalWriter(user)
        validateBody(body, requireName = false)
        val m = ministries.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "ministry not found") }
        (body["name"] as? String)?.trim()?.takeIf { it.isNotBlank() }?.let { m.name = it }
        (body["slug"] as? String)?.trim()?.takeIf { it.isNotBlank() }?.let { newSlug ->
            ministries.findBySlug(newSlug)?.let { if (it.id != m.id) throw ResponseStatusException(HttpStatus.CONFLICT, "slug already in use") }
            m.slug = newSlug
        }
        (body["code"] as? String)?.trim()?.uppercase()?.takeIf { it.isNotBlank() }?.let { m.code = it }
        if (body.containsKey("logo")) m.logo = (body["logo"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("color")) m.color = (body["color"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("description")) m.description = (body["description"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("website")) m.website = (body["website"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("contact_email")) m.contactEmail = (body["contact_email"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("contact_phone")) m.contactPhone = (body["contact_phone"] as? String)?.trim()?.ifBlank { null }
        (body["is_active"] as? Boolean)?.let { m.isActive = it }
        m.updatedAt = now()
        val saved = ministries.save(m)
        audit.record(user.id ?: 0, "NATIONAL_MINISTRY_UPDATE", null, "id=${saved.id} slug=${saved.slug}")
        return saved
    }

    fun deleteMinistry(user: User, id: Long, hard: Boolean) {
        requireNationalWriter(user)
        val m = ministries.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "ministry not found") }
        if (hard) {
            if (user.tier != Role.KICC) throw ResponseStatusException(HttpStatus.FORBIDDEN, "hard delete is KICC-only")
            if (agencies.findByMinistryIdOrderByNameAsc(id).isNotEmpty())
                throw ResponseStatusException(HttpStatus.CONFLICT, "ministry still has agencies — reassign or delete them first")
            ministries.delete(m)
            audit.record(user.id ?: 0, "NATIONAL_MINISTRY_DELETE_HARD", null, "id=$id slug=${m.slug}")
        } else {
            m.isActive = false
            m.updatedAt = now()
            ministries.save(m)
            audit.record(user.id ?: 0, "NATIONAL_MINISTRY_DELETE_SOFT", null, "id=$id slug=${m.slug}")
        }
    }

    // ---- agencies ---------------------------------------------------------

    fun listAgencies(ministryId: Long?): List<Agency> =
        ministryId?.let { agencies.findByMinistryIdOrderByNameAsc(it) } ?: agencies.findAllByOrderByNameAsc()

    fun createAgency(user: User, body: Map<String, Any?>): Agency {
        requireNationalWriter(user)
        val name = validateBody(body, requireName = true)
        val ministryId = (body["ministry_id"] as? Number)?.toLong()
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "ministry_id is required")
        if (!ministries.existsById(ministryId))
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "ministry_id=$ministryId does not exist")
        val a = Agency().apply {
            this.ministryId = ministryId
            this.name = name
            slug = uniqueSlug((body["slug"] as? String)?.trim().orEmpty().ifBlank { slugOf(name) }) { agencies.existsBySlug(it) }
            code = ((body["code"] as? String)?.trim()?.uppercase().orEmpty().ifBlank {
                "A" + slugOf(name).take(4).uppercase().replace("-", "")
            })
            logo = (body["logo"] as? String)?.trim()?.ifBlank { null }
            description = (body["description"] as? String)?.trim()?.ifBlank { null }
            website = (body["website"] as? String)?.trim()?.ifBlank { null }
            contactEmail = (body["contact_email"] as? String)?.trim()?.ifBlank { null }
            isActive = body["is_active"] as? Boolean ?: true
            createdAt = now()
            updatedAt = now()
        }
        val saved = agencies.save(a)
        audit.record(user.id ?: 0, "NATIONAL_AGENCY_CREATE", null, "id=${saved.id} ministry=${saved.ministryId} slug=${saved.slug}")
        return saved
    }

    fun updateAgency(user: User, id: Long, body: Map<String, Any?>): Agency {
        requireNationalWriter(user)
        validateBody(body, requireName = false)
        val a = agencies.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "agency not found") }
        (body["ministry_id"] as? Number)?.toLong()?.let {
            if (!ministries.existsById(it)) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "ministry_id=$it does not exist")
            a.ministryId = it
        }
        (body["name"] as? String)?.trim()?.takeIf { it.isNotBlank() }?.let { a.name = it }
        (body["slug"] as? String)?.trim()?.takeIf { it.isNotBlank() }?.let { newSlug ->
            agencies.findBySlug(newSlug)?.let { if (it.id != a.id) throw ResponseStatusException(HttpStatus.CONFLICT, "slug already in use") }
            a.slug = newSlug
        }
        (body["code"] as? String)?.trim()?.uppercase()?.takeIf { it.isNotBlank() }?.let { a.code = it }
        if (body.containsKey("logo")) a.logo = (body["logo"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("description")) a.description = (body["description"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("website")) a.website = (body["website"] as? String)?.trim()?.ifBlank { null }
        if (body.containsKey("contact_email")) a.contactEmail = (body["contact_email"] as? String)?.trim()?.ifBlank { null }
        (body["is_active"] as? Boolean)?.let { a.isActive = it }
        a.updatedAt = now()
        val saved = agencies.save(a)
        audit.record(user.id ?: 0, "NATIONAL_AGENCY_UPDATE", null, "id=${saved.id} slug=${saved.slug}")
        return saved
    }

    fun deleteAgency(user: User, id: Long, hard: Boolean) {
        requireNationalWriter(user)
        val a = agencies.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "agency not found") }
        if (hard) {
            if (user.tier != Role.KICC) throw ResponseStatusException(HttpStatus.FORBIDDEN, "hard delete is KICC-only")
            agencies.delete(a)
            audit.record(user.id ?: 0, "NATIONAL_AGENCY_DELETE_HARD", null, "id=$id slug=${a.slug}")
        } else {
            a.isActive = false
            a.updatedAt = now()
            agencies.save(a)
            audit.record(user.id ?: 0, "NATIONAL_AGENCY_DELETE_SOFT", null, "id=$id slug=${a.slug}")
        }
    }
}

@RestController
@RequestMapping("/api/national")
class NationalContentController(private val service: NationalContentService) {

    @GetMapping("/ministries")
    @org.springframework.security.access.prepost.PreAuthorize("isAuthenticated()")
    fun ministries(): List<Ministry> = service.listMinistries()

    @GetMapping("/agencies")
    @org.springframework.security.access.prepost.PreAuthorize("isAuthenticated()")
    fun agencies(@RequestParam(required = false) ministryId: Long?): List<Agency> = service.listAgencies(ministryId)

    @PostMapping("/ministries")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun createMinistry(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @RequestBody body: Map<String, Any?>
    ): Ministry = service.createMinistry(user, body)

    @PutMapping("/ministries/{id}")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateMinistry(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Ministry = service.updateMinistry(user, id, body)

    @DeleteMapping("/ministries/{id}")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun deleteMinistry(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestParam(defaultValue = "false") hard: Boolean
    ): Map<String, String> {
        service.deleteMinistry(user, id, hard)
        return mapOf("deleted" to if (hard) "hard" else "soft")
    }

    @PostMapping("/agencies")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun createAgency(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @RequestBody body: Map<String, Any?>
    ): Agency = service.createAgency(user, body)

    @PutMapping("/agencies/{id}")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateAgency(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Agency = service.updateAgency(user, id, body)

    @DeleteMapping("/agencies/{id}")
    @org.springframework.security.access.prepost.PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun deleteAgency(
        @org.springframework.security.core.annotation.AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestParam(defaultValue = "false") hard: Boolean
    ): Map<String, String> {
        service.deleteAgency(user, id, hard)
        return mapOf("deleted" to if (hard) "hard" else "soft")
    }
}
