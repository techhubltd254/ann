import { useEffect, useRef, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'

export interface FloatingNavItem {
  to: string
  label: string
  icon: React.ReactNode
  privilege?: string
  privileges?: string[]
}

export interface FloatingNavProps {
  items: FloatingNavItem[]
  cta?: { to: string; label: string; icon?: React.ReactNode }
  ariaLabel?: string
}

/**
 * Floating segmented pill navbar (glassmorphic).
 * - Scrollable tab list (fades at the edges as an overflow affordance),
 *   action CTA pill at the right end (Fitts's Law).
 * - Sliding highlight tracks the active tab; `aria-current` for AT.
 * - WCAG 2.2: 44px targets, linear keyboard nav + arrow-key scrolling, focus-visible.
 */
export default function FloatingNav({ items, cta, ariaLabel = 'Primary' }: FloatingNavProps) {
  const loc = useLocation()
  const trackRef = useRef<HTMLDivElement>(null)
  const [highlight, setHighlight] = useState<{ left: number; width: number } | null>(null)
  const [canScrollL, setCanScrollL] = useState(false)
  const [canScrollR, setCanScrollR] = useState(false)
  const [reduced, setReduced] = useState(false)

  useEffect(() => {
    const mq = window.matchMedia('(prefers-reduced-motion: reduce)')
    setReduced(mq.matches)
    const onChange = (e: MediaQueryListEvent) => setReduced(e.matches)
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [])

  useEffect(() => {
    const track = trackRef.current
    if (!track) return
    const measure = () => {
      const active = track.querySelector<HTMLElement>('[aria-current="page"]')
      if (!active) { setHighlight(null); return }
      const t = track.getBoundingClientRect()
      const a = active.getBoundingClientRect()
      setHighlight({ left: a.left - t.left, width: a.width })
    }
    const scrollState = () => {
      if (!track) return
      setCanScrollL(track.scrollLeft > 2)
      setCanScrollR(track.scrollLeft + track.clientWidth < track.scrollWidth - 2)
    }
    measure()
    scrollState()
    const ro = new ResizeObserver(() => { measure(); scrollState() })
    ro.observe(track)
    window.addEventListener('resize', () => { measure(); scrollState() })
    track.addEventListener('scroll', scrollState, { passive: true })
    return () => { ro.disconnect(); window.removeEventListener('resize', measure); track.removeEventListener('scroll', scrollState) }
  }, [loc.pathname, items])

  const onKeyDown = (e: React.KeyboardEvent) => {
    const track = trackRef.current
    if (!track) return
    if (e.key === 'ArrowLeft') { track.scrollBy({ left: -120, behavior: reduced ? 'auto' : 'smooth' }); e.preventDefault() }
    else if (e.key === 'ArrowRight') { track.scrollBy({ left: 120, behavior: reduced ? 'auto' : 'smooth' }); e.preventDefault() }
  }

  return (
    <nav
      aria-label={ariaLabel}
      onKeyDown={onKeyDown}
      className="fixed bottom-4 left-1/2 z-40 w-max max-w-[calc(100vw-1rem)] -translate-x-1/2"
    >
      <div className="glass-pill flex items-center gap-1 rounded-full p-1.5 overflow-x-auto no-scrollbar relative">
        <div ref={trackRef} className="relative flex items-center gap-1">
          {/* Sliding highlight indicator */}
          <span
            aria-hidden
            className={`pointer-events-none absolute top-1 bottom-1 rounded-full bg-gradient-brand shadow-glow-blue ${highlight ? 'opacity-100' : 'opacity-0'}`}
            style={highlight ? { left: highlight.left, width: highlight.width, transition: reduced ? 'none' : 'left 260ms cubic-bezier(0.22, 1, 0.36, 1), width 260ms cubic-bezier(0.22, 1, 0.36, 1), opacity 200ms' } : undefined}
          />
          {items.map(item => {
            const active = loc.pathname === item.to
            return (
              <Link
                key={item.to}
                to={item.to}
                aria-current={active ? 'page' : undefined}
                className="target-min relative z-10 flex min-h-11 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-colors duration-200"
                style={active
                  ? { color: '#fff' }
                  : { color: 'rgba(226,232,240,0.72)' }}
              >
                <span className="shrink-0 text-lg leading-none" aria-hidden>{item.icon}</span>
                <span className="whitespace-nowrap">{item.label}</span>
              </Link>
            )
          })}
        </div>

        {cta && (
          <div className="ml-1 flex shrink-0 items-center border-l border-white/10 pl-1.5">
            <Link
              to={cta.to}
              aria-current={loc.pathname === cta.to ? 'page' : undefined}
              className="target-min flex min-h-11 items-center gap-2 rounded-full bg-gradient-brand px-4 py-2 text-sm font-bold text-white shadow-lg shadow-blue-600/30 transition-all duration-200 hover:brightness-110 hover:shadow-glow-blue active:scale-95"
            >
              {cta.icon && <span className="text-base leading-none" aria-hidden>{cta.icon}</span>}
              <span className="whitespace-nowrap">{cta.label}</span>
            </Link>
          </div>
        )}
      </div>
      {(canScrollL || canScrollR) && (
        <span aria-hidden className="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-full bg-slate-900/80 px-2.5 py-0.5 text-[9px] font-bold text-slate-400 backdrop-blur">
          ← scroll → (Arrow keys)
        </span>
      )}
    </nav>
  )
}
