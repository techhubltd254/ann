package ke.go.kicc.engine.tenant

data class Tenant(
    val countySlug: String?,
    val sectorId: Long?,
    val boothId: Long?
)

object TenantContext {
    private val holder = ThreadLocal<Tenant>()

    fun set(tenant: Tenant) {
        holder.set(tenant)
    }

    fun get(): Tenant = holder.get() ?: Tenant(null, null, null)

    fun clear() {
        holder.remove()
    }
}
