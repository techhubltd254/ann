package ke.go.kicc.engine.bootstrap

import ke.go.kicc.engine.rbac.Role
import ke.go.kicc.engine.user.User
import ke.go.kicc.engine.user.UserRepository
import org.springframework.beans.factory.annotation.Value
import org.springframework.boot.CommandLineRunner
import org.springframework.security.crypto.password.PasswordEncoder
import org.springframework.stereotype.Component

@Component
class DataSeeder(
    private val userRepository: UserRepository,
    private val passwordEncoder: PasswordEncoder,
    @Value("\${kicc.seed.county-slug:kilifi}") private val countySlug: String,
    @Value("\${kicc.seed.county-password:county@2026}") private val countyPassword: String,
    @Value("\${kicc.seed.admin-password:Admin@2026}") private val adminPassword: String,
    @Value("\${kicc.seed.mode:all}") private val mode: String
) : CommandLineRunner {

    override fun run(vararg args: String?) {
        if (mode == "county") {
            seed("county@kicc.go.ke", countyPassword, "County Administrator", Role.COUNTY, countySlug, null, null)
            seed("exhibitor@kicc.go.ke", "exhibitor@2026", "Exhibitor Admin", Role.EXHIBITOR, null, null, 1L)
            return
        }
        seed("admin@kicc.go.ke", adminPassword, "KICC Administrator", Role.KICC, null, null, null)
        seed("national@kicc.go.ke", "national@2026", "National Administrator", Role.NATIONAL, null, null, null)
        seed("county@kicc.go.ke", countyPassword, "County Administrator", Role.COUNTY, countySlug, null, null)
        seed("exhibitor@kicc.go.ke", "exhibitor@2026", "Exhibitor Admin", Role.EXHIBITOR, null, null, 1L)
    }

    private fun seed(
        email: String,
        password: String,
        fullName: String,
        tier: Role,
        countySlug: String?,
        sectorId: Long?,
        boothId: Long?
    ) {
        if (userRepository.findByEmail(email) == null) {
            userRepository.save(
                User(
                    email = email,
                    passwordHash = passwordEncoder.encode(password),
                    fullName = fullName,
                    tier = tier,
                    countySlug = countySlug,
                    sectorId = sectorId,
                    boothId = boothId
                )
            )
        }
    }
}
