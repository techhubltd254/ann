package ke.go.kicc.engine.data

import com.fasterxml.jackson.databind.ObjectMapper
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.rbac.PermissionEvaluator
import ke.go.kicc.engine.rbac.Privilege
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.rbac.ScopeResolver
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.server.ResponseStatusException
import java.time.Instant

data class ScopedHandle(
    val repo: CountyScopedRepository<*>,
    val entityClass: Class<out CountyScoped>
)

@Service
class CountyDataService(
    private val countyRepository: CountyRepository,
    private val sectorRepository: SectorRepository,
    private val countySectorRepository: CountySectorRepository,
    private val attractionRepository: AttractionRepository,
    private val hotelRepository: HotelRepository,
    private val farmRepository: FarmRepository,
    private val healthRepository: HealthFacilityRepository,
    private val institutionRepository: InstitutionRepository,
    private val transportRepository: TransportRepository,
    private val cultureRepository: CultureRepository,
    private val productRepository: ProductRepository,
    private val productCategoryRepository: ProductCategoryRepository,
    private val exhibitionRepository: ExhibitionRepository,
    private val venueRepository: VenueRepository,
    private val boothRepository: BoothRepository,
    private val sectorEntityRepository: SectorEntityRepository,
    private val scopeResolver: ScopeResolver,
    private val objectMapper: ObjectMapper,
    private val auditLogService: AuditLogService,
    private val permissionEvaluator: PermissionEvaluator,
    private val userRepository: ke.go.kicc.engine.user.UserRepository
) {

    private val scopedRepos: Map<String, ScopedHandle> = mapOf(
        "attractions" to ScopedHandle(attractionRepository, Attraction::class.java),
        "hotels" to ScopedHandle(hotelRepository, Hotel::class.java),
        "farms" to ScopedHandle(farmRepository, Farm::class.java),
        "health-facilities" to ScopedHandle(healthRepository, HealthFacility::class.java),
        "institutions" to ScopedHandle(institutionRepository, Institution::class.java),
        "transport" to ScopedHandle(transportRepository, TransportService::class.java),
        "culture-sites" to ScopedHandle(cultureRepository, CultureSite::class.java),
        "products" to ScopedHandle(productRepository, Product::class.java),
        "sector-entities" to ScopedHandle(sectorEntityRepository, SectorEntity::class.java)
    )

    private val unmodifiableKeys = setOf(
        "id", "countyId", "localId", "centralId", "syncStatus", "contentHash",
        "syncedAt", "createdAt", "updatedAt", "deletedAt"
    )

    private fun requireDataAccess(user: User) {
        val access = scopeResolver.accessFor(user)
        if (!access.unrestricted && access.counties.isEmpty()) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "No county data access for this role")
        }
    }

    fun counties(): List<County> = countyRepository.findAll()

    fun county(id: Long): County =
        countyRepository.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "County not found") }

    fun sectors(): List<Sector> = sectorRepository.findAll()

    fun sector(id: Long): Sector =
        sectorRepository.findById(id).orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Sector not found") }

    fun countySectors(countySlug: String?): List<CountySectorLink> {
        val county = requireCounty(countySlug)
        return countySectorRepository.findAllByCountyIdOrderByIdAsc(county.id!!)
    }

    private fun requireCounty(countySlug: String?): County {
        val county = countySlug?.let { countyRepository.findBySlug(it) }
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Unknown county slug: $countySlug")
        return county
    }

    private fun ownCounty(user: User): County =
        user.countySlug?.let { countyRepository.findBySlug(it) }
            ?: throw ResponseStatusException(HttpStatus.FORBIDDEN, "County scope not configured for this user")

    private fun num(value: Any?): Long? = (value as? Number)?.toLong()

    private fun resolveCountyId(user: User, bodyCountyId: Long?, countySlug: String?): Long? {
        val access = scopeResolver.accessFor(user)
        val requested = when (user.tier) {
            Role.COUNTY -> ownCounty(user).id
            else -> bodyCountyId?.also { county(it) } ?: countySlug?.let { requireCounty(it).id }
        } ?: return null
        if (!access.unrestricted && requested !in access.counties) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "County outside your access scope")
        }
        return requested
    }

    @Suppress("UNCHECKED_CAST")
    private fun handle(resource: String): ScopedHandle =
        scopedRepos[resource]
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Unknown resource: $resource")

    /**
     * Allowed county ids for county-data resources, or null when unrestricted (KICC only).
     * NATIONAL is independent of the county sector structure — empty envelope means
     * NO county data access, not unrestricted access.
     */
    private fun dataCounties(user: User, countySlug: String?): Set<Long>? {
        val access = scopeResolver.accessFor(user)
        if (!access.unrestricted && access.counties.isEmpty()) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "No county data access for this role")
        }
        val allowed = access.counties
        val requested = countySlug?.let { requireCounty(it).id }
        return when {
            access.unrestricted -> requested?.let { setOf(it) }
            requested != null -> if (requested in allowed) setOf(requested)
            else throw ResponseStatusException(HttpStatus.NOT_FOUND, "County outside your access scope")
            else -> allowed
        }
    }

    private fun requireInScope(user: User, resource: String, entity: CountyScoped) {
        val access = scopeResolver.accessFor(user)
        val countyId = entity.countyId
        if (!access.unrestricted && (countyId == null || countyId !in access.counties)) {
            throw ResponseStatusException(HttpStatus.NOT_FOUND, "$resource not found in scope")
        }
        if (resource == "sector-entities" && access.sectorIds.isNotEmpty() &&
            (entity is SectorEntity) && (entity.sectorId == null || entity.sectorId !in access.sectorIds)
        ) {
            throw ResponseStatusException(HttpStatus.NOT_FOUND, "$resource not found in scope")
        }
    }

    @Suppress("UNCHECKED_CAST")
    fun listScoped(resource: String, user: User, countySlug: String?): List<*> {
        val h = handle(resource)
        val access = scopeResolver.accessFor(user)
        val counties = dataCounties(user, countySlug)
        val repo = h.repo as CountyScopedRepository<CountyScoped>
        val rows = if (counties != null) repo.findAllByCountyIdInOrderByIdAsc(counties) else repo.findAll()
        return if (resource == "sector-entities" && access.sectorIds.isNotEmpty()) {
            rows.filter { it is SectorEntity && it.sectorId != null && it.sectorId in access.sectorIds }
        } else {
            rows
        }
    }

    @Suppress("UNCHECKED_CAST")
    fun getScoped(resource: String, user: User, id: Long): Any {
        requireDataAccess(user)
        val h = handle(resource)
        val repo = h.repo as CountyScopedRepository<CountyScoped>
        val entity = repo.findById(id).orElse(null)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "$resource $id not found in scope")
        requireInScope(user, resource, entity)
        return entity
    }

    @Suppress("UNCHECKED_CAST")
    @Transactional
    fun createScoped(resource: String, user: User, body: Map<String, Any?>): Any {
        val h = handle(resource)
        requireDataAccess(user)
        val countyId = resolveCountyId(user, num(body["countyId"]), null)
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "countyId is required")
        val safe = body - unmodifiableKeys
        val entity = objectMapper.convertValue(safe, h.entityClass)
        entity.countyId = countyId
        val repo = h.repo as CountyScopedRepository<CountyScoped>
        repo.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=$resource, countyId=$countyId")
        return entity
    }

    @Suppress("UNCHECKED_CAST")
    @Transactional
    fun updateScoped(resource: String, user: User, id: Long, body: Map<String, Any?>): Any {
        val h = handle(resource)
        requireDataAccess(user)
        val repo = h.repo as CountyScopedRepository<CountyScoped>
        val entity = repo.findById(id).orElse(null)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "$resource $id not found in scope")
        requireInScope(user, resource, entity)
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(entity, safe)
        if (user.tier == Role.COUNTY) {
            entity.countyId = ownCounty(user).id
        } else {
            val newCountyId = num(body["countyId"])
            if (newCountyId != null && permissionEvaluator.effectivePrivileges(user).contains(Privilege.COUNTY_MANAGE)) {
                val target = countyRepository.findById(newCountyId)
                    .orElseThrow { ResponseStatusException(HttpStatus.BAD_REQUEST, "Target county not found: $newCountyId") }
                val access = scopeResolver.accessFor(user)
                if (access.counties.isNotEmpty() && target.id !in access.counties) {
                    throw ResponseStatusException(HttpStatus.FORBIDDEN, "Target county outside your access scope")
                }
                entity.countyId = target.id
            }
        }
        repo.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=$resource, id=$id")
        return entity
    }

    @Suppress("UNCHECKED_CAST")
    @Transactional
    fun deleteScoped(resource: String, user: User, id: Long) {
        val h = handle(resource)
        requireDataAccess(user)
        val repo = h.repo as CountyScopedRepository<CountyScoped>
        val entity = repo.findById(id).orElse(null)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "$resource $id not found in scope")
        requireInScope(user, resource, entity)
        repo.delete(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=$resource, id=$id")
    }

    @Suppress("UNCHECKED_CAST")
    @Transactional
    fun publishScoped(resource: String, user: User, id: Long, published: Boolean): Any {
        val h = handle(resource)
        requireDataAccess(user)
        val repo = h.repo as CountyScopedRepository<CountyScoped>
        val entity = repo.findById(id).orElse(null)
            ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "$resource $id not found in scope")
        requireInScope(user, resource, entity)
        objectMapper.updateValue(entity, mapOf("isPublished" to published))
        repo.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_PUBLISHED", null, "resource=$resource, id=$id, published=$published")
        return entity
    }

    /** County ids visible for exhibitions/venues/booths: own county for COUNTY tier, plus any delegated counties. */
    private fun exhibitionCounties(user: User): Set<Long>? {
        val access = scopeResolver.accessFor(user)
        return when (user.tier) {
            Role.COUNTY -> access.counties
            else -> access.delegatedCounties.takeIf { it.isNotEmpty() }
        }
    }

    private fun exhibitionInScope(user: User, entity: Exhibition) {
        val allowed = exhibitionCounties(user)
        val countyId = entity.countyId
        if (allowed != null && (countyId == null || countyId !in allowed)) {
            throw ResponseStatusException(HttpStatus.NOT_FOUND, "Exhibition not found in scope")
        }
    }

    fun exhibitions(user: User, countySlug: String?): List<Exhibition> {
        val allowed = exhibitionCounties(user)
        return when {
            allowed != null -> {
                val countyId = countySlug?.let { requireCounty(it).id }
                if (countyId != null) {
                    if (countyId !in allowed) throw ResponseStatusException(HttpStatus.NOT_FOUND, "County outside your access scope")
                    exhibitionRepository.findAllByCountyIdOrderByIdDesc(countyId)
                } else {
                    exhibitionRepository.findAllByCountyIdInOrderByIdDesc(allowed)
                }
            }
            countySlug != null -> {
                val countyId = requireCounty(countySlug).id
                    ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found")
                exhibitionRepository.findAllByCountyIdOrderByIdDesc(countyId)
            }
            else -> exhibitionRepository.findAllByOrderByIdDesc()
        }
    }

    fun exhibition(user: User, id: Long): Exhibition {
        val entity = exhibitionRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Exhibition not found") }
        exhibitionInScope(user, entity)
        return entity
    }

    @Transactional
    fun createExhibition(user: User, body: Map<String, Any?>): Exhibition {
        requireDataAccess(user)
        val countyId = when (user.tier) {
            Role.COUNTY -> ownCounty(user).id
            else -> num(body["countyId"])?.also { county(it) }
        } ?: throw ResponseStatusException(HttpStatus.FORBIDDEN, "No exhibition access for this role")
        val access = scopeResolver.accessFor(user)
        if (!access.unrestricted && countyId !in access.counties) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "County outside your access scope")
        }
        val safe = body - unmodifiableKeys
        val entity = objectMapper.convertValue(safe, Exhibition::class.java)
        entity.countyId = countyId
        exhibitionRepository.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=exhibitions, countyId=$countyId")
        return entity
    }

    @Transactional
    fun updateExhibition(user: User, id: Long, body: Map<String, Any?>): Exhibition {
        requireDataAccess(user)
        val entity = exhibition(user, id)
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(entity, safe)
        if (user.tier == Role.COUNTY) entity.countyId = ownCounty(user).id
        exhibitionRepository.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=exhibitions, id=$id")
        return entity
    }

    @Transactional
    fun deleteExhibition(user: User, id: Long) {
        requireDataAccess(user)
        exhibitionRepository.delete(exhibition(user, id))
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=exhibitions, id=$id")
    }

    private fun venueNamesInScope(user: User): Set<String>? {
        val counties = exhibitionCounties(user) ?: return null
        if (counties.isEmpty()) return null
        return countyRepository.findAllById(counties).map { it.name }.toSet()
    }

    private fun venueInScope(user: User, entity: Venue) {
        val names = venueNamesInScope(user)
        if (names != null && entity.county !in names) {
            throw ResponseStatusException(HttpStatus.NOT_FOUND, "Venue not found in scope")
        }
    }

    fun venues(user: User, countySlug: String?): List<Venue> {
        val names = venueNamesInScope(user)
        return when {
            names != null -> {
                val countyName = countySlug?.let { requireCounty(it).name }
                if (countyName != null) {
                    if (countyName !in names) throw ResponseStatusException(HttpStatus.NOT_FOUND, "County outside your access scope")
                    venueRepository.findAllByCountyOrderByIdAsc(countyName)
                } else {
                    venueRepository.findAllByCountyInOrderByIdAsc(names)
                }
            }
            countySlug != null -> venueRepository.findAllByCountyOrderByIdAsc(requireCounty(countySlug).name)
            else -> venueRepository.findAllByOrderByIdAsc()
        }
    }

    fun venue(user: User, id: Long): Venue {
        val venue = venueRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Venue not found") }
        venueInScope(user, venue)
        return venue
    }

    @Transactional
    fun createVenue(user: User, body: Map<String, Any?>): Venue {
        requireDataAccess(user)
        val safe = body - unmodifiableKeys
        val entity = objectMapper.convertValue(safe, Venue::class.java)
        if (user.tier == Role.COUNTY) {
            entity.county = ownCounty(user).name
        } else {
            val names = venueNamesInScope(user)
            if (names != null && entity.county !in names) {
                throw ResponseStatusException(HttpStatus.FORBIDDEN, "County outside your access scope")
            }
        }
        venueRepository.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=venues")
        return entity
    }

    @Transactional
    fun updateVenue(user: User, id: Long, body: Map<String, Any?>): Venue {
        requireDataAccess(user)
        val entity = venue(user, id)
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(entity, safe)
        if (user.tier == Role.COUNTY) entity.county = ownCounty(user).name
        venueRepository.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=venues, id=$id")
        return entity
    }

    @Transactional
    fun deleteVenue(user: User, id: Long) {
        requireDataAccess(user)
        venueRepository.delete(venue(user, id))
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=venues, id=$id")
    }

    fun booths(user: User, exhibitionId: Long?): List<Booth> {
        val access = scopeResolver.accessFor(user)
        val rows = if (exhibitionId != null) {
            val exhibition = exhibition(user, exhibitionId)
            if (user.tier == Role.EXHIBITOR && exhibition.status != "published") {
                throw ResponseStatusException(HttpStatus.FORBIDDEN, "Exhibition not published")
            }
            boothRepository.findByExhibitionIdOrderByIdAsc(exhibitionId)
        } else {
            val allowed = exhibitionCounties(user)
            if (allowed != null && allowed.isNotEmpty()) {
                val exhibitionIds = exhibitionRepository.findAllByCountyIdInOrderByIdDesc(allowed).map { it.id!! }
                if (exhibitionIds.isEmpty()) emptyList()
                else boothRepository.findByExhibitionIdInOrderByIdAsc(exhibitionIds)
            } else {
                boothRepository.findAll()
            }
        }
        if (access.boothIds.isEmpty()) return rows
        val countyByExhibition = exhibitionRepository.findAll().associate { it.id!! to it.countyId }
        return rows.filter { booth ->
            booth.id != null && (
                booth.id in access.boothIds ||
                    (access.counties.isNotEmpty() && booth.exhibitionId?.let { countyByExhibition[it] }?.let { it in access.counties } == true)
                )
        }
    }

    fun booth(user: User, id: Long): Booth {
        val booth = boothRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Booth not found") }
        if (user.tier == Role.COUNTY) {
            val exhibitionId = booth.exhibitionId ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "Booth not found in scope")
            exhibition(user, exhibitionId)
        }
        return booth
    }

    @Transactional
    fun createBooth(user: User, body: Map<String, Any?>): Booth {
        if (user.tier == Role.EXHIBITOR) throw ResponseStatusException(HttpStatus.FORBIDDEN, "No booth management access for this role")
        val exhibitionId = num(body["exhibitionId"])
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "exhibitionId is required")
        exhibition(user, exhibitionId)
        val safe = body - unmodifiableKeys
        val entity = objectMapper.convertValue(safe, Booth::class.java)
        entity.exhibitionId = exhibitionId
        boothRepository.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=booths, exhibitionId=$exhibitionId")
        return entity
    }

    @Transactional
    fun updateBooth(user: User, id: Long, body: Map<String, Any?>): Booth {
        if (user.tier == Role.EXHIBITOR) throw ResponseStatusException(HttpStatus.FORBIDDEN, "No booth management access for this role")
        val entity = booth(user, id)
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(entity, safe)
        boothRepository.save(entity)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=booths, id=$id")
        return entity
    }

    @Transactional
    fun deleteBooth(user: User, id: Long) {
        if (user.tier == Role.EXHIBITOR) throw ResponseStatusException(HttpStatus.FORBIDDEN, "No booth management access for this role")
        boothRepository.delete(booth(user, id))
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=booths, id=$id")
    }

    fun productCategories(): List<ProductCategory> = productCategoryRepository.findAll()

    @Transactional
    fun createProductCategory(user: User, body: Map<String, Any?>): ProductCategory {
        val name = body["name"]?.toString().orEmpty().ifBlank {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "name is required")
        }
        val slug = (body["slug"] as? String)?.trim()?.lowercase() ?: name.lowercase().replace(Regex("\\s+"), "-")
        val safe = body - unmodifiableKeys
        val cat = objectMapper.convertValue(safe, ProductCategory::class.java)
        cat.slug = slug
        cat.createdAt = now()
        productCategoryRepository.save(cat)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=product-categories, slug=$slug")
        return cat
    }

    @Transactional
    fun updateProductCategory(user: User, id: Long, body: Map<String, Any?>): ProductCategory {
        val cat = productCategoryRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Product category not found") }
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(cat, safe)
        cat.updatedAt = now()
        productCategoryRepository.save(cat)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=product-categories, id=$id")
        return cat
    }

    @Transactional
    fun deleteProductCategory(user: User, id: Long) {
        val cat = productCategoryRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Product category not found") }
        productCategoryRepository.delete(cat)
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=product-categories, id=$id")
    }

    private val countyScopedRepos: List<CountyScopedRepository<*>> = listOf(
        attractionRepository, hotelRepository, farmRepository, healthRepository,
        institutionRepository, transportRepository, cultureRepository, productRepository,
        sectorEntityRepository
    )

    @Transactional
    fun createCounty(user: User, body: Map<String, Any?>): County {
        val slug = (body["slug"] as? String)?.trim()?.lowercase()
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "slug is required")
        if (!slug.matches(Regex("^[a-z0-9]([a-z0-9-]*[a-z0-9])?$"))) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "slug must be lowercase alphanumeric with hyphens")
        }
        if (countyRepository.existsBySlug(slug)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "county slug already exists: $slug")
        }
        if (body["name"]?.toString().orEmpty().isBlank()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "name is required")
        }
        val safe = body - unmodifiableKeys
        val county = objectMapper.convertValue(safe, County::class.java)
        county.slug = slug
        countyRepository.save(county)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=counties, slug=$slug")
        return county
    }

    @Transactional
    fun updateCounty(user: User, id: Long, body: Map<String, Any?>): County {
        if (user.tier == Role.EXHIBITOR) throw ResponseStatusException(HttpStatus.FORBIDDEN, "Exhibitors cannot edit counties")
        val county = county(id)
        if (user.tier == Role.COUNTY && county.slug != user.countySlug) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Cannot edit a different county")
        }
        val slug = (body["slug"] as? String)?.trim()?.lowercase()
        if (slug != null && slug != county.slug) {
            if (!slug.matches(Regex("^[a-z0-9]([a-z0-9-]*[a-z0-9])?$"))) {
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "slug must be lowercase alphanumeric with hyphens")
            }
            if (countyRepository.existsBySlug(slug)) {
                throw ResponseStatusException(HttpStatus.CONFLICT, "county slug already exists: $slug")
            }
        }
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(county, safe)
        if (slug != null) county.slug = slug
        countyRepository.save(county)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=counties, id=$id")
        return county
    }

    @Transactional
    fun deleteCounty(user: User, id: Long) {
        val county = county(id)
        if (countySectorRepository.existsByCountyId(id)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "County has sector links; remove them first")
        }
        for (repo in countyScopedRepos) {
            val typed = repo as CountyScopedRepository<*>
            if (typed.findAllByCountyIdInOrderByIdAsc(listOf(id)).isNotEmpty()) {
                throw ResponseStatusException(HttpStatus.CONFLICT, "County has data rows; delete them first")
            }
        }
        if (exhibitionRepository.findAllByCountyIdOrderByIdDesc(id).isNotEmpty()) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "County has exhibitions; delete them first")
        }
        if (venueRepository.findAllByCountyOrderByIdAsc(county.name).isNotEmpty()) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "County has venues; delete them first")
        }
        if (userRepository.existsByCountySlug(county.slug)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "County has users; remove or reassign them first")
        }
        countyRepository.delete(county)
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=counties, id=$id, slug=${county.slug}")
    }

    @Transactional
    fun createSector(user: User, body: Map<String, Any?>): Sector {
        val name = body["name"]?.toString().orEmpty().ifBlank {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "name is required")
        }
        val slug = (body["slug"] as? String)?.trim()?.lowercase() ?: name.lowercase().replace(Regex("\\s+"), "-")
        if (sectorRepository.existsBySlug(slug)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "sector slug already exists: $slug")
        }
        val safe = body - unmodifiableKeys
        val sector = objectMapper.convertValue(safe, Sector::class.java)
        sector.slug = slug
        sectorRepository.save(sector)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=sectors, slug=$slug")
        return sector
    }

    @Transactional
    fun updateSector(user: User, id: Long, body: Map<String, Any?>): Sector {
        val sector = sector(id)
        val slug = (body["slug"] as? String)?.trim()?.lowercase()
        if (slug != null && slug != sector.slug && sectorRepository.existsBySlug(slug)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "sector slug already exists: $slug")
        }
        val safe = body - unmodifiableKeys
        objectMapper.updateValue(sector, safe)
        if (slug != null) sector.slug = slug
        sectorRepository.save(sector)
        auditLogService.record(user.id!!, "COUNTY_DATA_UPDATED", null, "resource=sectors, id=$id")
        return sector
    }

    @Transactional
    fun deleteSector(user: User, id: Long) {
        val sector = sector(id)
        if (countySectorRepository.existsBySectorId(id)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "Sector is linked to counties; unlink them first")
        }
        if (sectorEntityRepository.existsBySectorId(id)) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "Sector has sector-entity references; remove them first")
        }
        sectorRepository.delete(sector)
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=sectors, id=$id")
    }

    @Transactional
    fun createCountySectorLink(user: User, body: Map<String, Any?>): CountySectorLink {
        val countyId = num(body["countyId"])
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "countyId is required")
        val sectorId = num(body["sectorId"])
            ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "sectorId is required")
        county(countyId)
        sector(sectorId)
        if (countySectorRepository.findByCountyIdAndSectorId(countyId, sectorId) != null) {
            throw ResponseStatusException(HttpStatus.CONFLICT, "Link already exists between county $countyId and sector $sectorId")
        }
        val link = CountySectorLink(id = null, countyId = countyId, sectorId = sectorId, subSectors = body["subSectors"]?.toString())
        countySectorRepository.save(link)
        auditLogService.record(user.id!!, "COUNTY_DATA_CREATED", null, "resource=county-sectors, countyId=$countyId, sectorId=$sectorId")
        return link
    }

    @Transactional
    fun deleteCountySectorLink(user: User, id: Long) {
        val link = countySectorRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "county-sector link not found") }
        countySectorRepository.delete(link)
        auditLogService.record(user.id!!, "COUNTY_DATA_DELETED", null, "resource=county-sectors, id=$id")
    }

    fun now(): String = Instant.now().toString()
}
