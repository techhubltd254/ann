import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'

const QUESTIONS: { key: string; label: string; options: string[]; hint: string }[] = [
  { key: 'employees', label: 'How many people does your organisation employ?', options: ['<10', '10-50', '50-200', '200+'], hint: 'Scales: micro, small, medium, large' },
  { key: 'eventsPerYear', label: 'How many exhibition or trade events do you run or join per year?', options: ['0', '1-3', '4+'], hint: '' },
  { key: 'listings', label: 'How many products or services would you list?', options: ['1-10', '11-50', '50+'], hint: '' },
  { key: 'itStaff', label: 'Do you have dedicated IT staff?', options: ['none', 'part-time', 'dedicated'], hint: 'None, part-time support, or a dedicated IT team' },
  { key: 'connectivity', label: 'How reliable is internet in your county operations?', options: ['poor', 'average', 'good'], hint: '' }
]

export default function Apply() {
  const nav = useNavigate()
  const [step, setStep] = useState(1)
  const [details, setDetails] = useState({ orgName: '', contactName: '', email: '', phone: '', countySlug: '' })
  const [answers, setAnswers] = useState<Record<string, string>>({})
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [countyList, setCountyList] = useState<{ id: number; name: string; slug: string }[]>([])

  useEffect(() => {
    fetch('/api/data/public/counties')
      .then(r => r.json())
      .then(setCountyList)
      .catch(() => {})
  }, [])

  const submit = async () => {
    setBusy(true)
    setError('')
    try {
      const res = await fetch('/api/onboarding/apply', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...details, email: details.email.trim().toLowerCase(), answers })
      })
      const body = await res.json().catch(() => ({}))
      if (!res.ok) throw new Error(body.error ?? `HTTP ${res.status}`)
      nav(`/result?route=${body.route}&score=${body.score}&org=${encodeURIComponent(details.orgName)}`, { replace: true })
    } catch (e) {
      setError((e as Error).message)
    } finally {
      setBusy(false)
    }
  }

  const ready =
    details.orgName.trim() !== '' &&
    details.contactName.trim() !== '' &&
    details.email.includes('@') &&
    Object.keys(answers).length === QUESTIONS.length

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 px-6 py-5">
        <a href="/" className="text-sm text-slate-400 hover:text-white">← Back to home</a>
      </header>

      <main className="mx-auto max-w-2xl px-6 py-10">
        <h1 className="text-2xl font-bold text-white">Exhibitor application</h1>
        <div className="mt-2 text-xs text-slate-500">Step {step} of 2</div>

        <div className="mt-6 rounded-xl border border-slate-800 bg-slate-900 p-6">
          {step === 1 && (
            <div className="space-y-4">
              <h2 className="font-semibold text-white">Organisation details</h2>
              {[
                ['orgName', 'Organisation name', 'text'],
                ['contactName', 'Contact person (full name)', 'text'],
                ['email', 'Work email', 'email'],
                ['phone', 'Phone (optional)', 'tel'],
                ['countySlug', 'County (slug, e.g. kilifi)', 'select']
              ].map(([key, label, type]) => (
                <label key={key} className="block">
                  <span className="text-sm text-slate-300">{label}</span>
                  {type === 'select' ? (
                    <select
                      value={details[key as keyof typeof details]}
                      onChange={(e) => setDetails({ ...details, [key]: e.target.value })}
                      className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
                    >
                      <option value="">Select a county</option>
                      {countyList.map(c => <option key={c.slug} value={c.slug}>{c.name}</option>)}
                    </select>
                  ) : (
                    <input
                      type={type}
                      value={details[key as keyof typeof details]}
                      onChange={(e) => setDetails({ ...details, [key]: e.target.value })}
                      className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
                    />
                  )}
                </label>
              ))}
              <button onClick={() => setStep(2)} className="mt-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">
                Continue
              </button>
            </div>
          )}

          {step === 2 && (
            <div className="space-y-5">
              <h2 className="font-semibold text-white">Qualification questions</h2>
              {QUESTIONS.map((q) => (
                <div key={q.key}>
                  <span className="text-sm text-slate-200">{q.label}</span>
                  {q.hint && <span className="ml-2 text-xs text-slate-500">{q.hint}</span>}
                  <div className="mt-2 flex flex-wrap gap-2">
                    {q.options.map((o) => (
                      <button
                        key={o}
                        onClick={() => setAnswers({ ...answers, [q.key]: o })}
                        className={`rounded-full border px-4 py-1.5 text-sm ${
                          answers[q.key] === o
                            ? 'border-blue-500 bg-blue-600 text-white'
                            : 'border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-500'
                        }`}
                      >
                        {o}
                      </button>
                    ))}
                  </div>
                </div>
              ))}
              {error !== '' && <p className="text-sm text-red-400">{error}</p>}
              <div className="flex gap-3 pt-2">
                <button onClick={() => setStep(1)} className="rounded-lg border border-slate-700 px-5 py-2.5 text-sm text-slate-300 hover:border-slate-500">
                  Back
                </button>
                <button
                  onClick={submit}
                  disabled={!ready || busy}
                  className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                >
                  {busy ? 'Submitting…' : 'Submit application'}
                </button>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  )
}
