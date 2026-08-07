package ke.go.kicc.engine.data

import org.springframework.beans.factory.annotation.Value
import org.springframework.boot.CommandLineRunner
import org.springframework.jdbc.core.JdbcTemplate
import org.springframework.stereotype.Component
import java.io.File
import java.sql.Connection
import java.sql.DriverManager
import java.sql.ResultSet
import java.sql.Types

@Component
class CountyDataImporter(
    private val countyRepository: CountyRepository,
    private val jdbcTemplate: JdbcTemplate,
    @Value("\${kicc.source-db}") private val sourceDbPath: String
) : CommandLineRunner {

    private val tables = listOf(
        "counties",
        "sectors",
        "county_sector",
        "county_tourism_attractions",
        "county_hotels",
        "county_farms",
        "county_health_facilities",
        "county_institutions",
        "county_transport",
        "county_culture_sites",
        "county_products",
        "product_categories",
        "exhibitions",
        "venues",
        "booths",
        "sector_entities"
    )

    override fun run(vararg args: String?) {
        if (countyRepository.count() > 0L) return
        val source = File(sourceDbPath)
        if (!source.exists()) {
            logger.info("Source DB not found at {}, skipping import", sourceDbPath)
            return
        }
        DriverManager.getConnection("jdbc:sqlite:$sourceDbPath").use { src ->
            for (table in tables) {
                importTable(src, table)
            }
        }
    }

    private fun importTable(src: Connection, table: String) {
        val columns = src.createStatement().use { st ->
            val rs = st.executeQuery("PRAGMA table_info('$table')")
            buildList {
                while (rs.next()) add(rs.getString("name"))
            }
        }
        if (columns.isEmpty()) return
        val targetColumns = jdbcTemplate.queryForList(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?",
            table.uppercase()
        ).map { it["COLUMN_NAME"] as String }
        val targetUpper = targetColumns.toSet()
        val usable = columns.filter { it.uppercase() in targetUpper }
        if (usable.isEmpty()) return

        val selectSql = columns.joinToString(", ") { it }
        var maxId = 0L
        val rows = src.createStatement().use { st ->
            val rs = st.executeQuery("SELECT $selectSql FROM $table")
            buildList {
                while (rs.next()) {
                    val row = LinkedHashMap<String, Any?>()
                    for (c in columns) {
                        row[c] = normalize(rs, c, rs.getObject(c))
                    }
                    (row["id"] as? Number)?.let { if (it.toLong() > maxId) maxId = it.toLong() }
                    add(row)
                }
            }
        }
        if (rows.isEmpty()) return

        val varcharCols = jdbcTemplate.queryForList(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND DATA_TYPE LIKE '%CHAR%'",
            table.uppercase()
        ).map { it["COLUMN_NAME"] as String }
        for (col in varcharCols) {
            jdbcTemplate.execute("ALTER TABLE $table ALTER COLUMN $col SET DATA TYPE CHARACTER LARGE OBJECT")
        }

        val insertCols = usable.joinToString(", ")
        val placeholders = usable.joinToString(", ") { "?" }
        val sql = "INSERT INTO $table ($insertCols) VALUES ($placeholders)"

        // Dual-schema bridge: also populate the camelCase mirror columns AT INSERT
        // TIME from their snake twins — the columns are NOT NULL, so a post-update
        // would never get the chance to run. Only mirror columns that actually
        // exist in the target schema; falls back to mirrorDefaults for nulls.
        val mirror = mirrorMaps[table].orEmpty()
            .filterKeys { camel -> camel.uppercase() in targetUpper }
        val usablePlus = usable.toMutableList()
        mirror.keys.forEach { camel -> if (camel !in usablePlus) usablePlus += camel }
        val sqlPlus = if (mirror.isNotEmpty())
            "INSERT INTO $table (${usablePlus.joinToString(", ")}) VALUES (${usablePlus.joinToString(", ") { "?" }})"
        else sql

        jdbcTemplate.batchUpdate(sqlPlus, rows.map { row ->
            val params = arrayOfNulls<Any>(usablePlus.size)
            usablePlus.forEachIndexed { i, col ->
                val sourceCol = mirror[col] ?: col
                val value = row[sourceCol] ?: mirrorDefaults[col]
                params[i] = when (value) {
                    null -> null
                    is Boolean -> if (value) 1 else 0
                    else -> value
                }
            }
            params
        })
        if ("id" in usable && maxId > 0) {
            jdbcTemplate.execute("ALTER TABLE $table ALTER COLUMN id RESTART WITH ${maxId + 1}")
        }
        logger.info("Imported {} rows into {}", rows.size, table)
    }

    /**
     * Dual-schema bridge: the source dump carries snake_case only, but the target
     * schema has BOTH conventions (engine camelCase NOT NULL + Laravel snake_case).
     * After the raw insert, mirror snake → camel so NOT NULL constraints hold and
     * entity reads see the imported rows. Mirrors LegacyColumnMirror's write path.
     */
    private val mirrorMaps: Map<String, Map<String, String>> = buildMap {
        // Full camel↔snake coverage — including the sync bookkeeping fields, which
        // must never be null (Jackson round-trips pulled rows back into the entities).
        val syncFields = mapOf(
            "localId" to "local_id", "centralId" to "central_id",
            "syncStatus" to "sync_status", "contentHash" to "content_hash",
            "syncedAt" to "synced_at", "createdAt" to "created_at", "updatedAt" to "updated_at"
        )
        val countyData = mapOf("countyId" to "county_id", "isPublished" to "is_published") + syncFields
        listOf(
            "county_tourism_attractions", "county_hotels", "county_farms", "county_health_facilities",
            "county_institutions", "county_transport", "county_culture_sites", "county_products"
        ).forEach { put(it, countyData) }
        put(
            "sector_entities", mapOf(
                "countyId" to "county_id", "isPublished" to "is_published",
                "sectorId" to "sector_id", "entityId" to "entity_id",
                "entityType" to "entity_type", "sectorType" to "sector_type",
                "captureStatus" to "capture_status", "languagePrimary" to "language_primary"
            ) + syncFields
        )
        put("sectors", mapOf("isActive" to "is_active", "sortOrder" to "sort_order") + syncFields)
        put("county_sector", mapOf("countyId" to "county_id", "sectorId" to "sector_id"))
        put("exhibitions", mapOf("countyId" to "county_id", "startDate" to "start_date", "endDate" to "end_date", "isFeatured" to "is_featured") + syncFields)
        put("venues", mapOf("isActive" to "is_active", "venueType" to "venue_type"))
        put("booths", mapOf("exhibitionId" to "exhibition_id", "boothNumber" to "booth_number", "maxQuantity" to "max_quantity", "bookedQuantity" to "booked_quantity"))
        put(
            "counties", mapOf(
                "formerProvince" to "former_province", "economicZone" to "economic_zone",
                "isActive" to "is_active", "sceneType" to "scene_type",
                "population2024" to "population_2024", "areaKm2" to "area_km2",
                "mapX" to "map_x", "mapY" to "map_y", "mapZ" to "map_z",
                "iconEmoji" to "icon_emoji", "profileImage" to "profile_image",
                "tourismHighlights" to "tourism_highlights", "warmestMonth" to "warmest_month",
                "coolestMonth" to "coolest_month", "rainySeason" to "rainy_season",
                "drySeason" to "dry_season", "weatherStationId" to "weather_station_id",
                "primarySectors" to "primary_sectors", "weatherTags" to "weather_tags"
            )
        )
    }

    /** Defaults for NOT NULL camel columns when the source value is missing or null. */
    private val mirrorDefaults: Map<String, Any> = mapOf(
        "isActive" to 1, "isPublished" to 0, "isFeatured" to 0,
        "sortOrder" to 0, "maxQuantity" to 0, "bookedQuantity" to 0,
        "syncStatus" to "local", "captureStatus" to "none", "languagePrimary" to "en",
        "sceneType" to "default", "formerProvince" to "", "economicZone" to ""
    )

    private fun normalize(rs: ResultSet, column: String, value: Any?): Any? {
        if (value == null) return null
        if (column.startsWith("is_") && value is Number) return value.toInt() != 0
        return when (value) {
            is Int -> value.toLong()
            is Float -> value.toDouble()
            else -> value
        }
    }

    private val logger = org.slf4j.LoggerFactory.getLogger(CountyDataImporter::class.java)
}
