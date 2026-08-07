package ke.go.kicc.engine.data

import ke.go.kicc.engine.user.User
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.*

@RestController
@RequestMapping("/api/data")
class CountyDataController(private val service: CountyDataService) {

    @GetMapping("/counties")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun counties(): List<County> = service.counties()

    @GetMapping("/public/counties")
    fun publicCounties(): List<Map<String, Any>> = service.counties().filter { it.isActive }.map {
        mapOf("id" to it.id!!, "name" to it.name, "slug" to it.slug)
    }

    @GetMapping("/counties/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun county(@PathVariable id: Long): County = service.county(id)

    @GetMapping("/sectors")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun sectors(): List<Sector> = service.sectors()

    @GetMapping("/sectors/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun sector(@PathVariable id: Long): Sector = service.sector(id)

    @GetMapping("/county-sectors")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun countySectors(@RequestParam countySlug: String): List<CountySectorLink> = service.countySectors(countySlug)

    @PostMapping("/counties")
    @PreAuthorize("hasAuthority('COUNTY_MANAGE')")
    fun createCounty(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, Any?>): County =
        service.createCounty(user, body)

    @PutMapping("/counties/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateCounty(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody body: Map<String, Any?>): County =
        service.updateCounty(user, id, body)

    @DeleteMapping("/counties/{id}")
    @PreAuthorize("hasAuthority('COUNTY_MANAGE')")
    fun deleteCounty(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteCounty(user, id)
        return mapOf("deleted" to "true")
    }

    @PostMapping("/sectors")
    @PreAuthorize("hasAuthority('SECTOR_MANAGE')")
    fun createSector(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, Any?>): Sector =
        service.createSector(user, body)

    @PutMapping("/sectors/{id}")
    @PreAuthorize("hasAuthority('SECTOR_MANAGE')")
    fun updateSector(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody body: Map<String, Any?>): Sector =
        service.updateSector(user, id, body)

    @DeleteMapping("/sectors/{id}")
    @PreAuthorize("hasAuthority('SECTOR_MANAGE')")
    fun deleteSector(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteSector(user, id)
        return mapOf("deleted" to "true")
    }

    @PostMapping("/county-sectors")
    @PreAuthorize("hasAuthority('SECTOR_MANAGE')")
    fun createCountySector(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, Any?>): CountySectorLink =
        service.createCountySectorLink(user, body)

    @DeleteMapping("/county-sectors/{id}")
    @PreAuthorize("hasAuthority('SECTOR_MANAGE')")
    fun deleteCountySector(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteCountySectorLink(user, id)
        return mapOf("deleted" to "true")
    }

    @GetMapping("/product-categories")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun productCategories(): List<ProductCategory> = service.productCategories()

    @PostMapping("/product-categories")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun createProductCategory(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, Any?>): ProductCategory =
        service.createProductCategory(user, body)

    @PutMapping("/product-categories/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateProductCategory(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody body: Map<String, Any?>): ProductCategory =
        service.updateProductCategory(user, id, body)

    @DeleteMapping("/product-categories/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun deleteProductCategory(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteProductCategory(user, id)
        return mapOf("deleted" to "true")
    }

    @GetMapping("/{resource}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun list(
        @PathVariable resource: String,
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) countySlug: String?
    ): List<*> = service.listScoped(resource, user, countySlug)

    @GetMapping("/{resource}/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun get(
        @PathVariable resource: String,
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long
    ): Any = service.getScoped(resource, user, id)

    @PostMapping("/{resource}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun create(
        @PathVariable resource: String,
        @AuthenticationPrincipal user: User,
        @RequestBody body: Map<String, Any?>
    ): Any = service.createScoped(resource, user, body)

    @PutMapping("/{resource}/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun update(
        @PathVariable resource: String,
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Any = service.updateScoped(resource, user, id, body)

    @DeleteMapping("/{resource}/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun delete(
        @PathVariable resource: String,
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long
    ): Map<String, String> {
        service.deleteScoped(resource, user, id)
        return mapOf("deleted" to "true")
    }

    @PatchMapping("/{resource}/{id}/publish")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun publish(
        @PathVariable resource: String,
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Any = service.publishScoped(resource, user, id, body["published"] as? Boolean ?: true)

    @GetMapping("/exhibitions")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun exhibitions(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) countySlug: String?
    ): List<Exhibition> = service.exhibitions(user, countySlug)

    @GetMapping("/exhibitions/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun exhibition(@AuthenticationPrincipal user: User, @PathVariable id: Long): Exhibition =
        service.exhibition(user, id)

    @PostMapping("/exhibitions")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun createExhibition(
        @AuthenticationPrincipal user: User,
        @RequestBody body: Map<String, Any?>
    ): Exhibition = service.createExhibition(user, body)

    @PutMapping("/exhibitions/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateExhibition(
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Exhibition = service.updateExhibition(user, id, body)

    @DeleteMapping("/exhibitions/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun deleteExhibition(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteExhibition(user, id)
        return mapOf("deleted" to "true")
    }

    @GetMapping("/venues")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun venues(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) countySlug: String?
    ): List<Venue> = service.venues(user, countySlug)

    @GetMapping("/venues/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun venue(@AuthenticationPrincipal user: User, @PathVariable id: Long): Venue = service.venue(user, id)

    @PostMapping("/venues")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun createVenue(
        @AuthenticationPrincipal user: User,
        @RequestBody body: Map<String, Any?>
    ): Venue = service.createVenue(user, body)

    @PutMapping("/venues/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateVenue(
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Venue = service.updateVenue(user, id, body)

    @DeleteMapping("/venues/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun deleteVenue(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteVenue(user, id)
        return mapOf("deleted" to "true")
    }

    @GetMapping("/booths")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun booths(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) exhibitionId: Long?
    ): List<Booth> = service.booths(user, exhibitionId)

    @GetMapping("/booths/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun booth(@AuthenticationPrincipal user: User, @PathVariable id: Long): Booth = service.booth(user, id)

    @PostMapping("/booths")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun createBooth(
        @AuthenticationPrincipal user: User,
        @RequestBody body: Map<String, Any?>
    ): Booth = service.createBooth(user, body)

    @PutMapping("/booths/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun updateBooth(
        @AuthenticationPrincipal user: User,
        @PathVariable id: Long,
        @RequestBody body: Map<String, Any?>
    ): Booth = service.updateBooth(user, id, body)

    @DeleteMapping("/booths/{id}")
    @PreAuthorize("hasAuthority('CONTENT_MANAGE')")
    fun deleteBooth(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.deleteBooth(user, id)
        return mapOf("deleted" to "true")
    }
}
