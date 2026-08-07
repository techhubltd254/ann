package ke.go.kicc.engine.sync

import com.fasterxml.jackson.core.type.TypeReference
import com.fasterxml.jackson.databind.JsonNode
import com.fasterxml.jackson.databind.ObjectMapper
import jakarta.persistence.*
import ke.go.kicc.engine.data.*
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.rbac.ScopeResolver
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import java.nio.charset.StandardCharsets
import java.security.MessageDigest
import java.time.Instant
import java.util.*
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

@Entity
@Table(name = "sync_bundles")
class SyncBundle(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var countySlug: String = "",

    @Column(nullable = false)
    var version: Int = 1,

    @Column(nullable = false)
    var entityType: String = "all",

    @Column(nullable = false)
    var checksum: String = "",

    @Lob
    @Column(nullable = false)
    var payload: String = "",

    @Column(nullable = false)
    var createdByUserId: Long = 0,

    @Column(nullable = false)
    var createdAt: String = ""
)

interface SyncBundleRepository : JpaRepository<SyncBundle, Long> {
    fun findByCountySlugOrderByIdDesc(slug: String): List<SyncBundle>

    fun countByCountySlug(slug: String): Long
}

data class SyncRow(val entityType: String, val row: Map<String, Any?>)

data class PullResponse(
    val countySlug: String,
    val exportedAt: String,
    val rows: List<SyncRow>
)

data class PushResponse(
    val countySlug: String,
    val applied: Int,
    val skipped: Int,
    val version: Int
)

@Service
class SyncService(
    private val syncBundleRepository: SyncBundleRepository,
    private val countyRepository: CountyRepository,
    private val attractionRepository: AttractionRepository,
    private val hotelRepository: HotelRepository,
    private val farmRepository: FarmRepository,
    private val healthFacilityRepository: HealthFacilityRepository,
    private val institutionRepository: InstitutionRepository,
    private val transportRepository: TransportRepository,
    private val cultureRepository: CultureRepository,
    private val productRepository: ProductRepository,
    private val sectorEntityRepository: SectorEntityRepository,
    private val objectMapper: ObjectMapper,
    private val scopeResolver: ScopeResolver,
    private val auditLogService: ke.go.kicc.engine.audit.AuditLogService,
    @org.springframework.beans.factory.annotation.Value("\${kicc.sync.hmac-secret}") private val hmacSecret: String
) {

    private data class Target(
        val entityClass: Class<*>,
        val listAll: (Long) -> List<Any>,
        val findByLocalId: (String, Long) -> Any?,
        val findById: (Long) -> Any?,
        val save: (Any) -> Any
    )

    private val targets: Map<String, Target> = mapOf(
        "attractions" to Target(Attraction::class.java, { attractionRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> attractionRepository.findByLocalIdAndCountyId(l, c) }, { attractionRepository.findById(it).orElse(null) }, { attractionRepository.save(it as Attraction) }),
        "hotels" to Target(Hotel::class.java, { hotelRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> hotelRepository.findByLocalIdAndCountyId(l, c) }, { hotelRepository.findById(it).orElse(null) }, { hotelRepository.save(it as Hotel) }),
        "farms" to Target(Farm::class.java, { farmRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> farmRepository.findByLocalIdAndCountyId(l, c) }, { farmRepository.findById(it).orElse(null) }, { farmRepository.save(it as Farm) }),
        "health-facilities" to Target(HealthFacility::class.java, { healthFacilityRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> healthFacilityRepository.findByLocalIdAndCountyId(l, c) }, { healthFacilityRepository.findById(it).orElse(null) }, { healthFacilityRepository.save(it as HealthFacility) }),
        "institutions" to Target(Institution::class.java, { institutionRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> institutionRepository.findByLocalIdAndCountyId(l, c) }, { institutionRepository.findById(it).orElse(null) }, { institutionRepository.save(it as Institution) }),
        "transport" to Target(TransportService::class.java, { transportRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> transportRepository.findByLocalIdAndCountyId(l, c) }, { transportRepository.findById(it).orElse(null) }, { transportRepository.save(it as TransportService) }),
        "culture-sites" to Target(CultureSite::class.java, { cultureRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> cultureRepository.findByLocalIdAndCountyId(l, c) }, { cultureRepository.findById(it).orElse(null) }, { cultureRepository.save(it as CultureSite) }),
        "products" to Target(Product::class.java, { productRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> productRepository.findByLocalIdAndCountyId(l, c) }, { productRepository.findById(it).orElse(null) }, { productRepository.save(it as Product) }),
        "sector-entities" to Target(SectorEntity::class.java, { sectorEntityRepository.findAllByCountyIdOrderByIdAsc(it) }, { l, c -> sectorEntityRepository.findByLocalIdAndCountyId(l, c) }, { sectorEntityRepository.findById(it).orElse(null) }, { sectorEntityRepository.save(it as SectorEntity) })
    )

    fun resolveCounty(user: User, requested: String?): String {
        val access = scopeResolver.accessFor(user)
        if (user.tier == Role.COUNTY) {
            val own = user.countySlug
                ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "County admin has no county assigned")
            val requestedId = requested?.let { countyRepository.findBySlug(it.lowercase())?.id }
            if (requested != null && requestedId != null && requestedId in access.counties && requested.lowercase() != own) {
                return requested.lowercase()
            }
            return own
        }
        val slug = requested?.lowercase() ?: (user.countySlug ?: "kilifi")
        val county = countyRepository.findBySlug(slug)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found: $slug")
        if (access.counties.isNotEmpty() && county.id !in access.counties) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "County outside your access scope")
        }
        return slug
    }

    fun pull(user: User, countySlug: String?, entityType: String?, since: String?): PullResponse {
        val slug = resolveCounty(user, countySlug)
        val county = countyRepository.findBySlug(slug)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found: $slug")
        val rows = mutableListOf<SyncRow>()
        val cutoff = since?.let { try { Instant.parse(it) } catch (_: Exception) { null } }
        targets.forEach { (type, target) ->
            if (entityType.isNullOrBlank() || entityType == "all" || entityType == type) {
                val all = target.listAll(county.id!!)
                val filtered = if (cutoff != null) {
                    all.filter { row ->
                        try {
                            val updated = (row as? Attraction)?.updatedAt
                                ?: (row as? Hotel)?.updatedAt
                                ?: (row as? Farm)?.updatedAt
                                ?: (row as? HealthFacility)?.updatedAt
                                ?: (row as? Institution)?.updatedAt
                                ?: (row as? TransportService)?.updatedAt
                                ?: (row as? CultureSite)?.updatedAt
                                ?: (row as? Product)?.updatedAt
                                ?: (row as? SectorEntity)?.updatedAt
                            val synced = (row as? Attraction)?.syncedAt
                                ?: (row as? Hotel)?.syncedAt
                                ?: (row as? Farm)?.syncedAt
                                ?: (row as? HealthFacility)?.syncedAt
                                ?: (row as? Institution)?.syncedAt
                                ?: (row as? TransportService)?.syncedAt
                                ?: (row as? CultureSite)?.syncedAt
                                ?: (row as? Product)?.syncedAt
                                ?: (row as? SectorEntity)?.syncedAt
                            val ts = updated ?: synced ?: return@filter false
                            Instant.parse(ts).isAfter(cutoff)
                        } catch (_: Exception) { false }
                    }
                } else all
                filtered.forEach { rows.add(SyncRow(type, objectMapper.convertValue(it, object : TypeReference<Map<String, Any?>>() {}))) }
            }
        }
        auditLogService.record(
            actorId = user.id!!, action = "SYNC_PULL", targetUserId = null,
            detail = "county=$slug, entityType=${entityType ?: "all"}, rows=${rows.size}, delta=${cutoff != null}"
        )
        return PullResponse(slug, Instant.now().toString(), rows)
    }

    @Transactional
    fun push(user: User, rawBody: String, signature: String?): PushResponse {
        if (signature == null || !verify(rawBody, signature)) {
            throw ResponseStatusException(HttpStatus.UNAUTHORIZED, "Invalid sync signature")
        }
        val root = objectMapper.readTree(rawBody)
        val requested = root.get("countySlug")?.asText()?.lowercase()
        val slug = resolveCounty(user, requested)
        val rowsNode = root.get("rows") ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Missing rows")

        var applied = 0
        var skipped = 0
        rowsNode.forEach { item ->
            val type = item.get("entityType")?.asText()
                ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Row missing entityType")
            val target = targets[type] ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Unknown entityType $type")
            val rowNode = item.get("row") ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Row missing row")
            if (applyRow(target, rowNode, countyIdOf(slug))) applied++ else skipped++
        }
        val version = (syncBundleRepository.countByCountySlug(slug) + 1).toInt()
        syncBundleRepository.save(
            SyncBundle(
                countySlug = slug,
                version = version,
                entityType = "all",
                checksum = sha256(rawBody),
                payload = rawBody,
                createdByUserId = user.id!!,
                createdAt = Instant.now().toString()
            )
        )
        auditLogService.record(
            actorId = user.id!!, action = "SYNC_PUSH", targetUserId = null,
            detail = "county=$slug, applied=$applied, skipped=$skipped, version=$version"
        )
        return PushResponse(slug, applied, skipped, version)
    }

    private fun countyIdOf(slug: String): Long =
        countyRepository.findBySlug(slug)?.id ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found: $slug")

    private fun applyRow(target: Target, rowNode: JsonNode, countyId: Long): Boolean {
        val localId = rowNode.get("localId")?.asText()
        val serverId = rowNode.get("id")?.asLong()
        val incoming = objectMapper.convertValue(rowNode, target.entityClass) as CountyScoped
        incoming.countyId = countyId

        val existing = localId?.let { target.findByLocalId(it, countyId) }
            ?: serverId?.let { target.findById(it) }

        if (existing == null) {
            target.save(incoming)
            return true
        }
        val incomingHash = rowNode.get("contentHash")?.asText()
        val existingHash = readField(existing, "getContentHash") as? String
        if (incomingHash != null && incomingHash == existingHash) {
            return false
        }
        val incomingUpdated = rowNode.get("updatedAt")?.asText() ?: ""
        val existingUpdated = readField(existing, "getUpdatedAt") as? String ?: ""
        if (incomingUpdated >= existingUpdated) {
            setId(incoming, existing)
            target.save(incoming)
            return true
        }
        return false
    }

    private fun readField(o: Any, getterName: String): Any? = try {
        o.javaClass.getMethod(getterName).invoke(o)
    } catch (_: Exception) {
        null
    }

    private fun setId(incoming: Any, existing: Any) {
        try {
            val existingId = existing.javaClass.getMethod("getId").invoke(existing)
            incoming.javaClass.methods.firstOrNull { it.name == "setId" && it.parameterCount == 1 }
                ?.invoke(incoming, existingId)
        } catch (_: Exception) {
            // leave as-is; server id wins on insert
        }
    }

    fun bundles(user: User, countySlug: String?): List<SyncBundle> {
        val slug = resolveCounty(user, countySlug)
        return syncBundleRepository.findByCountySlugOrderByIdDesc(slug)
    }

    fun sign(data: ByteArray): String {
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(SecretKeySpec(hmacSecret.toByteArray(), "HmacSHA256"))
        return Base64.getEncoder().encodeToString(mac.doFinal(data))
    }

    private fun verify(data: String, signature: String): Boolean {
        val expected = sign(data.toByteArray(StandardCharsets.UTF_8))
        return MessageDigest.isEqual(expected.toByteArray(), signature.toByteArray())
    }

    private fun sha256(data: String): String {
        val digest = MessageDigest.getInstance("SHA-256").digest(data.toByteArray(StandardCharsets.UTF_8))
        return digest.joinToString("") { "%02x".format(it) }
    }
}

@RestController
@RequestMapping("/api/sync")
class SyncController(
    private val syncService: SyncService,
    private val objectMapper: ObjectMapper
) {

    @GetMapping("/pull")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun pull(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) countySlug: String?,
        @RequestParam(required = false) entityType: String?,
        @RequestParam(required = false) since: String?,
        response: jakarta.servlet.http.HttpServletResponse
    ): PullResponse {
        val pull = syncService.pull(user, countySlug, entityType, since)
        val body = objectMapper.writeValueAsString(pull)
        response.setHeader("X-Sync-Signature", syncService.sign(body.toByteArray(StandardCharsets.UTF_8)))
        return pull
    }

    @PostMapping("/push")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun push(
        @AuthenticationPrincipal user: User,
        @RequestBody(required = false) raw: String?,
        @RequestHeader(value = "X-Sync-Signature", required = false) signature: String?
    ): PushResponse = syncService.push(user, raw ?: "", signature)

    @GetMapping("/bundles")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun bundles(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) countySlug: String?
    ): List<SyncBundle> = syncService.bundles(user, countySlug)
}
