package ke.go.kicc.engine.data

import com.fasterxml.jackson.annotation.JsonProperty
import jakarta.persistence.*

interface CountyScoped {
    val id: Long?
    var countyId: Long?
}

@Entity
@Table(name = "counties")
class County(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var capital: String = "",

    @Column(nullable = false)
    var code: String = "",

    @Column(nullable = false)
    var formerProvince: String = "",

    @Column(nullable = false)
    var economicZone: String = "",

    var population2024: Long? = null,
    var areaKm2: Double? = null,
    var latitude: Double? = null,
    var longitude: Double? = null,
    var weatherStationId: String? = null,
    var primarySectors: String? = null,
    var iconEmoji: String? = null,
    var tagline: String? = null,
    var description: String? = null,
    var tourismHighlights: String? = null,
    var warmestMonth: String? = null,
    var coolestMonth: String? = null,
    var rainySeason: String? = null,
    var drySeason: String? = null,

    @Column(nullable = false)
    var slug: String = "",

    var weatherTags: String? = null,

    @JsonProperty("isActive")
    @Column(nullable = false)
    var isActive: Boolean = true,

    var profileImage: String? = null,
    var mapX: Double? = null,
    var mapY: Double? = null,
    var mapZ: Double? = null,

    @Column(nullable = false)
    var sceneType: String = "default",

    var region: String? = null,
    var createdAt: String? = null,
    var updatedAt: String? = null,

    // ---- Dual-schema bridge: Laravel reads the snake_case side of this shared table.
    // Mirrors below keep both conventions in sync (see LegacyColumnMirror).
    @Column(name = "former_province", nullable = false)
    var legacyFormerProvince: String = "",

    @Column(name = "economic_zone", nullable = false)
    var legacyEconomicZone: String = "",

    @Column(name = "is_active", nullable = false)
    var legacyIsActive: Boolean = true,

    @Column(name = "scene_type", nullable = false)
    var legacySceneType: String = "default",

    @Column(name = "population_2024")
    var legacyPopulation2024: Long? = null,

    @Column(name = "area_km2")
    var legacyAreaKm2: Double? = null,

    @Column(name = "map_x")
    var legacyMapX: Double? = null,

    @Column(name = "map_y")
    var legacyMapY: Double? = null,

    @Column(name = "map_z")
    var legacyMapZ: Double? = null,

    @Column(name = "icon_emoji")
    var legacyIconEmoji: String? = null,

    @Column(name = "profile_image")
    var legacyProfileImage: String? = null,

    @Column(name = "tourism_highlights")
    var legacyTourismHighlights: String? = null,

    @Column(name = "warmest_month")
    var legacyWarmestMonth: String? = null,

    @Column(name = "coolest_month")
    var legacyCoolestMonth: String? = null,

    @Column(name = "rainy_season")
    var legacyRainySeason: String? = null,

    @Column(name = "dry_season")
    var legacyDrySeason: String? = null,

    @Column(name = "weather_station_id")
    var legacyWeatherStationId: String? = null,

    @Column(name = "primary_sectors")
    var legacyPrimarySectors: String? = null,

    @Column(name = "weather_tags")
    var legacyWeatherTags: String? = null
) {
    @PostLoad
    fun adoptLegacyColumns() {
        if (formerProvince.isBlank()) formerProvince = legacyFormerProvince
        if (economicZone.isBlank()) economicZone = legacyEconomicZone
        if (sceneType.isBlank()) sceneType = legacySceneType
        if (population2024 == null) population2024 = legacyPopulation2024
        if (areaKm2 == null) areaKm2 = legacyAreaKm2
        if (mapX == null) mapX = legacyMapX
        if (mapY == null) mapY = legacyMapY
        if (mapZ == null) mapZ = legacyMapZ
        if (iconEmoji == null) iconEmoji = legacyIconEmoji
        if (profileImage == null) profileImage = legacyProfileImage
        if (tourismHighlights == null) tourismHighlights = legacyTourismHighlights
        if (warmestMonth == null) warmestMonth = legacyWarmestMonth
        if (coolestMonth == null) coolestMonth = legacyCoolestMonth
        if (rainySeason == null) rainySeason = legacyRainySeason
        if (drySeason == null) drySeason = legacyDrySeason
        if (weatherStationId == null) weatherStationId = legacyWeatherStationId
        if (primarySectors == null) primarySectors = legacyPrimarySectors
        if (weatherTags == null) weatherTags = legacyWeatherTags
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyFormerProvince = formerProvince
        legacyEconomicZone = economicZone
        legacyIsActive = isActive
        legacySceneType = sceneType
        legacyPopulation2024 = population2024
        legacyAreaKm2 = areaKm2
        legacyMapX = mapX
        legacyMapY = mapY
        legacyMapZ = mapZ
        legacyIconEmoji = iconEmoji
        legacyProfileImage = profileImage
        legacyTourismHighlights = tourismHighlights
        legacyWarmestMonth = warmestMonth
        legacyCoolestMonth = coolestMonth
        legacyRainySeason = rainySeason
        legacyDrySeason = drySeason
        legacyWeatherStationId = weatherStationId
        legacyPrimarySectors = primarySectors
        legacyWeatherTags = weatherTags
    }
}

@Entity
@Table(name = "sectors")
class Sector(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var slug: String = "",

    @Column(nullable = false)
    var code: String = "",

    var emoji: String? = null,
    var description: String? = null,
    var parentId: Long? = null,
    var icon: String? = null,

    @JsonProperty("isActive")
    @Column(nullable = false)
    var isActive: Boolean = true,

    @Column(nullable = false)
    var sortOrder: Int = 0,

    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
    var syncedAt: String? = null
)

@Entity
@Table(name = "county_sector")
class CountySectorLink(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var sectorId: Long? = null,

    var subSectors: String? = null
) : CountyScoped

@Entity
@Table(name = "county_tourism_attractions")
class Attraction(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    var description: String? = null,

    @Column(nullable = false)
    var category: String = "",

    var location: String? = null,
    var entryFee: Double? = null,
    var openingHours: String? = null,
    var contact: String? = null,
    var latitude: Double? = null,
    var longitude: Double? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_hotels")
class Hotel(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var category: String = "",

    var starRating: Int? = null,
    var description: String? = null,
    var location: String? = null,
    var phone: String? = null,
    var email: String? = null,
    var website: String? = null,
    var latitude: Double? = null,
    var longitude: Double? = null,
    var priceRangeMin: Double? = null,
    var priceRangeMax: Double? = null,
    var amenities: String? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_farms")
class Farm(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var type: String = "",

    var description: String? = null,
    var location: String? = null,
    var contact: String? = null,
    var sizeAcres: Double? = null,
    var mainCrops: String? = null,
    var products: String? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_health_facilities")
class HealthFacility(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var type: String = "",

    @Column(nullable = false)
    var level: String = "",

    var description: String? = null,
    var location: String? = null,
    var phone: String? = null,
    var email: String? = null,
    var services: String? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_institutions")
class Institution(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var type: String = "",

    var description: String? = null,
    var location: String? = null,
    var phone: String? = null,
    var email: String? = null,
    var website: String? = null,
    var studentCount: Long? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_transport")
class TransportService(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var type: String = "",

    var description: String? = null,
    var location: String? = null,
    var operator: String? = null,
    var contact: String? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_culture_sites")
class CultureSite(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var type: String = "",

    var description: String? = null,
    var location: String? = null,
    var community: String? = null,
    var contact: String? = null,
    var latitude: Double? = null,
    var longitude: Double? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "county_products")
class Product(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    var userId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    var description: String? = null,

    @Column(nullable = false)
    var category: String = "",

    var price: Double? = null,
    var unit: String? = null,
    var stock: Int? = null,

    @Column(nullable = false)
    var status: String = "active",

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}


@Entity
@Table(name = "product_categories")
class ProductCategory(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var slug: String = "",

    var description: String? = null,
    var parentId: Long? = null,
    var icon: String? = null,
    var imageUrl: String? = null,
    var sortOrder: Int? = null,

    @JsonProperty("isActive")
    var isActive: Int? = 1,

    var createdAt: String? = null,
    var updatedAt: String? = null
)

@Entity
@Table(name = "exhibitions")
class Exhibition(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var slug: String = "",

    var description: String? = null,
    var tagline: String? = null,

    @Column(name = "countyId")
    override var countyId: Long? = null,

    @Column(name = "county_id")
    var legacyCountyId: Long? = null,

    @Column(nullable = false)
    var startDate: String = "",

    @Column(nullable = false)
    var endDate: String = "",

    var openTime: String? = null,
    var closeTime: String? = null,
    var coverImage: String? = null,
    var gallery: String? = null,

    @Column(nullable = false)
    var status: String = "draft",

    @JsonProperty("isFeatured")
    var isFeatured: Boolean = false,

    var organizerInfo: String? = null,
    var meta: String? = null,
    var venueId: Long? = null,
    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
    var syncedAt: String? = null
) : CountyScoped {

    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && (legacyCountyId ?: 0) != 0L) countyId = legacyCountyId
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId
    }
}

@Entity
@Table(name = "venues")
class Venue(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    @Column(nullable = false)
    var slug: String = "",

    var description: String? = null,

    @Column(nullable = false)
    var venueType: String = "",

    var address: String? = null,
    var city: String? = null,
    var county: String? = null,
    var latitude: Double? = null,
    var longitude: Double? = null,
    var capacity: Long? = null,
    var amenities: String? = null,
    var contactInfo: String? = null,
    var coverImage: String? = null,

    @JsonProperty("isActive")
    @Column(nullable = false)
    var isActive: Boolean = true,

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
    var syncedAt: String? = null
)

@Entity
@Table(name = "booths")
class Booth(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var exhibitionId: Long? = null,

    @Column(nullable = false)
    var boothNumber: String = "",

    var name: String? = null,

    @Column(nullable = false)
    var size: String = "",

    @Column(nullable = false)
    var category: String = "",

    var description: String? = null,
    var amenities: String? = null,

    @Column(nullable = false)
    var price: Double = 0.0,

    var discountPrice: Double? = null,

    @Column(nullable = false)
    var maxQuantity: Int = 1,

    @Column(nullable = false)
    var bookedQuantity: Int = 0,

    var locationHint: String? = null,
    var dimensions: String? = null,
    var images: String? = null,

    @Column(nullable = false)
    var status: String = "available",

    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
    var syncedAt: String? = null
)

@Entity
@Table(name = "sector_entities")
class SectorEntity(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    override var id: Long? = null,

    @Column(name = "countyId", nullable = false)
    override var countyId: Long? = null,

    @Column(nullable = false)
    var sectorId: Long? = null,

    @Column(nullable = false)
    var entityType: String = "",

    @Column(nullable = false)
    var entityId: Long? = null,

    @Column(nullable = false)
    var name: String = "",

    var description: String? = null,

    @Column(nullable = false)
    var sectorType: String = "",

    @Column(nullable = false)
    var captureStatus: String = "complete",

    var sponsorFunderTag: String? = null,
    var latitude: Double? = null,
    var longitude: Double? = null,
    var contactInfo: String? = null,
    var socialLinks: String? = null,

    @JsonProperty("isPublished")
    @Column(name = "isPublished", nullable = false)
    var isPublished: Boolean = false,

    @Column(nullable = false)
    var languagePrimary: String = "en",

    var tags: String? = null,
    var verificationOwner: String? = null,
    var verificationDate: String? = null,
    var createdAt: String? = null,
    var updatedAt: String? = null,
    var localId: String? = null,
    var centralId: Long? = null,
    var syncStatus: String = "local",
    var contentHash: String? = null,
        var syncedAt: String? = null,

    // ---- Dual-schema bridge (legacy Laravel snake_case mirrors) ----
    @Column(name = "county_id", nullable = false)
    var legacyCountyId: Long = 0,

    @Column(name = "is_published", nullable = false)
    var legacyIsPublished: Boolean = false
) : CountyScoped {
    @PostLoad
    fun adoptLegacyColumns() {
        if ((countyId ?: 0) == 0L && legacyCountyId != 0L) countyId = legacyCountyId
        if (!isPublished && legacyIsPublished) isPublished = true
    }

    @PrePersist
    @PreUpdate
    fun mirrorLegacyColumns() {
        legacyCountyId = countyId ?: 0
        legacyIsPublished = isPublished
    }
}

