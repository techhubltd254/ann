package ke.go.kicc.engine.marketplace

import jakarta.persistence.*
import ke.go.kicc.engine.audit.AuditLogService
import ke.go.kicc.engine.data.ProductRepository
import ke.go.kicc.engine.data.CountyRepository
import ke.go.kicc.engine.user.User
import org.springframework.data.jpa.repository.JpaRepository
import org.springframework.http.HttpStatus
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.stereotype.Service
import org.springframework.transaction.annotation.Transactional
import org.springframework.web.bind.annotation.*
import org.springframework.web.server.ResponseStatusException
import java.security.SecureRandom
import java.time.Instant

@Entity
@Table(name = "cart_items")
class CartItem(
    @Id @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,
    @Column(nullable = false) var userId: Long = 0,
    @Column(nullable = false) var productId: Long = 0,
    @Column(nullable = false) var quantity: Int = 1,
    @Column(nullable = false) var addedAt: String = ""
)

@Entity
@Table(name = "orders")
class KeOrder(
    @Id @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,
    @Column(nullable = false) var orderReference: String = "",
    @Column(nullable = false) var userId: Long = 0,
    @Column(nullable = false) var status: String = "PENDING",
    @Column(nullable = false) var subtotal: Double = 0.0,
    @Column(nullable = false) var tax: Double = 0.0,
    @Column(nullable = false) var total: Double = 0.0,
    var notes: String? = null,
    var countySlug: String? = null,
    @Column(nullable = false) var createdAt: String = "",
    var updatedAt: String? = null
)

@Entity
@Table(name = "order_items")
class KeOrderItem(
    @Id @GeneratedValue(strategy = GenerationType.IDENTITY)
    var id: Long? = null,
    @Column(nullable = false) var orderId: Long = 0,
    @Column(nullable = false) var productId: Long = 0,
    @Column(nullable = false) var productName: String = "",
    @Column(nullable = false) var quantity: Int = 1,
    @Column(nullable = false) var unitPrice: Double = 0.0,
    @Column(nullable = false) var subtotal: Double = 0.0
)

interface CartItemRepository : JpaRepository<CartItem, Long> {
    fun findByUserIdOrderByIdAsc(userId: Long): List<CartItem>
    fun findByUserIdAndProductId(userId: Long, productId: Long): CartItem?
    fun deleteByUserId(userId: Long)
}

interface OrderRepository : JpaRepository<KeOrder, Long> {
    fun findByUserIdOrderByIdDesc(userId: Long): List<KeOrder>
    fun findByOrderReference(reference: String): KeOrder?
    fun findAllByOrderByIdDesc(): List<KeOrder>
}

interface OrderItemRepository : JpaRepository<KeOrderItem, Long> {
    fun findByOrderIdOrderByIdAsc(orderId: Long): List<KeOrderItem>
}

@Service
class MarketplaceService(
    private val cartItemRepository: CartItemRepository,
    private val orderRepository: OrderRepository,
    private val orderItemRepository: OrderItemRepository,
    private val productRepository: ProductRepository,
    private val countyRepository: CountyRepository,
    private val auditLogService: AuditLogService
) {
    private val random = SecureRandom()
    private val vatRate = 0.16

    fun cart(user: User): List<CartItem> = cartItemRepository.findByUserIdOrderByIdAsc(user.id!!)

    @Transactional
    fun addToCart(user: User, productId: Long, quantity: Int): CartItem {
        val product = productRepository.findById(productId)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Product not found") }
        if (product.stock != null && product.stock!! <= 0)
            throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Product out of stock")
        val existing = cartItemRepository.findByUserIdAndProductId(user.id!!, productId)
        if (existing != null) {
            existing.quantity += quantity
            return cartItemRepository.save(existing)
        }
        return cartItemRepository.save(
            CartItem(userId = user.id!!, productId = productId, quantity = quantity, addedAt = Instant.now().toString())
        )
    }

    @Transactional
    fun updateCartItem(user: User, id: Long, quantity: Int): CartItem {
        val item = cartItemRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Cart item not found") }
        if (item.userId != user.id!!) throw ResponseStatusException(HttpStatus.FORBIDDEN, "Not your cart item")
        if (quantity <= 0) { cartItemRepository.delete(item); return item }
        item.quantity = quantity
        return cartItemRepository.save(item)
    }

    @Transactional
    fun removeCartItem(user: User, id: Long) {
        val item = cartItemRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Cart item not found") }
        if (item.userId != user.id!!) throw ResponseStatusException(HttpStatus.FORBIDDEN, "Not your cart item")
        cartItemRepository.delete(item)
    }

    @Transactional
    fun checkout(user: User, notes: String?): KeOrder {
        val cart = cartItemRepository.findByUserIdOrderByIdAsc(user.id!!)
        if (cart.isEmpty()) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Cart is empty")
        val now = Instant.now().toString()
        var subtotal = 0.0
        val items = mutableListOf<KeOrderItem>()
        var countySlug: String? = null
        for (ci in cart) {
            val product = productRepository.findById(ci.productId).orElse(null) ?: continue
            val unitPrice = product.price ?: continue
            if (product.stock != null && product.stock!! < ci.quantity) {
                throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Insufficient stock for ${product.name}")
            }
            val lineTotal = unitPrice * ci.quantity
            subtotal += lineTotal
            items.add(KeOrderItem(
                orderId = 0,
                productId = ci.productId,
                productName = product.name,
                quantity = ci.quantity,
                unitPrice = unitPrice,
                subtotal = lineTotal
            ))
            product.stock = product.stock?.minus(ci.quantity)
            productRepository.save(product)
        }
        if (items.isEmpty()) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "No valid items in cart")
        val tax = subtotal * vatRate
        val total = subtotal + tax
        val ref = generateReference()
        val order = orderRepository.save(KeOrder(
            orderReference = ref,
            userId = user.id!!,
            status = "PENDING",
            subtotal = subtotal,
            tax = tax,
            total = total,
            notes = notes,
            countySlug = user.countySlug,
            createdAt = now
        ))
        items.forEach { it.orderId = order.id!!; orderItemRepository.save(it) }
        cartItemRepository.deleteByUserId(user.id!!)
        auditLogService.record(user.id!!, "ORDER_PLACED", null, "ref=$ref, items=${items.size}, total=$total")
        return order
    }

    fun myOrders(user: User): List<KeOrder> = orderRepository.findByUserIdOrderByIdDesc(user.id!!)

    fun orderDetail(user: User, id: Long): Map<String, Any> {
        val order = orderRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Order not found") }
        return mapOf(
            "order" to order,
            "items" to orderItemRepository.findByOrderIdOrderByIdAsc(id)
        )
    }

    fun allOrders(): List<KeOrder> = orderRepository.findAllByOrderByIdDesc()

    @Transactional
    fun setOrderStatus(user: User, id: Long, status: String): KeOrder {
        val order = orderRepository.findById(id)
            .orElseThrow { ResponseStatusException(HttpStatus.NOT_FOUND, "Order not found") }
        val allowed = mapOf(
            "PENDING" to listOf("CONFIRMED", "CANCELLED"),
            "CONFIRMED" to listOf("PROCESSING", "CANCELLED"),
            "PROCESSING" to listOf("SHIPPED", "CANCELLED"),
            "SHIPPED" to listOf("DELIVERED", "CANCELLED"),
            "DELIVERED" to emptyList(),
            "CANCELLED" to emptyList()
        )
        val next = allowed[order.status] ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Invalid status transition")
        if (status !in next) throw ResponseStatusException(HttpStatus.BAD_REQUEST, "Cannot transition ${order.status} → $status")
        if (status == "CANCELLED") {
            orderItemRepository.findByOrderIdOrderByIdAsc(id).forEach { oi ->
                productRepository.findById(oi.productId).ifPresent { p ->
                    p.stock = (p.stock ?: 0) + oi.quantity
                    productRepository.save(p)
                }
            }
        }
        order.status = status
        order.updatedAt = Instant.now().toString()
        orderRepository.save(order)
        auditLogService.record(user.id!!, "ORDER_STATUS_CHANGED", null, "ref=${order.orderReference}, status=$status")
        return order
    }

    private fun generateReference(): String {
        val chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
        return "KICC-ORD-" + (1..8).map { chars[random.nextInt(chars.length)] }.joinToString("")
    }

    fun browseProducts(countySlug: String?): List<ke.go.kicc.engine.data.Product> {
        return if (countySlug != null) {
            val county = countyRepository.findBySlug(countySlug)
                ?: throw ResponseStatusException(HttpStatus.NOT_FOUND, "County not found")
            productRepository.findAllByCountyIdInOrderByIdAsc(listOf(county.id!!))
                .filter { it.isPublished && (it.price ?: 0.0) > 0 }
        } else {
            productRepository.findAll().filter { it.isPublished && (it.price ?: 0.0) > 0 }
        }
    }
}

@RestController
@RequestMapping("/api/marketplace")
class MarketplaceController(private val service: MarketplaceService) {

    @GetMapping("/cart")
    fun cart(@AuthenticationPrincipal user: User): List<CartItem> = service.cart(user)

    @PostMapping("/cart")
    @Transactional
    fun addToCart(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, Any?>): CartItem =
        service.addToCart(user, (body["productId"] as? Number)?.toLong() ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "productId required"), (body["quantity"] as? Number)?.toInt() ?: 1)

    @PutMapping("/cart/{id}")
    @Transactional
    fun updateCart(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody body: Map<String, Any?>): CartItem =
        service.updateCartItem(user, id, (body["quantity"] as? Number)?.toInt() ?: 0)

    @DeleteMapping("/cart/{id}")
    @Transactional
    fun removeFromCart(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, String> {
        service.removeCartItem(user, id)
        return mapOf("deleted" to "true")
    }

    @PostMapping("/checkout")
    @Transactional
    fun checkout(@AuthenticationPrincipal user: User, @RequestBody body: Map<String, Any?>): KeOrder =
        service.checkout(user, body["notes"]?.toString())

    @GetMapping("/orders")
    fun myOrders(@AuthenticationPrincipal user: User): List<KeOrder> = service.myOrders(user)

    @GetMapping("/orders/{id}")
    fun orderDetail(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, Any> = service.orderDetail(user, id)

    @GetMapping("/products")
    fun browseProducts(@RequestParam(required = false) countySlug: String?): List<ke.go.kicc.engine.data.Product> =
        service.browseProducts(countySlug)
}

@RestController
@RequestMapping("/api/admin/orders")
class OrderAdminController(private val service: MarketplaceService) {

    @GetMapping
    @PreAuthorize("hasAuthority('MARKETPLACE_MANAGE')")
    fun listOrders(): List<KeOrder> = service.allOrders()

    @GetMapping("/{id}")
    @PreAuthorize("hasAuthority('MARKETPLACE_MANAGE')")
    fun orderDetail(@AuthenticationPrincipal user: User, @PathVariable id: Long): Map<String, Any> = service.orderDetail(user, id)

    @PostMapping("/{id}/status")
    @PreAuthorize("hasAuthority('MARKETPLACE_MANAGE')")
    @Transactional
    fun setStatus(@AuthenticationPrincipal user: User, @PathVariable id: Long, @RequestBody body: Map<String, Any?>): KeOrder =
        service.setOrderStatus(user, id, body["status"]?.toString() ?: throw ResponseStatusException(HttpStatus.BAD_REQUEST, "status required"))
}
