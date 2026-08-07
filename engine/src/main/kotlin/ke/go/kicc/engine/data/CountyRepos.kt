package ke.go.kicc.engine.data

import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.data.repository.NoRepositoryBean

@NoRepositoryBean
interface CountyScopedRepository<T : CountyScoped> : JpaRepository<T, Long> {
    fun findAllByCountyIdOrderByIdAsc(countyId: Long): List<T>

    fun findAllByCountyIdInOrderByIdAsc(countyIds: Collection<Long>): List<T>

    fun findByLocalIdAndCountyId(localId: String, countyId: Long): T?
}

interface CountyRepository : JpaRepository<County, Long> {
    fun findBySlug(slug: String): County?

    fun existsBySlug(slug: String): Boolean

    fun existsByName(name: String): Boolean
}

interface SectorRepository : JpaRepository<Sector, Long> {
    fun existsBySlug(slug: String): Boolean

    fun findBySlug(slug: String): Sector?
}

interface CountySectorRepository : JpaRepository<CountySectorLink, Long> {
    fun findAllByCountyIdOrderByIdAsc(countyId: Long): List<CountySectorLink>

    fun findByCountyIdAndSectorId(countyId: Long, sectorId: Long): CountySectorLink?

    fun existsByCountyId(countyId: Long): Boolean

    fun existsBySectorId(sectorId: Long): Boolean
}

interface AttractionRepository : CountyScopedRepository<Attraction>

interface HotelRepository : CountyScopedRepository<Hotel>

interface FarmRepository : CountyScopedRepository<Farm>

interface HealthFacilityRepository : CountyScopedRepository<HealthFacility>

interface InstitutionRepository : CountyScopedRepository<Institution>

interface TransportRepository : CountyScopedRepository<TransportService>

interface CultureRepository : CountyScopedRepository<CultureSite>

interface ProductRepository : CountyScopedRepository<Product>

interface ProductCategoryRepository : JpaRepository<ProductCategory, Long>

interface ExhibitionRepository : JpaRepository<Exhibition, Long> {
    fun findAllByOrderByIdDesc(): List<Exhibition>

    fun findAllByCountyIdOrderByIdDesc(countyId: Long): List<Exhibition>

    fun findAllByCountyIdInOrderByIdDesc(countyIds: Collection<Long>): List<Exhibition>

    fun findByIdAndCountyId(id: Long, countyId: Long): Exhibition?
}

interface VenueRepository : JpaRepository<Venue, Long> {
    fun findAllByOrderByIdAsc(): List<Venue>

    fun findAllByCountyOrderByIdAsc(county: String): List<Venue>

    fun findAllByCountyInOrderByIdAsc(counties: Collection<String>): List<Venue>
}

interface BoothRepository : JpaRepository<Booth, Long> {
    fun findByExhibitionIdOrderByIdAsc(exhibitionId: Long): List<Booth>

    fun findByExhibitionIdInOrderByIdAsc(exhibitionIds: List<Long>): List<Booth>

    fun findByIdAndExhibitionId(id: Long, exhibitionId: Long): Booth?
}

interface SectorEntityRepository : CountyScopedRepository<SectorEntity> {
    fun existsBySectorId(sectorId: Long): Boolean
}
