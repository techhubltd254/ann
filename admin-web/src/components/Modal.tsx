import { useEffect, useRef, type ReactNode } from 'react'
import { useAutoAnimate } from '@formkit/auto-animate/react'

interface ModalProps {
  open: boolean
  onClose: () => void
  title: string
  subtitle?: string
  children: ReactNode
  footer?: ReactNode
  wide?: boolean
}

const FOCUSABLE = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'

export default function Modal({ open, onClose, title, subtitle, children, footer, wide }: ModalProps) {
  const [bodyRef] = useAutoAnimate<HTMLDivElement>()
  const panelRef = useRef<HTMLDivElement>(null)
  const titleId = useRef(`modal-title-${Math.random().toString(36).slice(2, 8)}`).current

  useEffect(() => {
    if (!open) return
    const previous = document.activeElement as HTMLElement | null

    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') { onClose(); return }
      if (e.key !== 'Tab') return
      // Focus trap — keep Tab/Shift+Tab inside the dialog (WCAG 2.2, no keyboard traps)
      const panel = panelRef.current
      if (!panel) return
      const nodes = Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(el => el.offsetParent !== null)
      if (nodes.length === 0) { e.preventDefault(); panel.focus(); return }
      const first = nodes[0]
      const last = nodes[nodes.length - 1]
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
    }

    window.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    panelRef.current?.focus()
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
      previous?.focus?.()
    }
  }, [open, onClose])

  if (!open) return null

  return (
    <div
      className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby={titleId}
      onClick={onClose}
    >
      <div className="absolute inset-0 bg-slate-950/70 backdrop-blur-sm modal-fade" aria-hidden />
      <div
        ref={panelRef}
        tabIndex={-1}
        onClick={e => e.stopPropagation()}
        className={`relative w-full ${wide ? 'max-w-3xl' : 'max-w-lg'} rounded-t-2xl sm:rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50 modal-pop outline-none`}
      >
        <div className="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-slate-800">
          <div>
            <h2 id={titleId} className="text-lg font-bold text-white">{title}</h2>
            {subtitle && <p className="text-xs text-slate-400 mt-0.5">{subtitle}</p>}
          </div>
          <button
            onClick={onClose}
            aria-label="Close dialog"
            className="target-min w-11 h-11 shrink-0 rounded-full flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 active:scale-90 transition-all duration-150"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <div ref={bodyRef} className="px-6 py-5 max-h-[65vh] overflow-y-auto">
          {children}
        </div>

        {footer && (
          <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-800 bg-slate-950/40 rounded-b-2xl">
            {footer}
          </div>
        )}
      </div>
    </div>
  )
}
