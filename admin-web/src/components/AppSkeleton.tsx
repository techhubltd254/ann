/**
 * Full-screen loading placeholder for route-level auth guards.
 */
export default function AppSkeleton() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-surface" role="status" aria-label="Loading">
      <div className="flex flex-col items-center gap-3">
        <div className="h-9 w-9 animate-spin rounded-full border-2 border-slate-700 border-t-blue-400" aria-hidden />
        <div className="text-xs text-slate-400">Loading…</div>
      </div>
    </div>
  )
}
