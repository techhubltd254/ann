package ke.go.kicc.engine.payment

import jakarta.persistence.*
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.data.Booking
import ke.go.kicc.engine.data.BookingRepository
import ke.go.kicc.engine.data.BoothRepository
import ke.go.kicc.engine.data.CountyRepository
import ke.go.kicc.engine.data.ExhibitionRepository
import ke.go.kicc.engine.notify.NotificationService
import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.rbac.ScopeResolver
import ke.go.kicc.engine.user.User
import org.springframework.beans.factory.annotation.Value
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.scheduling.annotation.Scheduled
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Component
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.bind.annotation.*
import org.springframework.web.client.RestClient
import org.springframework.web.server.ResponseStatusException
import java.net.URI
import java.security.SecureRandom
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.*

@Entity
@Table(name = "payments")
class Payment(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var bookingId: Long = 0,

    @Column(nullable = false)
    var amount: Double = 0.0,

    @Column(nullable = false)
    var currency: String = "KES",

    @Column(nullable = false)
    var provider: String = "mock",

    @Column(nullable = false)
    var method: String = "mock",

    @Column(nullable = false)
    var status: String = "INITIATED",

    var providerRef: String? = null,
    var phone: String? = null,
    var description: String? = null,
    var failureReason: String? = null,

    @Column(length = 4000)
    var rawResponse: String? = null,

    @Column(nullable = false)
    var initiatedByUserId: Long = 0,

    @Column(nullable = false)
    var initiatedAt: String = "",

    var completedAt: String? = null
)

interface PaymentRepository : JpaRepository<Payment, Long> {
    fun findAllByOrderByIdDesc(): List<Payment>

    fun findByBookingIdInOrderByIdDesc(bookingIds: List<Long>): List<Payment>
}

@Entity
@Table(name = "settlements")
class Settlement(
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,

    @Column(nullable = false)
    var provider: String = "",

    var periodStart: String = "",
    var periodEnd: String = "",

    @Column(nullable = false)
    var totalAmount: Double = 0.0,

    @Column(nullable = false)
    var paymentCount: Int = 0,

    @Column(nullable = false)
    var status: String = "PENDING",

    @Column(nullable = false)
    var createdAt: String = "",

    var approvedAt: String? = null,
    var approvedByUserId: Long? = null,
    var paidAt: String? = null,
    var paidByUserId: Long? = null,
    var payoutRef: String? = null,
    var failureReason: String? = null
)

interface SettlementRepository : JpaRepository<Settlement, Long> {
    fun findAllByOrderByIdDesc(): List<Settlement>
}

data class PaymentView(
    val id: Long,
    val bookingId: Long,
    val bookingReference: String?,
    val amount: Double,
    val currency: String,
    val provider: String,
    val method: String,
    val status: String,
    val providerRef: String?,
    val phone: String?,
    val description: String?,
    val failureReason: String?,
    val initiatedByUserId: Long,
    val initiatedAt: String,
    val completedAt: String?,
    val revenueShare: RevenueShareView?
) {
    companion object {
        fun from(p: Payment, reference: String?, share: RevenueShareView?) = PaymentView(
            id = p.id!!,
            bookingId = p.bookingId,
            bookingReference = reference,
            amount = p.amount,
            currency = p.currency,
            provider = p.provider,
            method = p.method,
            status = p.status,
            providerRef = p.providerRef,
            phone = p.phone,
            description = p.description,
            failureReason = p.failureReason,
            initiatedByUserId = p.initiatedByUserId,
            initiatedAt = p.initiatedAt,
            completedAt = p.completedAt,
            revenueShare = share
        )
    }
}

data class RevenueShareView(
    val countyId: Long?,
    val kiccAmount: Double,
    val countyAmount: Double,
    val kiccShare: Double,
    val countyShare: Double
)

data class SettlementView(
    val id: Long,
    val provider: String,
    val periodStart: String,
    val periodEnd: String,
    val totalAmount: Double,
    val paymentCount: Int,
    val status: String,
    val createdAt: String,
    val approvedAt: String?,
    val approvedByUserId: Long?,
    val paidAt: String?,
    val paidByUserId: Long?,
    val payoutRef: String?,
    val failureReason: String?
) {
    companion object {
        fun from(s: Settlement) = SettlementView(
            id = s.id!!,
            provider = s.provider,
            periodStart = s.periodStart,
            periodEnd = s.periodEnd,
            totalAmount = s.totalAmount,
            paymentCount = s.paymentCount,
            status = s.status,
            createdAt = s.createdAt,
            approvedAt = s.approvedAt,
            approvedByUserId = s.approvedByUserId,
            paidAt = s.paidAt,
            paidByUserId = s.paidByUserId,
            payoutRef = s.payoutRef,
            failureReason = s.failureReason
        )
    }
}

data class StatementLineView(
    val paymentId: Long,
    val bookingReference: String?,
    val amount: Double,
    val currency: String,
    val paymentStatus: String,
    val completedAt: String?
)

data class CountyStatementView(
    val countySlug: String,
    val countyName: String,
    val periodStart: String,
    val periodEnd: String,
    val paymentCount: Int,
    val grossAmount: Double,
    val kiccAmount: Double,
    val countyAmount: Double,
    val kiccShare: Double,
    val countyShare: Double,
    val payments: List<StatementLineView>
)

data class InitiateRequest(
    val amount: Double,
    val currency: String,
    val phone: String?,
    val accountReference: String,
    val description: String
)

data class InitiationResult(val providerRef: String?, val status: String, val raw: String?)

data class VerifyResult(val status: String, val raw: String?)

interface PaymentProvider {
    val name: String

    fun initiate(req: InitiateRequest): InitiationResult

    fun verify(providerRef: String, amount: Double): VerifyResult

    fun refund(providerRef: String): Boolean
}

@Component
class MockPaymentProvider : PaymentProvider {

    private val random = SecureRandom()

    override val name = "mock"

    override fun initiate(req: InitiateRequest): InitiationResult {
        val ref = "MOCK-" + (1..8).map { random.nextInt(10) }.joinToString("")
        val status = when {
            req.phone?.endsWith("000") == true -> "FAILED"
            req.phone?.endsWith("WAIT") == true -> "PENDING"
            else -> "SUCCESS"
        }
        val raw = """{"simulated":true,"status":"$status","phone":"${req.phone}"}"""
        return InitiationResult(ref, status, raw)
    }

    override fun verify(providerRef: String, amount: Double): VerifyResult =
        VerifyResult("SUCCESS", """{"simulated":true,"verified":true}""")

    override fun refund(providerRef: String): Boolean = true
}

@Component
class MpesaProvider(
    @Value("\${kicc.mpesa.base-url}") private val baseUrl: String,
    @Value("\${kicc.mpesa.consumer-key}") private val consumerKey: String,
    @Value("\${kicc.mpesa.consumer-secret}") private val consumerSecret: String,
    @Value("\${kicc.mpesa.passkey}") private val passkey: String,
    @Value("\${kicc.mpesa.shortcode}") private val shortcode: String,
    @Value("\${kicc.mpesa.callback-url}") private val callbackUrl: String
) : PaymentProvider {

    private val client: RestClient = RestClient.builder().build()

    override val name = "mpesa"

    private fun checkConfigured() {
        if (consumerKey.isBlank() || consumerSecret.isBlank() || passkey.isBlank()) {
            throw ResponseStatusException(HttpStatus.SERVICE_UNAVAILABLE, "M-PESA is not configured (set MPESA_CONSUMER_KEY/SECRET/PASSKEY)")
        }
    }

    private fun accessToken(): String {
        val res = client.post()
            .uri(URI.create("$baseUrl/oauth/v1/generate?grant_type=client_credentials"))
            .header("Authorization", "Basic " + Base64.getEncoder().encodeToString("$consumerKey:$consumerSecret".toByteArray()))
            .retrieve()
            .body(String::class.java) ?: "{}"
        val token = res.replace("\"", "").split(":").lastOrNull() ?: ""
        if (token.isBlank()) throw ResponseStatusException(HttpStatus.BAD_GATEWAY, "M-PESA auth failed")
        return token
    }

    private fun timestamp(): String = DateTimeFormatter.ofPattern("yyyyMMddHHmmss")
        .format(Instant.now().atZone(ZoneOffset.UTC))

    private fun password(): String = Base64.getEncoder()
        .encodeToString("$shortcode$passkey${timestamp()}".toByteArray())

    override fun initiate(req: InitiateRequest): InitiationResult {
        checkConfigured()
        val body = mapOf(
            "BusinessShortCode" to shortcode,
            "Password" to password(),
            "Timestamp" to timestamp(),
            "TransactionType" to "CustomerPayBillOnline",
            "Amount" to req.amount.toInt().toString(),
            "PartyA" to (req.phone ?: ""),
            "PartyB" to shortcode,
            "PhoneNumber" to (req.phone ?: ""),
            "CallBackURL" to callbackUrl,
            "AccountReference" to req.accountReference.take(12),
            "TransactionDesc" to req.description.take(13)
        )
        val res = client.post()
            .uri(URI.create("$baseUrl/mpesa/stkpush/v1/processrequest"))
            .header("Authorization", "Bearer ${accessToken()}")
            .header("Content-Type", "application/json")
            .body(body)
            .retrieve()
            .body(String::class.java) ?: "{}"
        val checkoutId = Regex("\"CheckoutRequestID\"\\s*:\\s*\"([^\"]+)\"").find(res)?.groupValues?.get(1)
        if (checkoutId == null) {
            throw ResponseStatusException(HttpStatus.BAD_GATEWAY, "M-PESA STK push failed: $res")
        }
        return InitiationResult(checkoutId, "PENDING", res)
    }

    override fun verify(providerRef: String, amount: Double): VerifyResult {
        checkConfigured()
        val body = mapOf(
            "BusinessShortCode" to shortcode,
            "Password" to password(),
            "Timestamp" to timestamp(),
            "CheckoutRequestID" to providerRef
        )
        val res = client.post()
            .uri(URI.create("$baseUrl/mpesa/stkpushquery/v1/query"))
            .header("Authorization", "Bearer ${accessToken()}")
            .header("Content-Type", "application/json")
            .body(body)
            .retrieve()
            .body(String::class.java) ?: "{}"
        val code = Regex("\"ResultCode\"\\s*:\\s*(\\d+)").find(res)?.groupValues?.get(1)
        return VerifyResult(if (code == "0") "SUCCESS" else "FAILED", res)
    }

    override fun refund(providerRef: String): Boolean = true
}

@Component
class StripeProvider(
    @Value("\${kicc.stripe.secret-key}") private val secretKey: String
) : PaymentProvider {

    private val client: RestClient = RestClient.builder().build()

    override val name = "stripe"

    private fun checkConfigured() {
        if (secretKey.isBlank()) {
            throw ResponseStatusException(HttpStatus.SERVICE_UNAVAILABLE, "Stripe is not configured (set STRIPE_SECRET_KEY)")
        }
    }

    override fun initiate(req: InitiateRequest): InitiationResult {
        checkConfigured()
        val body = "amount=${(req.amount * 100).toLong()}&currency=${req.currency.lowercase()}&description=${req.description}&confirm=true&automatic_payment_methods[enabled]=true"
        val res = client.post()
            .uri(URI.create("https://api.stripe.com/v1/payment_intents"))
            .header("Authorization", "Bearer $secretKey")
            .header("Content-Type", "application/x-www-form-urlencoded")
            .body(body)
            .retrieve()
            .body(String::class.java) ?: "{}"
        val id = Regex("\"id\"\\s*:\\s*\"([^\"]+)\"").find(res)?.groupValues?.get(1)
            ?: throw ResponseStatusException(HttpStatus.BAD_GATEWAY, "Stripe create failed: $res")
        val status = Regex("\"status\"\\s*:\\s*\"([^\"]+)\"").find(res)?.groupValues?.get(1) ?: "pending"
        return InitiationResult(id, if (status == "succeeded") "SUCCESS" else "PENDING", res)
    }

    override fun verify(providerRef: String, amount: Double): VerifyResult {
        checkConfigured()
        val res = client.get()
            .uri(URI.create("https://api.stripe.com/v1/payment_intents/$providerRef"))
            .header("Authorization", "Bearer $secretKey")
            .retrieve()
            .body(String::class.java) ?: "{}"
        val status = Regex("\"status\"\\s*:\\s*\"([^\"]+)\"").find(res)?.groupValues?.get(1)
        return VerifyResult(if (status == "succeeded") "SUCCESS" else "FAILED", res)
    }

    override fun refund(providerRef: String): Boolean {
        checkConfigured()
        val res = client.post()
            .uri(URI.create("https://api.stripe.com/v1/refunds"))
            .header("Authorization", "Bearer $secretKey")
            .header("Content-Type", "application/x-www-form-urlencoded")
            .body("payment_intent=$providerRef")
            .retrieve()
            .body(String::class.java) ?: "{}"
        return Regex("\"id\"\\s*:\\s*\"([^\"]+)\"").find(res)?.groupValues?.get(1) != null
    }
}

@Service
class PaymentService(
    private val paymentRepository: PaymentRepository,
    private val bookingRepository: BookingRepository,
    private val boothRepository: BoothRepository,
    private val exhibitionRepository: ExhibitionRepository,
    private val settlementRepository: SettlementRepository,
    private val mockProvider: MockPaymentProvider,
    private val mpesaProvider: MpesaProvider,
    private val stripeProvider: StripeProvider,
    private val auditLogService: AuditLogService,
    private val notificationService: NotificationService,
    private val scopeResolver: ScopeResolver,
    private val countyRepository: CountyRepository,
    @Value("\${kicc.payments.revenue-share-kicc:0.8}") private val kiccShare: Double,
    @Value("\${kicc.payments.revenue-share-county:0.2}") private val countyShare: Double
) {

    private fun provider(method: String): PaymentProvider = when (method.lowercase()) {
        "mpesa" -> mpesaProvider
        "stripe" -> stripeProvider
        else -> mockProvider
    }

    private fun bookingFor(user: User, bookingId: Long): Booking {
        val booking = bookingRepository.findById(bookingId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Booking not found") }
        if (user.tier == Role.EXHIBITOR && booking.userId != user.id) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Not your booking")
        }
        return booking
    }

    @Transactional
    fun initiate(user: User, bookingId: Long, method: String, phone: String?): PaymentView {
        val booking = bookingFor(user, bookingId)
        if (booking.status != "PENDING" && booking.status != "CONFIRMED") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Booking is not payable (status ${booking.status})")
        }
        val provider = provider(method)
        val accountRef = booking.bookingReference
        val result = provider.initiate(
            InitiateRequest(
                amount = booking.total,
                currency = booking.currency,
                phone = phone,
                accountReference = accountRef,
                description = "KICC booth booking $accountRef"
            )
        )
        val payment = paymentRepository.save(
            Payment(
                bookingId = booking.id!!,
                amount = booking.total,
                currency = booking.currency,
                provider = provider.name,
                method = method.lowercase(),
                status = result.status,
                providerRef = result.providerRef,
                phone = phone,
                description = "KICC booth booking $accountRef",
                rawResponse = result.raw,
                initiatedByUserId = user.id!!,
                initiatedAt = Instant.now().toString()
            )
        )
        auditLogService.record(user.id!!, "PAYMENT_INITIATED", null, "payment=${payment.id}, booking=$accountRef, provider=${provider.name}")
        if (result.status == "SUCCESS") {
            completePayment(user, payment)
        } else if (result.status == "FAILED") {
            payment.failureReason = "Provider declined the payment"
            paymentRepository.save(payment)
        }
        return PaymentView.from(payment, accountRef, revenueShare(payment))
    }

    @Transactional
    fun verify(user: User, paymentId: Long): PaymentView {
        val payment = paymentRepository.findById(paymentId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Payment not found") }
        if (user.tier == Role.EXHIBITOR && payment.initiatedByUserId != user.id) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Not your payment")
        }
        if (payment.status == "SUCCESS" || payment.status == "REFUNDED") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Payment already ${payment.status}")
        }
        val provider = provider(payment.method)
        val result = provider.verify(payment.providerRef ?: "", payment.amount)
        payment.rawResponse = result.raw
        if (result.status == "SUCCESS") {
            completePayment(user, payment)
        } else {
            payment.status = "FAILED"
            payment.failureReason = "Verification failed"
            paymentRepository.save(payment)
        }
        val booking = bookingRepository.findById(payment.bookingId).orElse(null)
        return PaymentView.from(payment, booking?.bookingReference, revenueShare(payment))
    }

    private fun completePayment(user: User, payment: Payment) {
        payment.status = "SUCCESS"
        payment.completedAt = Instant.now().toString()
        payment.failureReason = null
        paymentRepository.save(payment)
        val booking = bookingRepository.findById(payment.bookingId).orElse(null)
        if (booking != null && booking.status != "CANCELLED" && booking.status != "REFUNDED") {
            booking.status = "PAID"
            booking.paidAt = Instant.now().toString()
            bookingRepository.save(booking)
        }
        auditLogService.record(user.id!!, "PAYMENT_SUCCESS", null, "payment=${payment.id}, ref=${payment.providerRef}, amount=${payment.amount}")
        booking?.userId?.let { ownerId ->
            notificationService.notify(
                ownerId,
                "BOOKING_STATUS",
                "Payment received",
                "KES ${payment.amount.toLong()} received for ${booking.bookingReference} — booking is PAID",
                "paymentId=${payment.id}"
            )
        }
    }

    @Transactional
    fun refund(user: User, paymentId: Long): PaymentView {
        if (user.tier == Role.EXHIBITOR) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Exhibitors cannot issue refunds")
        }
        val payment = paymentRepository.findById(paymentId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Payment not found") }
        if (payment.status != "SUCCESS") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Only successful payments can be refunded")
        }
        val provider = provider(payment.method)
        val ok = provider.refund(payment.providerRef ?: "")
        if (!ok) {
            throw ResponseStatusException(HttpStatus.BAD_GATEWAY, "Provider rejected the refund")
        }
        payment.status = "REFUNDED"
        payment.completedAt = Instant.now().toString()
        paymentRepository.save(payment)
        val booking = bookingRepository.findById(payment.bookingId).orElse(null)
        if (booking != null && booking.status != "CANCELLED") {
            booking.status = "REFUNDED"
            booking.boothId?.let { boothId ->
                boothRepository.findById(boothId).ifPresent { booth ->
                    booth.bookedQuantity = (booth.bookedQuantity - booking.quantity).coerceAtLeast(0)
                    boothRepository.save(booth)
                }
            }
            bookingRepository.save(booking)
        }
        auditLogService.record(user.id!!, "PAYMENT_REFUNDED", null, "payment=${payment.id}, booking=${booking?.bookingReference}")
        return PaymentView.from(payment, booking?.bookingReference, revenueShare(payment))
    }

    fun list(user: User): List<PaymentView> {
        val payments = if (user.tier == Role.EXHIBITOR) {
            val bookingIds = bookingRepository.findByUserIdOrderByIdDesc(user.id!!).map { it.id!! }
            paymentRepository.findByBookingIdInOrderByIdDesc(bookingIds)
        } else {
            paymentRepository.findAllByOrderByIdDesc()
        }
        val refs = bookingRepository.findAll().associate { it.id!! to it.bookingReference }
        return payments.map { PaymentView.from(it, refs[it.bookingId], revenueShare(it)) }
    }

    fun detail(user: User, paymentId: Long): PaymentView {
        val payment = paymentRepository.findById(paymentId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Payment not found") }
        if (user.tier == Role.EXHIBITOR && payment.initiatedByUserId != user.id) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Not your payment")
        }
        val ref = bookingRepository.findById(payment.bookingId).orElse(null)?.bookingReference
        return PaymentView.from(payment, ref, revenueShare(payment))
    }

    fun revenueShare(payment: Payment): RevenueShareView? {
        val booking = bookingRepository.findById(payment.bookingId).orElse(null) ?: return null
        val countyId = booking.exhibitionId?.let { exhibitionId ->
            exhibitionRepository.findById(exhibitionId).orElse(null)?.countyId
        }
        return RevenueShareView(
            countyId = countyId,
            kiccAmount = payment.amount * kiccShare,
            countyAmount = payment.amount * countyShare,
            kiccShare = kiccShare,
            countyShare = countyShare
        )
    }

    fun settlements(user: User): List<SettlementView> {
        requireMoneyOperator(user)
        return settlementRepository.findAllByOrderByIdDesc().map { SettlementView.from(it) }
    }

    private fun requireMoneyOperator(user: User) {
        if (user.tier == Role.EXHIBITOR) {
            throw ResponseStatusException(HttpStatus.FORBIDDEN, "Exhibitors cannot manage settlements")
        }
    }

    private fun requirePendingOrApproved(s: Settlement): Settlement {
        if (s.status != "PENDING" && s.status != "APPROVED") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Settlement is ${s.status} and cannot be modified")
        }
        return s
    }

    @Transactional
    fun approveSettlement(user: User, settlementId: Long): SettlementView {
        requireMoneyOperator(user)
        val settlement = settlementRepository.findById(settlementId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Settlement not found") }
        if (settlement.status != "PENDING") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Only PENDING settlements can be approved (status ${settlement.status})")
        }
        settlement.status = "APPROVED"
        settlement.approvedAt = Instant.now().toString()
        settlement.approvedByUserId = user.id
        settlementRepository.save(settlement)
        auditLogService.record(
            user.id!!, "SETTLEMENT_APPROVED", null,
            "settlement=${settlement.id}, provider=${settlement.provider}, amount=${settlement.totalAmount}"
        )
        return SettlementView.from(settlement)
    }

    @Transactional
    fun paySettlement(user: User, settlementId: Long, payoutRef: String): SettlementView {
        requireMoneyOperator(user)
        if (payoutRef.isBlank()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "payoutRef is required")
        }
        val settlement = settlementRepository.findById(settlementId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Settlement not found") }
        if (settlement.status != "APPROVED") {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Only APPROVED settlements can be paid (status ${settlement.status})")
        }
        settlement.status = "PAID"
        settlement.paidAt = Instant.now().toString()
        settlement.paidByUserId = user.id
        settlement.payoutRef = payoutRef
        settlementRepository.save(settlement)
        auditLogService.record(
            user.id!!, "SETTLEMENT_PAID", null,
            "settlement=${settlement.id}, provider=${settlement.provider}, payoutRef=$payoutRef, amount=${settlement.totalAmount}"
        )
        return SettlementView.from(settlement)
    }

    @Transactional
    fun failSettlement(user: User, settlementId: Long, reason: String): SettlementView {
        requireMoneyOperator(user)
        if (reason.isBlank()) {
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "failure reason is required")
        }
        val settlement = settlementRepository.findById(settlementId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Settlement not found") }
        requirePendingOrApproved(settlement)
        settlement.status = "FAILED"
        settlement.failureReason = reason
        settlementRepository.save(settlement)
        auditLogService.record(
            user.id!!, "SETTLEMENT_FAILED", null,
            "settlement=${settlement.id}, provider=${settlement.provider}, reason=$reason"
        )
        return SettlementView.from(settlement)
    }

    fun statement(user: User, countySlug: String?, from: String?, to: String?): List<CountyStatementView> {
        requireMoneyOperator(user)
        val access = scopeResolver.accessFor(user)
        val periodStart = parsePeriodBound(from, isEnd = false)
        val periodEnd = parsePeriodBound(to, isEnd = true)
        val successful = paymentRepository.findAllByOrderByIdDesc().filter { it.status == "SUCCESS" }
        val bookings = bookingRepository.findAll().associateBy { it.id!! }
        val exhibitions = exhibitionRepository.findAll().associateBy { it.id!! }

        val countyOfPayment: (Payment) -> Long? = { p ->
            bookings[p.bookingId]?.exhibitionId?.let { exhibitions[it]?.countyId }
        }

        val targets = if (countySlug != null) {
            val county = countyRepository.findBySlug(countySlug)
                ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found")
            if (!access.canAccessCounty(county.id!!)) {
                throw ResponseStatusException(HttpStatus.FORBIDDEN, "County outside your scope")
            }
            listOf(county)
        } else if (access.counties.isNotEmpty()) {
            countyRepository.findAllById(access.counties)
        } else {
            countyRepository.findAll()
        }

        return targets.map { county ->
            val lines = successful
                .filter { p ->
                    val at = p.completedAt?.let { runCatching { Instant.parse(it) }.getOrNull() } ?: return@filter false
                    at.isAfter(periodStart) && at.isBefore(periodEnd) && countyOfPayment(p) == county.id
                }
                .map { p ->
                    StatementLineView(
                        paymentId = p.id!!,
                        bookingReference = bookings[p.bookingId]?.bookingReference,
                        amount = p.amount,
                        currency = p.currency,
                        paymentStatus = p.status,
                        completedAt = p.completedAt
                    )
                }
            val gross = lines.sumOf { it.amount }
            CountyStatementView(
                countySlug = county.slug,
                countyName = county.name,
                periodStart = periodStart.toString(),
                periodEnd = periodEnd.toString(),
                paymentCount = lines.size,
                grossAmount = gross,
                kiccAmount = gross * kiccShare,
                countyAmount = gross * countyShare,
                kiccShare = kiccShare,
                countyShare = countyShare,
                payments = lines
            )
        }.filter { countySlug != null || it.paymentCount > 0 }
    }

    private fun parsePeriodBound(value: String?, isEnd: Boolean): Instant {
        if (value == null || value.isBlank()) {
            return if (isEnd) Instant.now() else Instant.now().minusSeconds(30 * 86400)
        }
        return runCatching { Instant.parse(value) }.getOrElse {
            runCatching {
                java.time.LocalDate.parse(value).atStartOfDay(java.time.ZoneOffset.UTC).toInstant()
            }.getOrElse {
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid date: use ISO instant or yyyy-MM-dd")
            }
        }
    }

    @Scheduled(cron = "0 0 2 * * *")
    @Transactional
    fun runDailySettlements() {
        val now = Instant.now()
        val dayStart = now.minusSeconds(86400)
        val dayEnd = now
        val payments = paymentRepository.findAllByOrderByIdDesc()
            .filter { it.status == "SUCCESS" && it.completedAt != null }
            .filter { p ->
                val completed = try {
                    Instant.parse(p.completedAt!!)
                } catch (e: Exception) {
                    return@filter false
                }
                completed.isAfter(dayStart) && completed.isBefore(dayEnd)
            }
        payments.groupBy { it.provider }.forEach { (provider, group) ->
            settlementRepository.save(
                Settlement(
                    provider = provider,
                    periodStart = dayStart.toString(),
                    periodEnd = dayEnd.toString(),
                    totalAmount = group.sumOf { it.amount },
                    paymentCount = group.size,
                    status = "PENDING",
                    createdAt = now.toString()
                )
            )
        }
    }

    @Scheduled(cron = "0 */5 * * * *")
    @Transactional
    fun expireStalledPayments() {
        val cutoff = Instant.now().minusSeconds(1800)
        val stalled = paymentRepository.findAllByOrderByIdDesc()
            .filter { it.status in setOf("INITIATED", "PENDING") }
            .filter {
                try { Instant.parse(it.initiatedAt).isBefore(cutoff) } catch (_: Exception) { false }
            }
        for (p in stalled) {
            p.status = "FAILED"
            p.failureReason = "Timed out after 30 minutes"
            paymentRepository.save(p)
        }
    }
}

data class PaymentRequest(
    @field:jakarta.validation.constraints.NotNull
    val bookingId: Long,
    val method: String = "mock",
    val phone: String? = null
)

@RestController
@RequestMapping("/api/payments")
class PaymentController(private val paymentService: PaymentService) {

    @PostMapping
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun initiate(@AuthenticationPrincipal user: User, @RequestBody req: PaymentRequest): PaymentView =
        paymentService.initiate(user, req.bookingId, req.method, req.phone)

    @GetMapping
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun list(@AuthenticationPrincipal user: User): List<PaymentView> = paymentService.list(user)

    @GetMapping("/{id}")
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun detail(@AuthenticationPrincipal user: User, @PathVariable id: Long): PaymentView =
        paymentService.detail(user, id)

    @PostMapping("/{id}/verify")
    @PreAuthorize("hasAuthority('BOOKINGS_MANAGE')")
    fun verify(@AuthenticationPrincipal user: User, @PathVariable id: Long): PaymentView =
        paymentService.verify(user, id)

    @PostMapping("/{id}/refund")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun refund(@AuthenticationPrincipal user: User, @PathVariable id: Long): PaymentView =
        paymentService.refund(user, id)

    @GetMapping("/settlements")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun settlements(@AuthenticationPrincipal user: User): List<SettlementView> =
        paymentService.settlements(user)

    @PostMapping("/settlements/{id}/approve")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun approveSettlement(@AuthenticationPrincipal user: User, @PathVariable id: Long): SettlementView =
        paymentService.approveSettlement(user, id)

    @PostMapping("/settlements/{id}/pay")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun paySettlement(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody req: SettlementPayRequest): SettlementView =
        paymentService.paySettlement(user, id, req.payoutRef)

    @PostMapping("/settlements/{id}/fail")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun failSettlement(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody req: SettlementFailRequest): SettlementView =
        paymentService.failSettlement(user, id, req.reason)

    @GetMapping("/statements")
    @PreAuthorize("hasAuthority('PAYMENTS_MANAGE')")
    fun statement(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) countySlug: String?,
        @RequestParam(required = false) from: String?,
        @RequestParam(required = false) to: String?
    ): List<CountyStatementView> = paymentService.statement(user, countySlug, from, to)
}

data class SettlementPayRequest(val payoutRef: String = "")

data class SettlementFailRequest(val reason: String = "")
