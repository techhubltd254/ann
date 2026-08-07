import { useCallback, useEffect, useState } from 'react'
import { useAutoAnimate } from '@formkit/auto-animate/react'
import { api } from '../lib/api'

interface AiAssistSuggestion {
  persona: string
  painPoint: string
  copy: string
  palette: [string, string, string]
}

interface UxAssistResponse {
  context: string
  suggestions: AiAssistSuggestion[]
  source: 'ai' | 'local'
}

const FALLBACKS: AiAssistSuggestion[] = [
  {
    persona: 'County media officer (Diaspora remittance + tourism)',
    painPoint: 'Shows a static hero; needs motion to prove the destination is alive on slow 3G connections.',
    copy: 'Show your county in motion — cinematic hero video, compressed for low-bandwidth phones.',
    palette: ['#3b82f6', '#2f915c', '#f59e0b']
  },
  {
    persona: 'Exhibitor preparing booth assets for MSME Expo',
    painPoint: 'Uploads JPEG, gets nothing back; no idea whether the render will finish before the expo.',
    copy: 'Upload once, run a cinematic pipeline, attach anywhere — nothing hardcoded.',
    palette: ['#8b5cf6', '#06b6d4', '#f97316']
  },
  {
    persona: 'KICC national content administrator',
    painPoint: 'Needs brand consistency across 47 county pages without waiting on designers.',
    copy: 'One asset, many surfaces — webm, mp4 and poster generated and attached automatically.',
    palette: ['#0ea5e9', '#10b981', '#ef4444']
  }
]

/** Simple WCAG relative-luminance contrast ratio between two hex colors. */
function contrastRatio(a: string, b: string): number {
  const lum = (hex: string) => {
    const [r, g, bl] = hex.slice(1).match(/[0-9a-f]{2}/gi)!.map(v => parseInt(v, 16) / 255)
    const lin = (c: number) => (c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4))
    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(bl)
  }
  const [l1, l2] = [lum(a), lum(b)].sort((x, y) => y - x)
  return (l1 + 0.05) / (l2 + 0.05)
}

function contrastToWhite(hex: string): number {
  return contrastRatio(hex, '#0f172a')
}

/**
 * AI-assist UX research panel.
 * Fetches live persona/pain-point/copy/palette insights from the backend
 * (OpenRouter/Gemini via AiUxService), rotating through them client-side.
 */
export default function AiAssistPanel({ context = 'media_library', className = '' }: { context?: string; className?: string }) {
  const [suggestions, setSuggestions] = useState<AiAssistSuggestion[]>(FALLBACKS)
  const [source, setSource] = useState<'ai' | 'local'>('local')
  const [status, setStatus] = useState<'idle' | 'loading' | 'ready' | 'error'>('idle')
  const [idx, setIdx] = useState(0)
  const [copied, setCopied] = useState(false)
  const [ref] = useAutoAnimate<HTMLDivElement>()
  const s = suggestions[idx % suggestions.length]

  const load = useCallback(async () => {
    setStatus('loading')
    try {
      const res = await api.get<UxAssistResponse>(`/ai/ux-assist?context=${encodeURIComponent(context)}`)
      if (res.suggestions?.length >= 3) {
        setSuggestions(res.suggestions)
        setSource(res.source === 'ai' ? 'ai' : 'local')
        setIdx(0)
        setStatus('ready')
        return
      }
      setStatus('error')
    } catch {
      setStatus('error')
    }
  }, [context])

  useEffect(() => {
    load()
  }, [load])

  const copy = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopied(true)
      setTimeout(() => setCopied(false), 1600)
    } catch {
      /* clipboard unavailable */
    }
  }

  return (
    <aside className={`card flex flex-col gap-3 p-4 ${className}`} aria-label="AI-assisted UX research">
      <div className="flex items-center justify-between gap-2">
        <h2 className="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-300">
          <span className="text-base" aria-hidden>✦</span> AI UX Research
          {status === 'loading' && (
            <span className="ml-1 h-3 w-3 animate-spin rounded-full border-2 border-slate-600 border-t-blue-400" aria-hidden />
          )}
        </h2>
        <div className="flex items-center gap-1.5">
          {source === 'ai' && status === 'ready' && (
            <span className="text-[9px] font-bold uppercase tracking-wider text-emerald-400" role="status">live AI</span>
          )}
          {source === 'local' && status === 'ready' && (
            <span className="text-[9px] font-bold uppercase tracking-wider text-slate-400" role="status">curated</span>
          )}
          {status === 'error' && (
            <span className="text-[9px] font-bold uppercase tracking-wider text-amber-400" role="status">offline</span>
          )}
          <button
            onClick={() => load()}
            disabled={status === 'loading'}
            className="target-min rounded-lg border border-slate-700 px-3 py-1.5 text-[11px] font-bold text-slate-300 transition-all duration-200 hover:border-blue-500 hover:text-white active:scale-95 disabled:opacity-50"
          >
            ↻ Refresh
          </button>
        </div>
      </div>

      <div ref={ref} key={idx} className="flex flex-col gap-3 animate-pop">
        <div className="rounded-xl border border-blue-500/20 bg-blue-500/5 p-3">
          <div className="text-[10px] font-black uppercase tracking-widest text-blue-300">Persona</div>
          <div className="mt-1 text-xs font-semibold text-white">{s.persona}</div>
        </div>

        <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3">
          <div className="text-[10px] font-black uppercase tracking-widest text-emerald-300">Pain point</div>
          <div className="mt-1 text-xs leading-relaxed text-slate-300">{s.painPoint}</div>
        </div>

        <div className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
          <div className="text-[10px] font-black uppercase tracking-widest text-slate-400">Suggested copy</div>
          <p className="mt-1 text-xs leading-relaxed text-white">"{s.copy}"</p>
          <button
            onClick={() => copy(s.copy)}
            className="target-min mt-2.5 rounded-lg border border-slate-700 px-3 py-1 text-[11px] font-bold text-slate-300 transition-colors hover:border-white hover:text-white active:scale-95"
          >
            {copied ? 'Copied ✓' : 'Copy to clipboard'}
          </button>
        </div>

        <div className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
          <div className="text-[10px] font-black uppercase tracking-widest text-slate-400">Palette pairing</div>
          <div className="mt-2 flex items-center gap-2">
            {s.palette.map(c => (
              <span
                key={c}
                title={`${c} — ${contrastToWhite(c) >= 4.5 ? 'passes AA on dark' : 'for reference only'}`}
                className="h-7 w-10 rounded-md border border-white/10"
                style={{ backgroundColor: c }}
              />
            ))}
            <span className="ml-auto text-[10px] font-mono text-slate-400">
              {s.palette.every(c => contrastToWhite(c) >= 4.5) ? 'WCAG AA paired' : 'contrast varies'}
            </span>
          </div>
        </div>
      </div>
    </aside>
  )
}
