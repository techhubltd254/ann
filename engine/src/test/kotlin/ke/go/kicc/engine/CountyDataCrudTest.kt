package ke.go.kicc.engine

import com.fasterxml.jackson.databind.ObjectMapper
import org.junit.jupiter.api.Assertions.*
import org.junit.jupiter.api.Test
import org.springframework.beans.factory.annotation.Autowired
import org.springframework.boot.test.autoconfigure.web.servlet.AutoConfigureMockMvc
import org.springframework.boot.test.context.SpringBootTest
import org.springframework.http.HttpHeaders
import org.springframework.http.MediaType
import org.springframework.test.context.ActiveProfiles
import org.springframework.test.web.servlet.MockMvc
import org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.jsonPath
import org.springframework.test.web.servlet.result.MockMvcResultMatchers.status

@SpringBootTest
@AutoConfigureMockMvc
@ActiveProfiles("test")
class CountyDataCrudTest {

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

    private fun loginAs(email: String, password: String) = login(email, password)

    private fun userId(token: String, email: String): Long {
        val res = mockMvc.perform(get("/api/admin/users").headers(bearer(token))).andReturn()
        return objectMapper.readTree(res.response.contentAsString).find { it["email"].asText() == email }!!["id"].asLong()
    }

    @Test
    fun importedRealData() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val res = mockMvc.perform(get("/api/data/counties").headers(bearer(kicc)))
            .andExpect(status().isOk).andReturn()
        val counties = objectMapper.readTree(res.response.contentAsString)
        assertEquals(47, counties.size())
        assertNotNull(counties.find { it["slug"].asText() == "kilifi" })
    }

    @Test
    fun countyAdminSeesOnlyOwnCountyData() {
        val county = login("county@kicc.go.ke", "county@2026")
        val res = mockMvc.perform(get("/api/data/attractions").headers(bearer(county)))
            .andExpect(status().isOk).andReturn()
        val attractions = objectMapper.readTree(res.response.contentAsString)
        assertTrue(attractions.size() > 0, "Kilifi should have attractions")
        attractions.forEach { assertEquals(3, it["countyId"].asLong(), "all rows must belong to Kilifi (id 3)") }
    }

    @Test
    fun countyAdminCannotTouchOtherCountyData() {
        val county = login("county@kicc.go.ke", "county@2026")
        val kicc = login("admin@kicc.go.ke", "Admin@2026")

        val mombasaRes = mockMvc.perform(get("/api/data/attractions?countySlug=mombasa").headers(bearer(kicc)))
            .andExpect(status().isOk).andReturn()
        val mombasaAttraction = objectMapper.readTree(mombasaRes.response.contentAsString)[0]["id"].asLong()

        mockMvc.perform(
            put("/api/data/attractions/$mombasaAttraction")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"name":"Hijacked"}""")
        ).andExpect(status().isNotFound)

        mockMvc.perform(
            delete("/api/data/attractions/$mombasaAttraction")
                .headers(bearer(county))
        ).andExpect(status().isNotFound)
    }

    @Test
    fun kiccCreatesInAnyCounty() {
        val kicc = login("admin@kicc.go.ke", "Admin@2026")
        val res = mockMvc.perform(
            post("/api/data/attractions")
                .headers(bearer(kicc))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"name":"Test Monolith","category":"heritage","description":"imported test","countyId":1,"latitude":-4.0435,"longitude":39.6682}"""
                )
        ).andExpect(status().isOk).andReturn()
        val created = objectMapper.readTree(res.response.contentAsString)
        assertEquals(1, created["countyId"].asLong())
        assertNotNull(created["id"].asLong())
    }

    @Test
    fun countyCreateForcesOwnCountyAndBlocksSmuggling() {
        val county = login("county@kicc.go.ke", "county@2026")
        val res = mockMvc.perform(
            post("/api/data/attractions")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content(
                    """{"name":"Smuggled To Mombasa","category":"heritage","countyId":1,"id":9999,"syncStatus":"hacked"}"""
                )
        ).andExpect(status().isOk).andReturn()
        val created = objectMapper.readTree(res.response.contentAsString)
        assertEquals(3, created["countyId"].asLong(), "countyId must be forced to the actor's county")
        assertNotEquals(9999, created["id"].asLong(), "id must not be taken from the body")
        assertEquals("local", created["syncStatus"].asText(), "sync fields must not be editable")
    }

    @Test
    fun updateCannotMoveCounty() {
        val county = login("county@kicc.go.ke", "county@2026")
        val listRes = mockMvc.perform(get("/api/data/farms").headers(bearer(county)))
            .andExpect(status().isOk).andReturn()
        val farmId = objectMapper.readTree(listRes.response.contentAsString)[0]["id"].asLong()

        mockMvc.perform(
            put("/api/data/farms/$farmId")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"countyId":1,"name":"Renamed Farm"}""")
        ).andExpect(status().isOk).andExpect(jsonPath("$.countyId").value(3))
            .andExpect(jsonPath("$.name").value("Renamed Farm"))
    }

    @Test
    fun publishToggleWorks() {
        val county = login("county@kicc.go.ke", "county@2026")
        val listRes = mockMvc.perform(get("/api/data/hotels").headers(bearer(county)))
            .andExpect(status().isOk).andReturn()
        val hotel = objectMapper.readTree(listRes.response.contentAsString)[0]
        val id = hotel["id"].asLong()

        mockMvc.perform(
            patch("/api/data/hotels/$id/publish")
                .headers(bearer(county))
                .contentType(MediaType.APPLICATION_JSON)
                .content("""{"published":true}""")
        ).andExpect(status().isOk).andExpect(jsonPath("$.isPublished").value(true))
    }

    @Test
    fun exhibitorCannotAccessCountyDataWithoutCountyScope() {
        // seeded exhibitor has no countySlug -> no county data access
        val exhibitor = login("exhibitor@kicc.go.ke", "exhibitor@2026")
        mockMvc.perform(get("/api/data/attractions").headers(bearer(exhibitor)))
            .andExpect(status().isForbidden)
    }

    @Test
    fun sectorEntitiesListableByCounty() {
        val county = login("county@kicc.go.ke", "county@2026")
        val res = mockMvc.perform(get("/api/data/sector-entities").headers(bearer(county)))
            .andExpect(status().isOk).andReturn()
        assertTrue(objectMapper.readTree(res.response.contentAsString).size() > 0)
    }

    @Test
    fun venuesAndBoothsScoped() {
        val county = login("county@kicc.go.ke", "county@2026")
        val venuesRes = mockMvc.perform(get("/api/data/venues").headers(bearer(county)))
            .andExpect(status().isOk).andReturn()
        val venues = objectMapper.readTree(venuesRes.response.contentAsString)
        venues.forEach { assertEquals("Kilifi", it["county"].asText(), "county venues scoped to own county") }
    }
}
