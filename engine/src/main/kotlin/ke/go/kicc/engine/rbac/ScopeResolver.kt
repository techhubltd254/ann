package ke.go.kicc.engine.rbac

import ke.go.kicc.engine.data.CountyRepository
import ke.go.kicc.engine.user.User
import org.springframework.stereotype.Service
import java.time.Instant

/**
 * A user's reachable data envelope.
 *
 * Semantics (documented in docs/gap-analysis-2026.md §3, C1):
 * - [homeCounties]: the user's own reach from their tier + countySlug
 *   (COUNTY/EXHIBITOR -> own county; KICC/NATIONAL -> empty = unrestricted).
 * - [delegatedCounties]/[sectorIds]/[boothIds]: scopes granted by active
 *   (non-revoked, non-expired) delegations.
 * - Union semantics: a row is accessible if covered by ANY scope in the
 *   envelope. Delegation scopes EXTEND reach (a kilifi county admin with a
 *   muranga-scoped grant may operate in muranga); they never narrow base
 *   access. Narrowing requires least-privilege defaults (Phase 7 W2).
 */
data class ScopedAccess(
    val homeCounties: Set<Long> = emptySet(),
    val delegatedCounties: Set<Long> = emptySet(),
    val sectorIds: Set<Long> = emptySet(),
    val boothIds: Set<Long> = emptySet(),
    /**
     * True ONLY for KICC: empty county envelope means "all counties".
     * NATIONAL gets an empty envelope WITHOUT this flag — the national tier is
     * independent of the county sector structure by design, so an empty envelope
     * means "no county data access", not "all of it".
     */
    val unrestricted: Boolean = false
) {
    val counties: Set<Long> get() = homeCounties + delegatedCounties

    val isRestricted: Boolean
        get() = !unrestricted && (counties.isNotEmpty() || sectorIds.isNotEmpty() || boothIds.isNotEmpty())

    fun canAccessCounty(id: Long): Boolean = unrestricted || id in counties

    fun canAccessSector(id: Long): Boolean = sectorIds.isEmpty() || id in sectorIds

    fun canAccessBooth(id: Long): Boolean = boothIds.isEmpty() || id in boothIds
}

@Service
class ScopeResolver(
    private val countyRepository: CountyRepository,
    private val delegationRepository: DelegationRepository
) {

    fun accessFor(user: User): ScopedAccess {
        val now = Instant.now()
        // KICC: unrestricted (empty envelope = all counties).
        // NATIONAL: empty envelope and NOT unrestricted — independent of county data.
        // COUNTY/EXHIBITOR: their own county only.
        val (home, unrestricted) = when (user.tier) {
            Role.KICC -> emptySet<Long>() to true
            Role.NATIONAL -> emptySet<Long>() to false
            else -> (user.countySlug?.let { slug -> countyRepository.findBySlug(slug)?.id?.let { setOf(it) } }
                ?: emptySet()) to false
        }
        val delegatedCounties = mutableSetOf<Long>()
        val sectors = mutableSetOf<Long>()
        val booths = mutableSetOf<Long>()
        if (user.id != null) {
            delegationRepository.findBySubjectIdAndRevokedAtIsNull(user.id!!)
                .filter { d ->
                    val expires = d.expiresAt
                    expires == null || expires.isAfter(now)
                }
                .forEach { d ->
                    when (d.scopeType) {
                        ScopeType.COUNTY -> d.scopeValue?.let { slug ->
                            countyRepository.findBySlug(slug)?.id?.let { delegatedCounties += it }
                        }
                        ScopeType.SECTOR -> d.scopeValue?.toLongOrNull()?.let { sectors += it }
                        ScopeType.BOOTH -> d.scopeValue?.toLongOrNull()?.let { booths += it }
                        ScopeType.ALL -> {}
                    }
                }
        }
        return ScopedAccess(home, delegatedCounties, sectors, booths, unrestricted)
    }
}
