import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { api } from '../lib/api'
import { useAuth } from '../lib/auth'

interface County { id: number; name: string; slug: string; capital: string; tagline: string | null; description: string | null; iconEmoji: string | null; isActive: boolean; economicZone: string; formerProvince: string; population2024: number | null; areaKm2: number | null; warmestMonth: string | null; coolestMonth: string | null; rainySeason: string | null; drySeason: string | null; tourismHighlights: string | null; primarySectors: string | null; region: string | null; profileImage: string | null }
interface Sector { id: number; name: string; slug: string; emoji: string | null; description: string | null; isActive: boolean; sortOrder: number }
interface CountySectorLink { id: number; countyId: number; sectorId: number }

const RESOURCES = ['attractions','hotels','farms','products']
const RESOURCE_FIELDS: Record<string, string[]> = {
  attractions: ['name','category','description','location','entryFee','openingHours','contact','latitude','longitude'],
  hotels: ['name','category','starRating','description','location','phone','email','website','priceRangeMin','priceRangeMax'],
  farms: ['name','type','description','location','contact','sizeAcres','mainCrops'],
  products: ['name','category','description','price','unit','status'],
}

export default function CountyDashboard() {
  const { slug } = useParams<{ slug: string }>()
  const { me } = useAuth()
  const nav = useNavigate()
  const [county, setCounty] = useState<County | null>(null)
  const [allSectors, setAllSectors] = useState<Sector[]>([])
  const [links, setLinks] = useState<CountySectorLink[]>([])
  const [content, setContent] = useState<Record<string, any[]>>({})
  const [exhibitions, setExhibitions] = useState<any[]>([])
  const [media, setMedia] = useState<any[]>([])
  const [tab, setTab] = useState('overview')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [msg, setMsg] = useState('')
  const [busy, setBusy] = useState(false)

  // Edit state
  const [profileEdit, setProfileEdit] = useState<Record<string,string>>({})
  const [newSectorId, setNewSectorId] = useState('')
  const [newResource, setNewResource] = useState<Record<string,Record<string,string>>>({})
  const [editResource, setEditResource] = useState<{type: string; id: number; fields: Record<string,string>} | null>(null)
  const [uploadFile, setUploadFile] = useState<File | null>(null)

  const fmt = (n: number) => n?.toLocaleString('en-KE') || '0'

  const loadAll = () => {
    if (!slug || !me) return
    setLoading(true)
    Promise.all([
      api.get<County[]>('/data/counties').then(cs => cs.find(c => c.slug === slug) as County),
      api.get<Sector[]>('/data/sectors'),
      api.get<CountySectorLink[]>(`/data/county-sectors?countySlug=${slug}`),
      api.get<any[]>('/media'),
      api.get<any[]>(`/data/exhibitions?countySlug=${slug}`).catch(() => []),
      ...RESOURCES.map(r => api.get<any[]>(`/data/${r}?countySlug=${slug}`).catch(() => []))
    ]).then(([c, sectors, countyLinks, mediaData, ex, ...contentData]) => {
      setCounty(c || null)
      setAllSectors(sectors || [])
      setLinks(countyLinks || [])
      setMedia(mediaData || [])
      setExhibitions(ex || [])
      const cm: Record<string, any[]> = {}
      RESOURCES.forEach((r, i) => { cm[r] = contentData[i] as any[] })
      setContent(cm)
      if (c) setProfileEdit({ tagline: c.tagline||'', description: c.description||'', tourismHighlights: c.tourismHighlights||'', primarySectors: c.primarySectors||'', region: c.region||'', warmestMonth: c.warmestMonth||'', coolestMonth: c.coolestMonth||'', rainySeason: c.rainySeason||'', drySeason: c.drySeason||'' })
    }).catch(e => setError((e as Error).message))
    .finally(() => setLoading(false))
  }

  useEffect(() => { loadAll() }, [slug, me])

  const act = async (fn: () => Promise<any>, okMsg: string) => {
    setBusy(true); setError('')
    try { await fn(); setMsg(okMsg); loadAll() }
    catch (e) { setError((e as Error).message) }
    setBusy(false)
  }

  const saveProfile = () => act(() => api.put(`/data/counties/${county!.id}`, profileEdit), 'Profile updated')

  const addSectorLink = () => act(() => api.post('/data/county-sectors', { countyId: county!.id, sectorId: Number(newSectorId) }), 'Sector linked')
  const removeSectorLink = (id: number) => act(() => api.delete(`/data/county-sectors/${id}`), 'Link removed')

  const addContent = (resource: string) => {
    const fields = newResource[resource] || {}
    if (!fields.name) { setError('Name is required'); return }
    act(() => api.post(`/data/${resource}`, { ...fields, countyId: county!.id }), `${resource} added`)
    setNewResource({...newResource, [resource]: {}})
  }

  const updateContent = () => {
    if (!editResource) return
    act(() => api.put(`/data/${editResource.type}/${editResource.id}`, editResource.fields), 'Updated')
    setEditResource(null)
  }

  const deleteContent = (resource: string, id: number) => {
    if (!confirm('Delete this item?')) return
    act(() => api.delete(`/data/${resource}/${id}`), 'Deleted')
  }

  const togglePublish = (resource: string, id: number, published: boolean) => {
    act(() => api.patch(`/data/${resource}/${id}/publish`, { published: !published }), 'Toggled')
  }

  const uploadMedia = () => {
    if (!uploadFile) return
    const form = new FormData()
    form.append('file', uploadFile)
    act(async () => {
      await fetch('/api/media', { method: 'POST', body: form, headers: { Authorization: `Bearer ${(window as any).__kicc_token || ''}` } })
    }, 'Uploaded')
    setUploadFile(null)
  }

  const deleteMedia = (key: string) => {
    if (!confirm('Delete this media file?')) return
    act(() => api.delete(`/media/${encodeURIComponent(key)}`), 'Deleted')
  }

  if (loading) return <div className="flex items-center justify-center min-h-[400px] text-white/40 font-sans text-sm">Loading {slug}...</div>
  if (!county) return <div className="p-6 text-red-400 font-sans">County not found: {slug}</div>

  const tabs = ['overview', 'sectors', 'content', 'media', 'exhibitions', 'profile']

  return (
    <div className="font-sans" style={{ fontFamily: "'Montserrat', sans-serif" }}>
      {msg && <div className="mb-4 px-4 py-2.5 bg-kicc-green/15 border border-kicc-green/30 rounded-xl text-sm text-kicc-green font-semibold">{msg} <button onClick={() => setMsg('')} className="ml-3 text-xs underline">dismiss</button></div>}
      {error && <div className="mb-4 px-4 py-2.5 bg-red-400/10 border border-red-400/30 rounded-xl text-sm text-red-400 font-semibold">{error}</div>}

      <div className="flex items-center gap-4 mb-6">
        <button onClick={() => nav('/admin')} className="text-white/40 hover:text-white text-sm font-semibold">&larr; Admin</button>
        <span className="text-2xl">{county.iconEmoji || '🏙️'}</span>
        <div>
          <h1 className="text-xl font-black text-white">{county.name} County</h1>
          <p className="text-xs text-white/40">{county.capital} · {county.economicZone}</p>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 mb-6 border-b border-white/[0.06] pb-0 overflow-x-auto">
        {tabs.map(t => (
          <button key={t} onClick={() => setTab(t)}
            className={`px-5 py-2.5 text-sm font-bold capitalize rounded-t-xl whitespace-nowrap transition-all ${
              tab === t ? 'bg-kicc-gold/15 text-kicc-gold border-b-2 border-kicc-gold' : 'text-white/40 hover:text-white/70'
            }`}>{t}</button>
        ))}
      </div>

      {/* OVERVIEW */}
      {tab === 'overview' && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {[{v:links.length,l:'Sectors',c:'#FFCD05'},{v:Object.values(content).reduce((s,a)=>s+a.filter(r=>r.isPublished).length,0),l:'Published',c:'#2D6A4F'},{v:exhibitions.length,l:'Exhibitions',c:'#901C1E'},{v:media.length,l:'Media Files',c:'#0B1E57'}].map((k,i)=>(
            <div key={i} className="bg-kicc-card border border-white/[0.06] rounded-2xl p-4">
              <div className="text-2xl font-black" style={{color:k.c}}>{k.v}</div>
              <div className="text-[11px] font-semibold text-white/40 uppercase tracking-wider mt-1">{k.l}</div>
            </div>
          ))}
          <div className="col-span-2 md:col-span-4 bg-kicc-card border border-white/[0.06] rounded-2xl p-5">
            <h3 className="font-bold text-white mb-2">About {county.name}</h3>
            <p className="text-sm text-white/50">{county.description || '—'}</p>
            <div className="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
              {[['Capital',county.capital],['Economy',county.economicZone],['Region',county.region||'—'],['Area',`${county.areaKm2||'—'} km²`]].map(([l,v])=>(
                <div key={l}><span className="text-white/30">{l}</span><div className="text-white font-semibold mt-0.5">{v}</div></div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* SECTORS */}
      {tab === 'sectors' && (
        <div>
          <div className="flex gap-3 mb-4 items-end">
            <select value={newSectorId} onChange={e => setNewSectorId(e.target.value)} className="bg-kicc-surface border border-white/[0.08] rounded-xl px-3 py-2 text-sm text-white outline-none focus:border-kicc-gold/50">
              <option value="">Add sector...</option>
              {allSectors.filter(s => s.isActive && !links.find(l => l.sectorId === s.id)).map(s => (
                <option key={s.id} value={s.id}>{s.emoji} {s.name}</option>
              ))}
            </select>
            <button onClick={addSectorLink} disabled={busy||!newSectorId} className="bg-kicc-gold text-kicc-navy font-bold px-4 py-2 rounded-xl text-sm hover:brightness-110 disabled:opacity-30">Link Sector</button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {links.map(link => {
              const s = allSectors.find(x => x.id === link.sectorId)
              if (!s) return null
              return (
                <div key={link.id} className="bg-kicc-card border border-white/[0.06] rounded-2xl p-4 group">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className="text-2xl">{s.emoji||'🏭'}</span>
                      <div>
                        <div className="font-bold text-white text-sm">{s.name}</div>
                        <div className="text-[11px] text-white/40">{s.description?.slice(0,60)}</div>
                      </div>
                    </div>
                    <button onClick={() => removeSectorLink(link.id)} className="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-300 text-lg font-bold transition-all">&times;</button>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      )}

      {/* CONTENT */}
      {tab === 'content' && (
        <div className="space-y-6">
          {RESOURCES.map(resource => (
            <div key={resource} className="bg-kicc-card border border-white/[0.06] rounded-2xl p-5">
              <h3 className="font-bold text-white capitalize mb-3">{resource} ({(content[resource]||[]).length})</h3>

              {/* Add form */}
              <div className="flex flex-wrap gap-2 mb-4 items-end">
                <input placeholder="Name" value={newResource[resource]?.name||''} onChange={e => setNewResource({...newResource,[resource]:{...(newResource[resource]||{}),name:e.target.value}})} className="bg-kicc-surface border border-white/[0.08] rounded-lg px-3 py-1.5 text-xs text-white w-32 outline-none focus:border-kicc-gold/50" />
                {(RESOURCE_FIELDS[resource]||[]).filter(f=>f!=='name').slice(0,2).map(f => (
                  <input key={f} placeholder={f} value={newResource[resource]?.[f]||''} onChange={e => setNewResource({...newResource,[resource]:{...(newResource[resource]||{}),[f]:e.target.value}})} className="bg-kicc-surface border border-white/[0.08] rounded-lg px-3 py-1.5 text-xs text-white w-28 outline-none focus:border-kicc-gold/50" />
                ))}
                <button onClick={() => addContent(resource)} disabled={busy} className="bg-kicc-gold text-kicc-navy font-bold px-3 py-1.5 rounded-lg text-xs hover:brightness-110 disabled:opacity-30">+ Add</button>
              </div>

              {/* Edit modal */}
              {editResource && editResource.type === resource && (
                <div className="mb-4 p-4 bg-kicc-surface rounded-xl border border-kicc-gold/20">
                  <div className="flex flex-wrap gap-2">
                    {(RESOURCE_FIELDS[resource]||[]).map(f => (
                      <input key={f} placeholder={f} value={editResource.fields[f]||''} onChange={e => setEditResource({...editResource,fields:{...editResource.fields,[f]:e.target.value}})} className="bg-kicc-dark border border-white/[0.08] rounded-lg px-3 py-1.5 text-xs text-white w-32 outline-none focus:border-kicc-gold/50" />
                    ))}
                    <button onClick={updateContent} disabled={busy} className="bg-kicc-green px-3 py-1.5 rounded-lg text-xs font-bold">Save</button>
                    <button onClick={() => setEditResource(null)} className="text-white/40 hover:text-white px-2 py-1 text-xs">Cancel</button>
                  </div>
                </div>
              )}

              {/* Row list */}
              <div className="space-y-1 max-h-80 overflow-y-auto">
                {(content[resource]||[]).map(row => (
                  <div key={row.id} className="flex items-center justify-between py-1.5 px-3 rounded-lg bg-white/[0.02] hover:bg-white/[0.04] group text-xs">
                    <div className="flex items-center gap-2 truncate">
                      <span className="text-white/80 truncate max-w-[200px]">{row.name}</span>
                      {row.price != null && <span className="text-kicc-gold font-bold">KES {row.price}</span>}
                      <span className={`px-1.5 py-0.5 rounded-full text-[9px] font-bold ${row.isPublished?'bg-kicc-green/20 text-kicc-green':'bg-white/5 text-white/30'}`}>{row.isPublished?'Pub':'Draft'}</span>
                    </div>
                    <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                      <button onClick={() => setEditResource({type:resource,id:row.id,fields:Object.fromEntries((RESOURCE_FIELDS[resource]||[]).map(f=>[f,row[f]||'']))})} className="text-blue-400 hover:text-blue-300 px-1.5 py-0.5 text-[10px]">edit</button>
                      <button onClick={() => togglePublish(resource,row.id,row.isPublished)} className="text-kicc-gold hover:text-yellow-300 px-1.5 py-0.5 text-[10px]">{row.isPublished?'unpub':'pub'}</button>
                      <button onClick={() => deleteContent(resource,row.id)} className="text-red-400 hover:text-red-300 px-1.5 py-0.5 text-[10px]">del</button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* MEDIA */}
      {tab === 'media' && (
        <div>
          <div className="flex gap-3 mb-4 items-end">
            <input type="file" accept="image/*,video/*" onChange={e => setUploadFile(e.target.files?.[0] || null)} className="text-xs text-white/40 file:bg-kicc-gold file:text-kicc-navy file:border-none file:rounded-lg file:px-3 file:py-1.5 file:font-bold file:text-xs file:cursor-pointer file:hover:brightness-110" />
            <button onClick={uploadMedia} disabled={busy||!uploadFile} className="bg-kicc-gold text-kicc-navy font-bold px-4 py-2 rounded-xl text-sm hover:brightness-110 disabled:opacity-30">Upload</button>
          </div>
          <div className="grid grid-cols-3 md:grid-cols-6 gap-3">
            {media.map(m => (
              <div key={m.key} className="group relative bg-kicc-card border border-white/[0.06] rounded-xl overflow-hidden aspect-square">
                {m.contentType?.startsWith('video') ? (
                  <div className="w-full h-full flex items-center justify-center bg-kicc-surface text-3xl">🎬</div>
                ) : (
                  <img src={`/api/media/${m.key}`} alt={m.originalName} className="w-full h-full object-cover" />
                )}
                <button onClick={() => deleteMedia(m.key)} className="absolute top-1 right-1 bg-red-600/80 hover:bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-all">&times;</button>
                <div className="absolute bottom-0 left-0 right-0 bg-black/60 p-1 text-[9px] text-white/60 truncate">{m.originalName?.slice(0,20)}</div>
              </div>
            ))}
          </div>
          {media.length === 0 && <p className="text-white/40 text-sm py-8 text-center">No media uploaded yet</p>}
        </div>
      )}

      {/* EXHIBITIONS */}
      {tab === 'exhibitions' && (
        <div className="space-y-3">
          {exhibitions.map(ex => (
            <div key={ex.id} className="bg-kicc-card border border-white/[0.06] rounded-2xl p-5">
              <div className="flex justify-between"><h3 className="font-bold text-white">{ex.name}</h3><span className={`text-[10px] font-bold px-3 py-1 rounded-full ${ex.status==='published'?'bg-kicc-green/20 text-kicc-green':'bg-white/5 text-white/30'}`}>{ex.status}</span></div>
              <p className="text-xs text-white/40 mt-1">{ex.description?.slice(0,150)}</p>
              <div className="mt-2 flex gap-4 text-xs text-white/30">{ex.startDate&&<span>📅 {ex.startDate.slice(0,10)}→{ex.endDate?.slice(0,10)}</span>}{ex.venueId&&<span>📍 Venue #{ex.venueId}</span>}</div>
            </div>
          ))}
        </div>
      )}

      {/* PROFILE */}
      {tab === 'profile' && (
        <div className="bg-kicc-card border border-white/[0.06] rounded-2xl p-5 max-w-2xl">
          <h3 className="font-bold text-white mb-4">Edit {county.name} Profile</h3>
          <div className="grid grid-cols-2 gap-3">
            {Object.entries(profileEdit).map(([k,v]) => (
              <label key={k} className="block col-span-2">
                <span className="text-[10px] text-white/40 uppercase font-bold">{k}</span>
                {k === 'description' || k === 'tourismHighlights' ? (
                  <textarea value={v} onChange={e => setProfileEdit({...profileEdit,[k]:e.target.value})} rows={3} className="w-full bg-kicc-surface border border-white/[0.08] rounded-xl px-3 py-2 text-sm text-white mt-1 outline-none focus:border-kicc-gold/50 resize-none" />
                ) : (
                  <input value={v} onChange={e => setProfileEdit({...profileEdit,[k]:e.target.value})} className="w-full bg-kicc-surface border border-white/[0.08] rounded-xl px-3 py-2 text-sm text-white mt-1 outline-none focus:border-kicc-gold/50" />
                )}
              </label>
            ))}
          </div>
          <button onClick={saveProfile} disabled={busy} className="mt-4 bg-kicc-gold text-kicc-navy font-bold px-6 py-2.5 rounded-xl text-sm hover:brightness-110 disabled:opacity-30 transition-all">
            {busy ? 'Saving...' : 'Save Profile'}
          </button>
        </div>
      )}
    </div>
  )
}
