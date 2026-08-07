package ke.go.kicc.engine.rbac

import org.springframework.data.jpa.repository.JpaRepository

interface DelegationRepository : JpaRepository<Delegation, Long> {
    fun findBySubjectIdAndRevokedAtIsNull(subjectId: Long): List<Delegation>
}
