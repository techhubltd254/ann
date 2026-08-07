import { useState } from 'react'

const CATEGORIES = [
  ['BILLING', 'Billing or payment'],
  ['BOOKING', 'Booth booking'],
  ['LISTING', 'Listing or content'],
  ['DATA', 'County data accuracy'],
  ['SUPPORT', 'Support request'],
  ['OTHER', 'Something else']
]

export default function Complaint() {
  const [form, setForm] = useState({ category: 'SUPPORT', subject: '', message: '', contactName: '', contactEmail: '' })
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [done, setDone] = useState<{ referenceNo: string; status: string } | null>(null)

  const submit = async () => {
    setBusy(true)
    setError('')
    try {
      const res = await fetch('/api/public/complaints', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form)
      })
      const body = await res.json().catch(() => ({}))
      if (!res.ok) throw new Error(body.error ?? `HTTP ${res.status}`)
      setDone(body)
    } catch (e) {
      setError((e as Error).message)
    } finally {
      setBusy(false)
    }
  }

  const ready = form.subject.trim().length >= 3 && form.message.trim().length >= 10

  if (done) {
    return (
      <div className="min-h-screen bg-slate-950 text-slate-100">
        <header className="border-b border-slate-800 px-6 py-5">
          <a href="/" className="text-sm text-slate-400 hover:text-white">← Back to home</a>
        </header>
        <main className="mx-auto max-w-xl px-6 py-10">
          <div className="rounded-xl border border-green-800 bg-green-950/40 p-8 text-center">
            <div className="text-2xl font-bold text-green-300">Complaint received</div>
            <div className="mt-4 text-sm text-slate-300">Reference number</div>
            <div className="mt-1 font-mono text-xl text-white">{done.referenceNo}</div>
            <div className="mt-1 text-xs text-slate-400">Status: {done.status}</div>
            <p className="mt-6 text-sm text-slate-400">
              Keep your reference number to <a href={`/track/${done.referenceNo}`} className="text-blue-400 hover:text-blue-300 underline">track your status</a>. Our team aims to respond within 72 hours
              (24 hours for billing or booking issues).
            </p>
            <a href="/complaint" className="mt-6 inline-block text-sm text-blue-400 hover:text-blue-300">Submit another</a>
          </div>
        </main>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 px-6 py-5">
        <a href="/" className="text-sm text-slate-400 hover:text-white">← Back to home</a>
      </header>

      <main className="mx-auto max-w-2xl px-6 py-10">
        <h1 className="text-2xl font-bold text-white">Report a problem</h1>
        <p className="mt-2 text-sm text-slate-400">
          Tell us what went wrong — you will get a reference number you can use to track resolution.
        </p>

        <div className="mt-6 rounded-xl border border-slate-800 bg-slate-900 p-6">
          <div className="space-y-4">
            <label className="block">
              <span className="text-sm text-slate-300">Category</span>
              <select
                value={form.category}
                onChange={(e) => setForm({ ...form, category: e.target.value })}
                className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
              >
                {CATEGORIES.map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
            </label>
            <label className="block">
              <span className="text-sm text-slate-300">Subject</span>
              <input
                type="text"
                value={form.subject}
                onChange={(e) => setForm({ ...form, subject: e.target.value })}
                placeholder="Short summary (min 3 characters)"
                className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
              />
            </label>
            <label className="block">
              <span className="text-sm text-slate-300">Details</span>
              <textarea
                value={form.message}
                onChange={(e) => setForm({ ...form, message: e.target.value })}
                rows={5}
                placeholder="What happened, when, and what you expected (min 10 characters)"
                className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
              />
            </label>
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="block">
                <span className="text-sm text-slate-300">Your name (optional)</span>
                <input
                  type="text"
                  value={form.contactName}
                  onChange={(e) => setForm({ ...form, contactName: e.target.value })}
                  className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
                />
              </label>
              <label className="block">
                <span className="text-sm text-slate-300">Email (optional)</span>
                <input
                  type="email"
                  value={form.contactEmail}
                  onChange={(e) => setForm({ ...form, contactEmail: e.target.value })}
                  className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
                />
              </label>
            </div>

            {error && <div className="rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{error}</div>}

            <button
              onClick={submit}
              disabled={!ready || busy}
              className="mt-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {busy ? 'Submitting…' : 'Submit complaint'}
            </button>
          </div>
        </div>
      </main>
    </div>
  )
}
