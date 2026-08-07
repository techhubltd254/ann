import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useAutoAnimate } from '@formkit/auto-animate/react'
import CinematicMedia from '../components/CinematicMedia'
import PipelineWizard, { type EngineOption } from '../components/PipelineWizard'
import AttachModal, { type AttachmentRegistry } from '../components/AttachModal'
import ProgressiveBlurCard from '../components/ProgressiveBlurCard'
import AiAssistPanel from '../components/AiAssistPanel'
import Modal from '../components/Modal'
import ConfirmDialog from '../components/ConfirmDialog'
import { SkeletonCard } from '../components/Skeleton'
import { api } from '../lib/api'

interface Derivative { kind: string; sizeBytes: number; variant?: string; path: string }
interface Asset {
  id: number
  uuid: string
  originalName: string
  kind: 'image' | 'video' | 'model' | string
  status: 'ready' | 'processing' | 'uploaded' | 'failed' | string
  poster: string
  webm?: string
  mp4?: string
  glb?: string
  width?: number
  height?: number
  sizeBytes: number
  derivatives: Derivative[]
  ownerName?: string
  slot?: string
  progress?: number | null
  stage?: string | null
  failureReason?: string | null
}

interface MediaIndexResponse {
  assets: Asset[]
  counts: Record<string, number>
}

interface MediaDetailResponse {
  asset: Asset
  engines: EngineOption[]
  attachment: { entityType: string; label: string; slots: Record<string, string>; entities: Record<string, string> }[]
}

interface JobStatusResponse {
  status: string
  progress: number
  stage: string
  error: string | null
}

const FILTERS = ['All', 'Images', 'Videos', 'Models', 'Ready', 'Processing'] as const
type Filter = typeof FILTERS[number]

function formatBytes(n: number) {
  if (n > 1_048_576) return `${(n / 1_048_576).toFixed(1)} MB`
  if (n > 1024) return `${Math.round(n / 1024)} KB`
  return `${n} B`
}

export default function MediaLibrary() {
  const [assets, setAssets] = useState<Asset[]>([])
  const [counts, setCounts] = useState<Record<string, number>>({})
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState<Filter>('All')
  const [search, setSearch] = useState('')
  const [chipsRef] = useAutoAnimate<HTMLDivElement>()
  const [gridRef] = useAutoAnimate<HTMLDivElement>()
  const [wizardFor, setWizardFor] = useState<Asset | null>(null)
  const [engines, setEngines] = useState<EngineOption[]>([])
  const [attachFor, setAttachFor] = useState<Asset | null>(null)
  const [registry, setRegistry] = useState<AttachmentRegistry[]>([])
  const [entities, setEntities] = useState<Record<string, Record<string, string>>>({})
  const [lightbox, setLightbox] = useState<Asset | null>(null)
  const [deleteFor, setDeleteFor] = useState<Asset | null>(null)
  const [dispatching, setDispatching] = useState(false)
  const [attachBusy, setAttachBusy] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [uploadBusy, setUploadBusy] = useState(false)
  const [toast, setToast] = useState('')
  const fileRef = useRef<HTMLInputElement>(null)
  const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const params = new URLSearchParams()
      if (filter === 'Images' || filter === 'Videos' || filter === 'Models') params.set('kind', filter.slice(0, -1).toLowerCase())
      else if (filter === 'Ready' || filter === 'Processing') params.set('status', filter.toLowerCase())
      if (search) params.set('q', search)
      const res = await api.get<MediaIndexResponse>(`/media?${params}`)
      setAssets(res.assets)
      setCounts(res.counts)
    } catch (e) {
      showToast((e as Error).message || 'Failed to load media library')
    } finally {
      setLoading(false)
    }
  }, [filter, search])

  useEffect(() => {
    const t = setTimeout(load, 120)
    return () => clearTimeout(t)
  }, [load])

  // Poll while any asset is processing/uploaded
  useEffect(() => {
    if (!assets.some(a => a.status === 'processing' || a.status === 'uploaded')) return
    const iv = setInterval(() => { load() }, 2500)
    return () => clearInterval(iv)
  }, [assets, load])

  const showToast = useCallback((m: string) => {
    setToast(m)
    if (toastTimer.current) clearTimeout(toastTimer.current)
    toastTimer.current = setTimeout(() => setToast(''), 3200)
  }, [])

  const openWizard = async (a: Asset) => {
    setWizardFor(a)
    try {
      const detail = await api.get<MediaDetailResponse>(`/media/${a.id}`)
      setEngines(detail.engines)
    } catch (e) {
      showToast((e as Error).message || 'Failed to load engines')
      setEngines([])
    }
  }

  const openAttach = async (a: Asset) => {
    setAttachFor(a)
    try {
      const detail = await api.get<MediaDetailResponse>(`/media/${a.id}`)
      const reg: AttachmentRegistry[] = detail.attachment.map(att => ({ entityType: att.entityType, label: att.label, slots: att.slots }))
      const ents: Record<string, Record<string, string>> = {}
      detail.attachment.forEach(att => { ents[att.entityType] = att.entities })
      setRegistry(reg)
      setEntities(ents)
    } catch (e) {
      showToast((e as Error).message || 'Failed to load attachment targets')
      setRegistry([])
      setEntities({})
    }
  }

  const dispatch = async (pipeline: string, engine: string, options: Record<string, string>) => {
    if (!wizardFor) return
    setDispatching(true)
    try {
      const res = await api.post<{ job_id: number }>(`/media/${wizardFor.id}/pipeline`, { pipeline, engine, ...options })
      setWizardFor(null)
      showToast('Pipeline started — processing in the background')
      // Poll the job status until the asset flips out of processing
      const jobId = res.job_id
      const poll = setInterval(async () => {
        try {
          const j = await api.get<JobStatusResponse>(`/media/jobs/${jobId}/status`)
          if (['complete', 'completed', 'done', 'failed', 'cancelled'].includes(j.status)) {
            clearInterval(poll)
            load()
          }
        } catch { clearInterval(poll) }
      }, 2000)
    } catch (e) {
      showToast((e as Error).message || 'Dispatch failed')
    } finally {
      setDispatching(false)
    }
  }

  const attach = async (entityType: string, entityId: string, slot: string) => {
    if (!attachFor) return
    setAttachBusy(true)
    try {
      const res = await api.post<{ asset: Asset }>(`/media/${attachFor.id}/attach`, { entity_type: entityType, entity_id: Number(entityId), slot })
      setAssets(prev => prev.map(a => a.id === res.asset.id ? res.asset : a))
      setAttachFor(null)
      showToast('Attached — it now plays on the target page')
    } catch (e) {
      showToast((e as Error).message || 'Attach failed')
    } finally {
      setAttachBusy(false)
    }
  }

  const detach = async () => {
    if (!attachFor) return
    setAttachBusy(true)
    try {
      const res = await api.post<{ asset: Asset }>(`/media/${attachFor.id}/detach`, {})
      setAssets(prev => prev.map(a => a.id === res.asset.id ? res.asset : a))
      setAttachFor(null)
      showToast('Detached — asset stays in the library')
    } catch (e) {
      showToast((e as Error).message || 'Detach failed')
    } finally {
      setAttachBusy(false)
    }
  }

  const uploadFiles = async (files: FileList | null) => {
    if (!files || files.length === 0) return
    setUploadBusy(true)
    try {
      const form = new FormData()
      Array.from(files).slice(0, 10).forEach(f => form.append('files[]', f))
      const res = await api.postForm<{ count: number }>('/media', form)
      showToast(`${res.count} file(s) uploaded — pick one to run the pipeline`)
      load()
    } catch (e) {
      showToast((e as Error).message || 'Upload failed')
    } finally {
      setUploadBusy(false)
      if (fileRef.current) fileRef.current.value = ''
    }
  }

  const confirmDelete = async () => {
    if (!deleteFor) return
    setDeleting(true)
    try {
      await api.delete(`/media/${deleteFor.id}`)
      setAssets(prev => prev.filter(a => a.id !== deleteFor.id))
      setDeleteFor(null)
      showToast('Asset deleted')
    } catch (e) {
      showToast((e as Error).message || 'Delete failed')
    } finally {
      setDeleting(false)
    }
  }

  const visible = useMemo(() => assets, [assets])

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4 mb-5">
        <div>
          <h1 className="text-2xl font-bold text-white">Media Library</h1>
          <p className="text-xs text-slate-400 mt-1">Upload once, run a cinematic pipeline, attach anywhere — nothing hardcoded.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <input
            ref={fileRef}
            type="file"
            multiple
            accept="image/*,video/mp4,video/webm,model/gltf-binary,.glb"
            className="hidden"
            aria-label="Upload media files"
            onChange={e => uploadFiles(e.target.files)}
          />
          <button
            onClick={() => fileRef.current?.click()}
            disabled={uploadBusy}
            className="target-min inline-flex items-center gap-2 rounded-xl bg-gradient-brand h-11 px-4 text-sm font-bold text-white hover:brightness-110 active:scale-95 transition-all duration-150 disabled:opacity-50"
          >
            {uploadBusy ? (
              <>
                <span className="w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin" aria-hidden />
                Uploading…
              </>
            ) : (
              <>↑ Upload files</>
            )}
          </button>
          <label className="relative">
            <svg className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="Search assets, formats, owners…"
              aria-label="Search assets, formats, owners"
              className="target-min h-11 w-56 rounded-xl bg-slate-900 border border-slate-800 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 transition-all"
            />
          </label>
        </div>
      </div>

      <div className="flex flex-col xl:flex-row gap-5 items-start">
        <div className="flex-1 min-w-0 w-full">
          {/* Filter chips — auto-animated */}
          <div className="flex flex-wrap gap-2 mb-5" ref={chipsRef}>
            {FILTERS.map(f => (
              <button
                key={f}
                onClick={() => setFilter(f)}
                aria-pressed={filter === f}
                className={`target-min inline-flex items-center gap-2 rounded-full px-4 h-11 text-xs font-semibold transition-all duration-200 active:scale-95 ${
                  filter === f ? 'bg-blue-700 text-white shadow-lg shadow-blue-600/25' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800 hover:border-slate-600'
                }`}
              >
                {f}
                <span className={`rounded-full px-1.5 py-0.5 text-[9px] font-black ${filter === f ? 'bg-white/20' : 'bg-slate-800 text-slate-400'}`}>{counts[f] ?? 0}</span>
              </button>
            ))}
          </div>

          {/* Grid — auto-animate on filter */}
          {loading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" aria-busy="true">
              {Array.from({ length: 8 }).map((_, i) => <SkeletonCard key={i} />)}
            </div>
          ) : visible.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-slate-800 py-16 text-center animate-pop">
              <div className="text-3xl mb-2" aria-hidden>🗂️</div>
              <div className="text-sm text-slate-400">
                {search ? (
                  <>No assets match "{search}"{filter !== 'All' ? ` in ${filter}` : ''}</>
                ) : (
                  <>No assets here yet. Upload a photo or video to start.</>
                )}
              </div>
              {!search && (
                <button
                  onClick={() => fileRef.current?.click()}
                  className="target-min mt-4 rounded-xl bg-gradient-brand h-11 px-5 text-sm font-bold text-white hover:brightness-110 active:scale-95 transition-all duration-150"
                >
                  ↑ Upload files
                </button>
              )}
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" ref={gridRef}>
              {visible.map(a => (
                <article key={a.id} className="rounded-2xl border border-slate-800 bg-slate-900 overflow-hidden hover:-translate-y-0.5 hover:border-slate-600 hover:shadow-xl hover:shadow-black/40 transition-all duration-200 group">
                  <div className="relative h-56">
                    <CinematicMedia
                      poster={a.poster}
                      webm={a.webm}
                      mp4={a.mp4}
                      glb={a.glb}
                      alt={a.originalName}
                      onClick={() => setLightbox(a)}
                      className="absolute inset-0"
                    />
                    <div className="absolute top-2.5 left-2.5 flex gap-1.5 z-20">
                      <span className={`rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-widest backdrop-blur-md border ${
                        a.kind === 'image' ? 'bg-sky-500/20 text-sky-300 border-sky-500/30' : a.kind === 'video' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-purple-500/20 text-purple-300 border-purple-500/30'
                      }`}>{a.kind}</span>
                      <span className={`rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-widest backdrop-blur-md border ${
                        a.status === 'ready' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                        : a.status === 'processing' || a.status === 'uploaded' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30'
                        : a.status === 'failed' ? 'bg-red-500/20 text-red-300 border-red-500/30'
                        : 'bg-slate-500/20 text-slate-300 border-slate-500/30'
                      }`}>{a.status}</span>
                    </div>
                    {a.ownerName && (
                      <div className="absolute bottom-3 left-3 z-20 flex items-center gap-1.5 rounded-full bg-slate-950/70 backdrop-blur-md border border-white/10 px-2.5 py-1 text-[9px] font-bold text-slate-300">
                        🔗 {a.ownerName} · {a.slot}
                      </div>
                    )}

                    {/* Progressive blur card footer — text + actions inside blurred frame (no duplicate img fetch) */}
                    <ProgressiveBlurCard
                      dark
                      blurHeight="72%"
                      className="absolute inset-x-0 bottom-0 h-full z-10"
                    >
                      <div className="flex items-end justify-between gap-2">
                        <div className="min-w-0">
                          <div className="text-sm font-semibold text-white truncate drop-shadow">{a.originalName}</div>
                          <div className="text-[10px] text-slate-300 mt-0.5">
                            {a.width}×{a.height} · {formatBytes(a.sizeBytes)}
                          </div>
                        </div>
                      </div>
                      {a.status === 'processing' || a.status === 'uploaded' ? (
                        <div className="mt-2.5">
                          <div className="flex items-center justify-between text-[9px] text-slate-300 mb-1">
                            <span className="font-mono">{a.stage ?? 'working…'}</span>
                            <span className="font-black text-amber-300">{Math.round(a.progress ?? 0)}%</span>
                          </div>
                          <div className="h-1.5 bg-slate-800 rounded-full overflow-hidden" role="progressbar" aria-valuenow={Math.round(a.progress ?? 0)} aria-valuemin={0} aria-valuemax={100} aria-label={`${a.originalName} processing`}>
                            <div className="h-full bg-gradient-brand rounded-full transition-all duration-500" style={{ width: `${a.progress ?? 0}%` }} />
                          </div>
                        </div>
                      ) : a.status === 'failed' ? (
                        <div className="mt-2.5 rounded-lg bg-red-500/10 border border-red-500/30 px-2.5 py-1.5">
                          <div className="text-[9px] font-bold text-red-300">Failed: {a.failureReason || 'pipeline error'}</div>
                        </div>
                      ) : null}
                      <div className="flex gap-2 mt-3.5">
                        <button
                          onClick={() => openWizard(a)}
                          disabled={a.status === 'processing' || a.status === 'uploaded'}
                          className="target-min flex-1 rounded-xl bg-white text-slate-900 h-10 text-[11px] font-bold hover:bg-blue-50 active:scale-95 transition-all duration-150 disabled:opacity-40 disabled:active:scale-100"
                        >
                          {a.status === 'failed' ? '↻ Retry' : '⚡ Pipeline'}
                        </button>
                        <button
                          onClick={() => openAttach(a)}
                          disabled={a.status !== 'ready'}
                          className="target-min flex-1 rounded-xl border border-white/25 bg-white/10 backdrop-blur-md h-10 text-[11px] font-bold text-white hover:border-white/50 hover:bg-white/15 active:scale-95 transition-all duration-150 disabled:opacity-40 disabled:active:scale-100"
                        >
                          🔗 Attach
                        </button>
                        <button
                          onClick={() => setDeleteFor(a)}
                          aria-label={`Delete ${a.originalName}`}
                          className="target-min w-10 shrink-0 rounded-xl border border-red-500/30 bg-red-500/10 h-10 text-[11px] font-bold text-red-300 hover:bg-red-500/20 active:scale-95 transition-all duration-150"
                        >
                          🗑
                        </button>
                      </div>
                    </ProgressiveBlurCard>
                  </div>
                </article>
              ))}
            </div>
          )}
        </div>

        {/* AI-assisted UX research panel — live insights from the backend */}
        <AiAssistPanel className="w-full xl:w-80 shrink-0" />
      </div>

      {/* Pipeline wizard modal */}
      <PipelineWizard
        open={wizardFor !== null}
        onClose={() => setWizardFor(null)}
        assetName={wizardFor?.originalName ?? ''}
        engines={engines}
        onDispatch={dispatch}
        dispatching={dispatching}
      />

      {/* Attach modal */}
      <AttachModal
        open={attachFor !== null}
        onClose={() => setAttachFor(null)}
        assetName={attachFor?.originalName ?? ''}
        registry={registry}
        entities={entities}
        current={attachFor ? { ownerName: attachFor.ownerName, slot: attachFor.slot } : null}
        onAttach={attach}
        onDetach={detach}
        busy={attachBusy}
      />

      {/* Delete confirmation */}
      <ConfirmDialog
        open={deleteFor !== null}
        title="Delete asset"
        message={<>Delete <strong className="text-white">{deleteFor?.originalName}</strong>? This removes the file and all pipeline outputs. This cannot be undone.</>}
        confirmLabel="Delete"
        busy={deleting}
        onConfirm={confirmDelete}
        onCancel={() => setDeleteFor(null)}
      />

      {/* Lightbox preview — proper dialog (focus trap, Escape, scroll lock) */}
      <Modal
        open={lightbox !== null}
        onClose={() => setLightbox(null)}
        title={lightbox?.originalName ?? 'Preview'}
        subtitle={lightbox ? `${lightbox.width}×${lightbox.height} · ${formatBytes(lightbox.sizeBytes)}${lightbox.ownerName ? ` · attached to ${lightbox.ownerName} (${lightbox.slot})` : ''}` : undefined}
        wide
        footer={
          <button
            onClick={() => setLightbox(null)}
            className="target-min rounded-xl border border-slate-700 h-11 px-5 text-sm font-semibold text-slate-300 hover:border-slate-500 hover:text-white transition-all duration-150"
          >
            Close
          </button>
        }
      >
        {lightbox && (
          <div>
            <CinematicMedia
              poster={lightbox.poster}
              webm={lightbox.webm}
              mp4={lightbox.mp4}
              glb={lightbox.glb}
              alt={lightbox.originalName}
              autoplay
              className="aspect-video rounded-2xl border border-slate-700 shadow-2xl"
            />
            <div className="flex gap-1.5 mt-3 flex-wrap">
              {lightbox.derivatives.map(d => (
                <span key={d.kind} className="rounded-full bg-slate-900 border border-slate-700 px-2.5 py-1 text-[9px] font-bold text-slate-400">
                  {d.kind}{d.variant ? ` · ${d.variant}` : ''} · {formatBytes(d.sizeBytes)}
                </span>
              ))}
            </div>
          </div>
        )}
      </Modal>

      {/* Micro toast */}
      {toast && (
        <div role="status" aria-live="polite" className="fixed bottom-6 right-6 z-[60] modal-pop">
          <div className="rounded-xl bg-slate-900 border border-slate-700 shadow-2xl shadow-black/50 px-4 py-3 text-sm text-white flex items-center gap-2.5">
            <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" aria-hidden />
            {toast}
          </div>
        </div>
      )}
    </div>
  )
}
