package ke.go.kicc.engine.user

import org.springframework.data.jpa.repository.JpaRepository

interface UserRepository : JpaRepository<User, Long> {
    fun findByEmail(email: String): User?

    fun findByCountySlug(countySlug: String): List<User>

    fun existsByCountySlug(countySlug: String): Boolean
}
