package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import org.springframework.beans.factory.annotation.Autowired
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc
import org.springframework.boot.test.context.SpringBootTest
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.test.annotation.DirtiesContext
import org.springframework.test.context.ActiveProfiles
import org.springframework.test.web.servlet.MockMvc
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
@DirtiesContext(classMode = DirtiesContext.ClassMode.AFTER_CLASS)
class CountyAdminFlowTest {

    @Autowired
    lateinit var mockMvc: MockMvc

    @Autowired
    lateinit var objectMapper: ObjectMapper

    private fun bearer(token: String) = HttpHeaders().apply { set(HttpHeaders.AUTHORIZATION, "Bearer $token") }

    private fun login(email: String, password: String): String {
        val res = mockMvc.perform(
            post("/api/auth/login")
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"email":"$email","password":"$password"}""")
        ).andExpect(status().isOk).andReturn()
        return objectMapper.readTree(res.response.contentAsString)["accessToken"].asText()
    }

    @Test
    fun kiccCreatesCounty() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val res = mockMvc.perform(
            post("/api/data/counties")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Test County","slug":"test-county","capital":"Test City","code":"99","formerProvince":"Coast","economicZone":"Test","tagline":"Testing","description":"A test county"}""")
        ).andExpect(status().isOk).andReturn()
        val county = objectMapper.readTree(res.response.contentAsString)
        assertEquals("test-county", county["slug"].asText())
        assertNotNull(county["id"].asLong())
        val id = county["id"].asLong()
        mockMvc.perform(delete("/api/data/counties/$id").headers(bearer(kicc)))
            .andExpect(status().isOk)
    }

    @Test
    fun countyAdminEditsOwnCountyProfile() {
        val county = login("county@kicc.go.ke", "county@2026")
        val countiesRes = mockMvc.perform(get("/api/data/counties").headers(bearer(county)))
            .andExpect(status().isOk).andReturn()
        val kilifi = objectMapper.readTree(countiesRes.response.contentAsString).find { it["slug"].asText() == "kilifi" }!!
        val id = kilifi["id"].asLong()

        mockMvc.perform(
            put("/api/data/counties/$id")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"tagline":"Updated Kilifi Tagline","description":"Updated description"}""")
        ).andExpect(status().isOk)
            .andExpect(jsonPath("$.tagline").value("Updated Kilifi Tagline"))
            .andExpect(jsonPath("$.slug").value("kilifi"))
    }

    @Test
    fun countyAdminCannotEditOtherCounty() {
        val county = login("county@kicc.go.ke", "county@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val countiesRes = mockMvc.perform(get("/api/data/counties").headers(bearer(kicc)))
            .andExpect(status().isOk).andReturn()
        val mombasa = objectMapper.readTree(countiesRes.response.contentAsString).find { it["slug"].asText() == "mombasa" }!!
        val id = mombasa["id"].asLong()

        mockMvc.perform(
            put("/api/data/counties/$id")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"tagline":"Hijacked"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun exhibitorCannotEditCounty() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val countiesRes = mockMvc.perform(get("/api/data/counties").headers(bearer(kicc)))
            .andExpect(status().isOk).andReturn()
        val kilifi = objectMapper.readTree(countiesRes.response.contentAsString).find { it["slug"].asText() == "kilifi" }!!
        mockMvc.perform(
            put("/api/data/counties/${kilifi["id"].asLong()}")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"tagline":"Hacked"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun countyTierCannotCreateCounty() {
        val county = login("county@kicc.go.ke", "county@2026")
        mockMvc.perform(
            post("/api/data/counties")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"County","slug":"county","capital":"C","code":"00","formerProvince":"N/A","economicZone":"N/A"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun kiccDeletesCountyWithDependentsBlocked() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val kilifi = objectMapper.readTree(
            mockMvc.perform(get("/api/data/counties").headers(bearer(kicc)))
                .andExpect(status().isOk).andReturn().response.contentAsString
        ).find { it["slug"].asText() == "kilifi" }!!
        val id = kilifi["id"].asLong()

        mockMvc.perform(delete("/api/data/counties/$id").headers(bearer(kicc)))
            .andExpect(status().isConflict)
    }

    @Test
    fun kiccDeletesEmptyCounty() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val createRes = mockMvc.perform(
            post("/api/data/counties")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Temp County","slug":"temp-county","capital":"Temp","code":"98","formerProvince":"Coast","economicZone":"Temp"}""")
        ).andExpect(status().isOk).andReturn()
        val id = objectMapper.readTree(createRes.response.contentAsString)["id"].asLong()

        mockMvc.perform(delete("/api/data/counties/$id").headers(bearer(kicc)))
            .andExpect(status().isOk)
            .andExpect(jsonPath("$.deleted").value("true"))

        mockMvc.perform(get("/api/data/counties/$id").headers(bearer(kicc)))
            .andExpect(status().isNotFound)
    }

    @Test
    fun kiccCreatesAndUpdatesSector() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val createRes = mockMvc.perform(
            post("/api/data/sectors")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Test Sector","slug":"test-sector","code":"TS01","emoji":"🔬","description":"A test","sortOrder":5}""")
        ).andExpect(status().isOk).andReturn()
        val id = objectMapper.readTree(createRes.response.contentAsString)["id"].asLong()

        mockMvc.perform(
            put("/api/data/sectors/$id")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Updated Sector","emoji":"🧪","sortOrder":10}""")
        ).andExpect(status().isOk)
            .andExpect(jsonPath("$.name").value("Updated Sector"))
            .andExpect(jsonPath("$.emoji").value("🧪"))
            .andExpect(jsonPath("$.sortOrder").value(10))
    }

    @Test
    fun nationalCannotCreateSector() {
        // By design (2026-08): the NATIONAL tier is independent of the county sector
        // structure — national content lives in /api/national/*, not /api/data/sectors.
        val national = login("national@kicc.go.ke", "national@2026")
        mockMvc.perform(
            post("/api/data/sectors")
                .headers(bearer(national))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"National Sector","slug":"national-sector","code":"NS","emoji":"🏛️"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun countyTierCannotCreateSector() {
        val county = login("county@kicc.go.ke", "county@2026")
        mockMvc.perform(
            post("/api/data/sectors")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"County Sector","slug":"county-sector","code":"CS","emoji":"🏛️"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun sectorDeleteBlockedWhenLinked() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val createRes = mockMvc.perform(
            post("/api/data/sectors")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Linkable Sector","slug":"linkable-sector","code":"LS01"}""")
        ).andExpect(status().isOk).andReturn()
        val sectorId = objectMapper.readTree(createRes.response.contentAsString)["id"].asLong()

        val countiesRes = mockMvc.perform(get("/api/data/counties").headers(bearer(kicc)))
            .andExpect(status().isOk).andReturn()
        val countyId = objectMapper.readTree(countiesRes.response.contentAsString)[0]["id"].asLong()

        val linkRes = mockMvc.perform(
            post("/api/data/county-sectors")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"countyId":$countyId,"sectorId":$sectorId}""")
        ).andExpect(status().isOk).andReturn()
        val linkId = objectMapper.readTree(linkRes.response.contentAsString)["id"].asLong()

        mockMvc.perform(delete("/api/data/sectors/$sectorId").headers(bearer(kicc)))
            .andExpect(status().isConflict)

        mockMvc.perform(delete("/api/data/county-sectors/$linkId").headers(bearer(kicc)))
            .andExpect(status().isOk)

        mockMvc.perform(delete("/api/data/sectors/$sectorId").headers(bearer(kicc)))
            .andExpect(status().isOk)
    }

    @Test
    fun kiccTransfersResourceBetweenCounties() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val mombasa = objectMapper.readTree(
            mockMvc.perform(get("/api/data/counties").headers(bearer(kicc)))
                .andExpect(status().isOk).andReturn().response.contentAsString
        ).find { it["slug"].asText() == "mombasa" }!!
        val mombasaId = mombasa["id"].asLong()

        val kilifiAttractions = mockMvc.perform(
            get("/api/data/attractions?countySlug=kilifi").headers(bearer(kicc))
        ).andExpect(status().isOk).andReturn()
        val kilifiAttraction = objectMapper.readTree(kilifiAttractions.response.contentAsString)[0]
        val attractionId = kilifiAttraction["id"].asLong()
        assertEquals(3, kilifiAttraction["countyId"].asLong())

        mockMvc.perform(
            put("/api/data/attractions/$attractionId")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"countyId":$mombasaId,"name":"Moved Attraction"}""")
        ).andExpect(status().isOk)
            .andExpect(jsonPath("$.countyId").value(mombasaId))
            .andExpect(jsonPath("$.name").value("Moved Attraction"))

        mockMvc.perform(
            put("/api/data/attractions/$attractionId")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"countyId":3,"name":"Restored Attraction"}""")
        ).andExpect(status().isOk)
            .andExpect(jsonPath("$.countyId").value(3))
    }

    @Test
    fun countyCannotTransferResource() {
        val county = login("county@kicc.go.ke", "county@2026")
        val kilifiFarms = mockMvc.perform(
            get("/api/data/farms").headers(bearer(county))
        ).andExpect(status().isOk).andReturn()
        val farm = objectMapper.readTree(kilifiFarms.response.contentAsString)[0]
        val farmId = farm["id"].asLong()
        assertEquals(3, farm["countyId"].asLong())

        mockMvc.perform(
            put("/api/data/farms/$farmId")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"countyId":1,"name":"Smuggled Farm"}""")
        ).andExpect(status().isOk)
            .andExpect(jsonPath("$.countyId").value(3))
    }

    @Test
    fun exhibitorCannotCreateCountyData() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        mockMvc.perform(
            post("/api/data/attractions")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Hack Entry","category":"heritage","countyId":1}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun exhibitorCannotGetSingleCountyData() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val kiccAttractions = mockMvc.perform(
            get("/api/data/attractions").headers(bearer(kicc))
        ).andExpect(status().isOk).andReturn()
        val first = objectMapper.readTree(kiccAttractions.response.contentAsString)[0]
        val id = first["id"].asLong()

        mockMvc.perform(get("/api/data/attractions/$id").headers(bearer(exhibitor)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun exhibitorCannotUpdateCountyData() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val kiccRes = mockMvc.perform(
            get("/api/data/attractions").headers(bearer(kicc))
        ).andExpect(status().isOk).andReturn()
        val first = objectMapper.readTree(kiccRes.response.contentAsString)[0]

        mockMvc.perform(
            put("/api/data/attractions/${first["id"].asLong()}")
                .headers(bearer(exhibitor))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Hacked Name"}""")
        ).andExpect(status().isForbidden)
    }

    @Test
    fun exhibitorCannotDeleteCountyData() {
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val kiccRes = mockMvc.perform(
            get("/api/data/attractions").headers(bearer(kicc))
        ).andExpect(status().isOk).andReturn()
        val first = objectMapper.readTree(kiccRes.response.contentAsString)[0]

        mockMvc.perform(delete("/api/data/attractions/${first["id"].asLong()}").headers(bearer(exhibitor)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun privilegesReflectHierarchy() {
        val me = { token: String ->
            objectMapper.readTree(
                mockMvc.perform(get("/api/me").headers(bearer(token)))
                    .andExpect(status().isOk).andReturn().response.contentAsString
            )
        }
        val kicc = me(login("admin@kicc.go.ke", "Admin@2026"))
        assertTrue(kicc["privileges"].toString().contains("COUNTY_MANAGE"))
        assertTrue(kicc["privileges"].toString().contains("SECTOR_MANAGE"))

        val national = me(login("national@kicc.go.ke", "national@2026"))
        assertFalse(national["privileges"].toString().contains("COUNTY_MANAGE"), "NATIONAL must not have COUNTY_MANAGE")
        assertFalse(national["privileges"].toString().contains("SECTOR_MANAGE"), "NATIONAL must not have SECTOR_MANAGE (independent of sectors)")
        assertTrue(national["privileges"].toString().contains("CONTENT_MANAGE"), "NATIONAL must have CONTENT_MANAGE")
        assertTrue(national["privileges"].toString().contains("ANALYTICS_VIEW"), "NATIONAL must have ANALYTICS_VIEW")

        val county = me(login("county@kicc.go.ke", "county@2026"))
        assertFalse(county["privileges"].toString().contains("COUNTY_MANAGE"))
        assertFalse(county["privileges"].toString().contains("SECTOR_MANAGE"))

        val exhibitor = me(login("exhibitor@kicc.go.ke", "exhibitor@2026"))
        assertFalse(exhibitor["privileges"].toString().contains("COUNTY_MANAGE"))
        assertFalse(exhibitor["privileges"].toString().contains("SECTOR_MANAGE"))
    }
}