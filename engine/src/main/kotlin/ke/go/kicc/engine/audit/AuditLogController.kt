package ke.go.kicc.engine.audit

import ke.go.kicc.engine.user.User
import org.springframework.security.access.prepost.PreAuthorize
import org.springframework.security.core.annotation.AuthenticationPrincipal
import org.springframework.web.bind.annotation.*

@RestController
@RequestMapping("/api/admin/audit-logs")
class AuditLogController(private val auditLogService: AuditLogService) {

    @GetMapping
    @PreAuthorize("hasAuthority('DELEGATE')")
    fun list(
        @AuthenticationPrincipal user: User,
        @RequestParam(required = false) targetUserId: Long?,
        @RequestParam(defaultValue = "200") limit: Int,
        @RequestParam(defaultValue = "0") offset: Int
    ): List<AuditLogView> = auditLogService.list(user, targetUserId, limit).drop(offset)

    @GetMapping("/verify")
    @PreAuthorize("hasAuthority('DELEGATE')")
    fun verify(): AuditChainStatus = auditLogService.verify()
}
