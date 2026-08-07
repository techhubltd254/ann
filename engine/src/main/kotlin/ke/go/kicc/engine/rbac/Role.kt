package ke.go.kicc.engine.rbac

enum class Role {
    KICC,
    NATIONAL,
    COUNTY,
    EXHIBITOR,
    /** National Intelligence Service — read-only security oversight (blueprint Layer 1). */
    NIS,
    /** Schools / institutions — limited exhibitor-style participation. */
    SCHOOL
}
