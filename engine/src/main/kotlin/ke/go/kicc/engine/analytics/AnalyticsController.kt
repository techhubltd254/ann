package ke.go.kicc.engine.analytics

import ke.go.kicc.engine.data.*
import ke.go.kicc.engine.payment.PaymentRepository
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.web.bind.annotation.GetMapping
import org.springframework.web.bind.annotation.RequestMapping
import org.springframework.web.bind.annotation.RequestParam
import org.springframework.web.bind.annotation.RestController
import java.time.Instant

@RestController
@RequestMapping("/api/analytics")
class AnalyticsController(
    private val bookingRepository: BookingRepository,
    private val paymentRepository: PaymentRepository,
    private val countyRepository: CountyRepository,
    private val complaintRepository: ke.go.kicc.engine.complaints.ComplaintRepository,
    private val attractionRepository: AttractionRepository,
    private val hotelRepository: HotelRepository,
    private val farmRepository: FarmRepository,
    private val productRepository: ProductRepository
) {
    data class AnalyticsResponse(
        val bookings: Map<String, Int>,
        val payments: Map<String, Any>,
        val contentByCounty: List<Map<String, Any>>,
        val complaints: Map<String, Any>,
        val totalRevenue: Double
    )

    @GetMapping
    @PreAuthorize("hasAuthority('ANALYTICS_VIEW')")
    fun overview(): AnalyticsResponse {
        val allBookings = bookingRepository.findAll()
        val bookingByStatus = allBookings.groupBy { it.status }.mapValues { it.value.size }
        val allPayments = paymentRepository.findAllByOrderByIdDesc()
        val paymentByStatus = allPayments.groupBy { it.status }.mapValues { it.value.size }
        val paymentByMethod = allPayments.groupBy { it.method }.mapValues { it.value.size }
        val totalRevenue = allPayments.filter { it.status == "SUCCESS" || it.status == "PAID" }.sumOf { it.amount }
        val allComplaints = complaintRepository.findAllByOrderByIdDesc()
        val complaintByStatus = allComplaints.groupBy { it.status }.mapValues { it.value.size }
        val complaintByPriority = allComplaints.groupBy { it.priority }.mapValues { it.value.size }
        val counties = countyRepository.findAll()
        val content = counties.sortedBy { it.name }.map { c ->
            val id = c.id ?: return@map emptyMap()
            mapOf(
                "county" to c.name,
                "slug" to c.slug,
                "attractions" to attractionRepository.findAllByCountyIdInOrderByIdAsc(listOf(id)).size,
                "hotels" to hotelRepository.findAllByCountyIdInOrderByIdAsc(listOf(id)).size,
                "farms" to farmRepository.findAllByCountyIdInOrderByIdAsc(listOf(id)).size,
                "products" to productRepository.findAllByCountyIdInOrderByIdAsc(listOf(id)).size
            )
        }
        return AnalyticsResponse(
            bookings = bookingByStatus,
            payments = mapOf("byStatus" to paymentByStatus, "byMethod" to paymentByMethod),
            contentByCounty = content.filter { it.isNotEmpty() },
            complaints = mapOf("byStatus" to complaintByStatus, "byPriority" to complaintByPriority),
            totalRevenue = totalRevenue
        )
    }
}
