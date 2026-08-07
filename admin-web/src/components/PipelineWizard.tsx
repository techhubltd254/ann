import { useState } from 'react'
import { useAutoAnimate } from '@formkit/auto-animate/react'
import Modal from './Modal'

export interface EngineOption {
  key: string
  label: string
  description: string
  cost: 'free' | 'local' | 'api'
  available: boolean
  note?: string
  pipeline?: string[]
}

export interface PipelineWizardProps {
  open: boolean
  onClose: () => void
  assetName: string
  engines: EngineOption[]
  onDispatch: (pipeline: string, engine: string, options: Record<string, string>) => void
  dispatching: boolean
}

const PIPELINES = [
  {
    key: 'cinematic_video',
    icon: '🎬',
    title: 'Cinematic Video',
    desc: 'Camera motion, lighting & depth from your image',
    accent: 'bg-blue-500/15 border-blue-500/40'
  },
  {
    key: 'image_to_3d',
    icon: '🧊',
    title: '3D Model (.glb)',
    desc: 'Rotatable web-ready mesh, compressed & LOD-ready',
    accent: 'bg-emerald-500/15 border-emerald-500/40'
  }
]

const STEPS = ['Pipeline', 'Engine', 'Options']

export default function PipelineWizard({ open, onClose, assetName, engines, onDispatch, dispatching }: PipelineWizardProps) {
  const [step, setStep] = useState(1)
  const [pipeline, setPipeline] = useState('cinematic_video')
  const [engine, setEngine] = useState('')
  const [options, setOptions] = useState({ prompt: 'Cinematic aerial shot, smooth camera motion, golden hour light, ultra realistic', camera: 'orbit', duration: '8', aspect: '16:9' })
  const [bodyRef] = useAutoAnimate<HTMLDivElement>()

  const filtered = engines.filter(e => e.pipeline?.includes(pipeline) ?? true)
  const anyAvailable = filtered.some(e => e.available)
  const hasEngine = Boolean(engine)

  const next = () => {
    if (step === 1 && anyAvailable) setStep(2)
    else if (step === 2 && hasEngine) setStep(3)
  }

  const submit = () => {
    onDispatch(pipeline, engine, options)
  }

  const onRadioKeyDown = (e: React.KeyboardEvent<HTMLButtonElement>, items: unknown[], idx: number, select: (i: number) => void) => {
    if (e.key !== 'ArrowDown' && e.key !== 'ArrowRight' && e.key !== 'ArrowUp' && e.key !== 'ArrowLeft') return
    e.preventDefault()
    if (items.length === 0) return
    const delta = e.key === 'ArrowUp' || e.key === 'ArrowLeft' ? -1 : 1
    const nextIdx = (idx + delta + items.length) % items.length
    select(nextIdx)
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Run cinematic pipeline"
      subtitle={assetName}
      footer={
        <>
          {step > 1 && (
            <button onClick={() => setStep(step - 1)} className="btn-secondary h-10 px-4 rounded-xl active:scale-95 transition-transform">
              Back
            </button>
          )}
          {step < 3 && (
            <button
              onClick={next}
              disabled={step === 2 ? !hasEngine : !anyAvailable}
              className="rounded-xl bg-gradient-brand px-6 h-11 font-semibold text-white hover:brightness-110 active:scale-95 transition-all duration-150 disabled:opacity-40 disabled:active:scale-100"
            >
              {step === 1 && !anyAvailable ? 'No engines available' : 'Continue →'}
            </button>
          )}
          {step === 3 && (
            <button
              onClick={submit}
              disabled={dispatching}
              className="inline-flex items-center gap-2 rounded-xl bg-gradient-brand px-6 h-11 font-semibold text-white hover:brightness-110 active:scale-95 transition-all duration-150 disabled:opacity-50"
            >
              {dispatching ? (
                <>
                  <span className="w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin" />
                  Dispatching…
                </>
              ) : (
                <>Looks good — run it</>
              )}
            </button>
          )}
        </>
      }
    >
      <div ref={bodyRef}>
        {/* Step indicator */}
        <div className="flex items-center gap-2 mb-6">
          {STEPS.map((s, i) => (
            <div key={s} className="flex items-center gap-2 flex-1">
              <div
                className={`w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black transition-all duration-300 ${
                  step > i + 1 ? 'bg-emerald-500 text-white' : step === i + 1 ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-500'
                }`}
              >
                {step > i + 1 ? '✓' : i + 1}
              </div>
              <div className={`text-[10px] font-bold uppercase tracking-widest ${step >= i + 1 ? 'text-white' : 'text-slate-600'}`}>{s}</div>
              {i < 2 && <div className={`h-px flex-1 ${step > i + 1 ? 'bg-emerald-500/50' : 'bg-slate-800'}`} />}
            </div>
          ))}
        </div>

        {step === 1 && (
          <div className="space-y-3" role="radiogroup" aria-label="Choose pipeline type">
            {PIPELINES.map((p, i) => (
              <button
                key={p.key}
                role="radio"
                aria-checked={pipeline === p.key}
                tabIndex={pipeline === p.key ? 0 : -1}
                onClick={() => setPipeline(p.key)}
                onKeyDown={e => onRadioKeyDown(e, PIPELINES, i, idx => setPipeline(PIPELINES[idx].key))}
                className={`w-full text-left flex items-center gap-3 p-4 rounded-xl border-2 transition-all duration-200 active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-blue-400 focus-visible:outline-offset-2 ${
                  pipeline === p.key ? 'border-blue-500 bg-blue-500/10' : 'border-slate-800 bg-slate-950/50 hover:border-slate-600'
                }`}
              >
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 ${p.accent}`}>{p.icon}</div>
                <div>
                  <div className="font-bold text-white text-sm">{p.title}</div>
                  <div className="text-[11px] text-slate-400 mt-0.5">{p.desc}</div>
                </div>
                {pipeline === p.key && <span className="ml-auto text-blue-400 text-lg">✓</span>}
              </button>
            ))}
          </div>
        )}

        {step === 2 && (
          <div className="space-y-3" role="radiogroup" aria-label="Choose engine">
            {filtered.length === 0 && (
              <div className="rounded-xl border border-slate-800 bg-slate-950/50 p-4 text-xs text-slate-400">
                No engines configured for this pipeline yet. Ask your admin to enable one in <code className="font-mono text-amber-400">config/pipeline.php</code>.
              </div>
            )}
            {filtered.map((e, i) => (
              <button
                key={e.key}
                role="radio"
                aria-checked={engine === e.key}
                tabIndex={engine === e.key ? 0 : -1}
                onClick={() => setEngine(e.key)}
                onKeyDown={ev => onRadioKeyDown(ev, filtered, i, idx => setEngine(filtered[idx].key))}
                disabled={!e.available}
                className={`w-full text-left p-4 rounded-xl border-2 transition-all duration-200 active:scale-[0.99] disabled:opacity-50 disabled:active:scale-100 focus-visible:outline-2 focus-visible:outline-blue-400 focus-visible:outline-offset-2 ${
                  engine === e.key ? 'border-blue-500 bg-blue-500/10' : 'border-slate-800 bg-slate-950/50 hover:border-slate-600'
                }`}
              >
                <div className="flex items-center justify-between gap-2">
                  <div className="font-bold text-white text-sm">{e.label}</div>
                  <span className={`text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-full ${
                    e.cost === 'free' ? 'bg-emerald-500/15 text-emerald-400' : e.cost === 'local' ? 'bg-sky-500/15 text-sky-400' : 'bg-amber-500/15 text-amber-400'
                  }`}>{e.cost}</span>
                </div>
                <p className="text-[11px] text-slate-400 mt-1 leading-relaxed">{e.description}</p>
                {!e.available && e.note && <p className="text-[10px] mt-1.5 text-amber-400">⚠ {e.note}</p>}
              </button>
            ))}
          </div>
        )}

        {step === 3 && (
          <div className="space-y-4">
            <label className="block">
              <span className="label text-slate-400">Prompt — what should the AI do?</span>
              <textarea
                rows={3}
                value={options.prompt}
                onChange={e => setOptions({ ...options, prompt: e.target.value })}
                className="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm text-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
              />
            </label>
            <div className="grid grid-cols-2 gap-3">
              <label className="block">
                <span className="label text-slate-400">Camera path</span>
                <select
                  value={options.camera}
                  onChange={e => setOptions({ ...options, camera: e.target.value })}
                  className="w-full h-11 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-white outline-none focus:border-blue-500"
                >
                  <option value="zoom_in">Zoom In</option>
                  <option value="zoom_out">Zoom Out</option>
                  <option value="pan_lr">Pan L → R</option>
                  <option value="tilt_up">Tilt Up</option>
                  <option value="orbit">Orbit</option>
                </select>
              </label>
              <label className="block">
                <span className="label text-slate-400">Duration</span>
                <select
                  value={options.duration}
                  onChange={e => setOptions({ ...options, duration: e.target.value })}
                  className="w-full h-11 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-white outline-none focus:border-blue-500"
                >
                  {['6', '8', '10', '15', '20'].map(d => <option key={d} value={d}>{d}s</option>)}
                </select>
              </label>
            </div>
            <div className="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5 flex items-start gap-3">
              <span className="text-base">⚡</span>
              <p className="text-[11px] text-slate-400 leading-relaxed">
                Processing runs in the background — this page polls progress live. Results are compressed to WebM + MP4 and delivered poster-first so pages never wait on video.
              </p>
            </div>
          </div>
        )}
      </div>
    </Modal>
  )
}
