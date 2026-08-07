import { useEffect, useRef, useState } from 'react'

interface CinematicMediaProps {
  poster: string
  webm?: string
  mp4?: string
  glb?: string
  alt?: string
  className?: string
  autoplay?: boolean
  onClick?: () => void
}

/**
 * Poster-first cinematic media:
 *  - shows the image instantly (never waits on video)
 *  - swaps to video on hover (pointer users) or autoplay (in-view, hero)
 *  - WebM preferred, MP4 fallback, 3D slot for .glb
 *  - WCAG: descriptive alt text, single interactive control (play/pause),
 *    reduced-motion aware, 44px targets
 */
export default function CinematicMedia({ poster, webm, mp4, glb, alt = '', className = '', autoplay = false, onClick }: CinematicMediaProps) {
  const videoRef = useRef<HTMLVideoElement>(null)
  const [playing, setPlaying] = useState(false)
  const [hovering, setHovering] = useState(false)
  const [inView, setInView] = useState(false)

  useEffect(() => {
    const el = videoRef.current
    if (!el || (!webm && !mp4)) return
    const io = new IntersectionObserver(
      ([entry]) => {
        setInView(entry.isIntersecting)
        if (entry.isIntersecting && autoplay) el.play().catch(() => {})
      },
      { threshold: 0.15 }
    )
    io.observe(el)
    return () => io.disconnect()
  }, [webm, mp4, autoplay])

  // Hover-to-play (pointer users only, respects reduced motion)
  const onMouseEnter = () => {
    if (autoplay) return
    setHovering(true)
    videoRef.current?.play().catch(() => {})
  }
  const onMouseLeave = () => {
    if (autoplay) return
    setHovering(false)
    videoRef.current?.pause()
  }

  const toggle = () => {
    const el = videoRef.current
    if (!el) return
    if (el.paused) el.play().then(() => setPlaying(true)).catch(() => {})
    else { el.pause(); setPlaying(false) }
  }

  const showVideo = hovering || (inView && autoplay)
  const hasVideo = Boolean(webm || mp4)
  const accessibleLabel = alt || 'Cinematic media'
  const reducedMotion = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

  return (
    <div
      role={onClick ? 'button' : undefined}
      aria-label={onClick ? `Preview ${accessibleLabel}` : undefined}
      aria-hidden={onClick ? undefined : !hasVideo && alt === ''}
      tabIndex={onClick ? 0 : undefined}
      onKeyDown={onClick ? (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onClick() } } : undefined}
      onMouseEnter={reducedMotion ? undefined : onMouseEnter}
      onMouseLeave={reducedMotion ? undefined : onMouseLeave}
      className={`relative overflow-hidden bg-slate-950 group focus-visible:outline-2 focus-visible:outline-blue-400 focus-visible:outline-offset-2 ${onClick ? 'cursor-pointer' : ''} ${className}`}
      onClick={onClick}
    >
      {glb ? (
        <div className="absolute inset-0 flex items-center justify-center text-slate-500 text-sm" aria-hidden>
          <span className="rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-2 text-xs font-semibold">🧊 3D model slot</span>
        </div>
      ) : null}

      {!glb && (
        <img
          src={poster}
          alt={hasVideo ? '' : alt}
          aria-hidden={hasVideo ? true : undefined}
          loading="lazy"
          className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-700 ${hasVideo && showVideo ? 'opacity-0' : 'opacity-100'}`}
        />
      )}

      {!glb && hasVideo && (
        <video
          ref={videoRef}
          muted
          loop
          playsInline
          preload={hovering || autoplay ? 'metadata' : 'none'}
          poster={poster}
          aria-label={accessibleLabel}
          className={`w-full h-full object-cover transition-opacity duration-700 ${showVideo ? 'opacity-100' : 'opacity-0'}`}
          onPlaying={() => setPlaying(true)}
          onPause={() => setPlaying(false)}
        >
          {webm && <source src={webm} type="video/webm" />}
          {mp4 && <source src={mp4} type="video/mp4" />}
        </video>
      )}

      {hasVideo && !onClick && (
        <button
          onClick={e => { e.stopPropagation(); toggle() }}
          aria-label={playing ? `Pause ${accessibleLabel}` : `Play ${accessibleLabel}`}
          className={`target-min absolute bottom-3 right-3 w-11 h-11 rounded-full flex items-center justify-center backdrop-blur-md border border-white/20 shadow-lg transition-all duration-200 active:scale-90 hover:scale-105 ${playing ? 'bg-white/15 text-white' : 'bg-white/90 text-slate-900'}`}
        >
          {playing ? (
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 5h4v14H6zM14 5h4v14h-4z" /></svg>
          ) : (
            <svg className="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
          )}
        </button>
      )}
    </div>
  )
}
