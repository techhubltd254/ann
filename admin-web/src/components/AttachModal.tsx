import { useEffect, useState } from 'react'
import { useAutoAnimate } from '@formkit/auto-animate/react'
import Modal from './Modal'

export interface AttachmentRegistry {
  entityType: string
  label: string
  slots: Record<string, string>
}

export interface AttachModalProps {
  open: boolean
  onClose: () => void
  assetName: string
  registry: AttachmentRegistry[]
  entities: Record<string, Record<string, string>>
  current?: { ownerName?: string; slot?: string } | null
  onAttach: (entityType: string, entityId: string, slot: string) => void
  onDetach: () => void
  busy: boolean
}

export default function AttachModal({ open, onClose, assetName, registry, entities, current, onAttach, onDetach, busy }: AttachModalProps) {
  const [entityType, setEntityType] = useState(registry[0]?.entityType ?? '')
  const [entityId, setEntityId] = useState('')
  const [slot, setSlot] = useState('')
  const [bodyRef] = useAutoAnimate<HTMLDivElement>()

  const def = registry.find(r => r.entityType === entityType)
  const options = entities[entityType] ?? {}
  const slotOptions = def?.slots ?? {}

  // Seed defaults to the first option so "Attach" works immediately (no pointless interaction)
  useEffect(() => {
    if (!open || !def) return
    setEntityType(def.entityType)
    setEntityId(Object.keys(entities[def.entityType] ?? {})[0] ?? '')
    setSlot(Object.keys(def.slots)[0] ?? '')
  }, [open])

  const changeType = (t: string) => {
    setEntityType(t)
    setEntityId(Object.keys(entities[t] ?? {})[0] ?? '')
    setSlot(Object.keys(registry.find(r => r.entityType === t)?.slots ?? {})[0] ?? '')
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Attach to a page"
      subtitle={assetName}
      footer={
        <>
          <button onClick={onClose} className="btn-secondary h-10 px-4 rounded-xl active:scale-95 transition-transform">Cancel</button>
          {current?.ownerName ? (
            <button onClick={onDetach} disabled={busy} className="rounded-xl border border-red-500/40 px-5 h-10 text-sm font-semibold text-red-400 hover:bg-red-500/10 active:scale-95 transition-all duration-150 disabled:opacity-50">
              Detach
            </button>
          ) : (
            <button
              onClick={() => onAttach(entityType, entityId, slot)}
              disabled={busy || !entityId}
              className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 h-10 font-semibold text-white hover:bg-blue-500 active:scale-95 transition-all duration-150 disabled:opacity-50"
            >
              {busy ? <span className="w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin" /> : '🔗'}
              Attach
            </button>
          )}
        </>
      }
    >
      <div ref={bodyRef}>
        {current?.ownerName ? (
          <div className="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 animate-pop">
            <span className="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-base">🔗</span>
            <div>
              <div className="text-sm font-bold text-emerald-300">{current.ownerName}</div>
              <div className="text-[11px] text-emerald-400/80">{current.slot ?? ''}</div>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            <label className="block">
              <span className="label text-slate-400">Page type</span>
              <select
                value={entityType}
                onChange={e => changeType(e.target.value)}
                className="w-full h-11 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-white outline-none focus:border-blue-500 transition-all"
              >
                {registry.map(r => <option key={r.entityType} value={r.entityType}>{r.label}</option>)}
              </select>
            </label>

            <label className="block">
              <span className="label text-slate-400">Placement slot</span>
              <select
                value={slot}
                onChange={e => setSlot(e.target.value)}
                className="w-full h-11 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-white outline-none focus:border-blue-500 transition-all"
              >
                {Object.entries(slotOptions).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
              </select>
            </label>

            <label className="block">
              <span className="label text-slate-400">Which one?</span>
              <select
                value={entityId}
                onChange={e => setEntityId(e.target.value)}
                className="w-full h-11 rounded-xl bg-slate-950 border border-slate-700 px-3 text-sm text-white outline-none focus:border-blue-500 transition-all"
              >
                {Object.entries(options).map(([id, name]) => <option key={id} value={id}>{name}</option>)}
              </select>
            </label>

            <p className="text-[11px] text-slate-500 leading-relaxed">
              Attached assets play automatically in the page hero with zero code changes. Nothing is hardcoded.
            </p>
          </div>
        )}
      </div>
    </Modal>
  )
}
