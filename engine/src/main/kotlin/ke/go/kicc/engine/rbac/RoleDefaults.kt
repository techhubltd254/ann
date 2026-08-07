package ke.go.kicc.engine.rbac

object RoleDefaults {

    private val all = Privilege.entries.toSet()

    fun privilegesFor(role: Role): Set<Privilege> = when (role) {
        Role.KICC -> all
        Role.NATIONAL -> setOf(
            // Least privilege: the national government admin manages NATIONAL content
            // (ministries, agencies, national hub, national media) and views national
            // aggregates. It does NOT touch county sector data, payments, marketplace,
            // users, or system settings — those belong to KICC and county tiers.
            Privilege.CONTENT_MANAGE, Privilege.MEDIA_MANAGE,
            Privilege.ANALYTICS_VIEW, Privilege.REPORTS_VIEW
        )
        Role.COUNTY -> setOf(
            Privilege.CONTENT_MANAGE, Privilege.BOOKINGS_MANAGE, Privilege.PAYMENTS_MANAGE,
            Privilege.USERS_MANAGE, Privilege.ANALYTICS_VIEW
        )
        Role.EXHIBITOR -> setOf(
            Privilege.CONTENT_MANAGE, Privilege.BOOKINGS_MANAGE
        )
        // NIS: read-only oversight — analytics and reports, zero write access.
        Role.NIS -> setOf(
            Privilege.ANALYTICS_VIEW, Privilege.REPORTS_VIEW
        )
        // SCHOOL: institution exhibitor — manage own content + bookings only.
        Role.SCHOOL -> setOf(
            Privilege.CONTENT_MANAGE, Privilege.BOOKINGS_MANAGE
        )
    }
}
