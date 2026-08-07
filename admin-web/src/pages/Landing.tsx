export default function Landing() {
  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 px-6 py-5 flex items-center justify-between">
        <div>
          <span className="text-xl font-bold text-white">KICC</span>
          <span className="ml-2 text-sm text-slate-400">Digital Economy Platform</span>
        </div>
        <a href="/apply" className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
          Apply to exhibit
        </a>
        <a href="/complaint" className="rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-500">
          Report a problem
        </a>
      </header>

      <main className="mx-auto max-w-3xl px-6 py-16">
        <h1 className="text-4xl font-bold leading-tight">Get your business on the national digital economy platform</h1>
        <p className="mt-4 text-lg text-slate-300">
          The KICC Digital Economy Platform connects Kenyan organisations — counties, businesses, cooperatives and
          exhibitors — with a national digital marketplace. Exhibitors get a managed portfolio to showcase products,
          services, booths and exhibition listings, with offline-first mobile tools for your field officers.
        </p>

        <div className="mt-10 grid gap-4 sm:grid-cols-3">
          {[
            ['Showcase', 'Products, services, exhibitions and booths under one national directory.'],
            ['Offline-first', 'Field officers capture data on any phone, even with poor connectivity.'],
            ['County-led', 'Your data stays with your county; syncs when it is safe to do so.']
          ].map(([t, d]) => (
            <div key={t} className="rounded-xl border border-slate-800 bg-slate-900 p-5">
              <h3 className="font-semibold text-white">{t}</h3>
              <p className="mt-2 text-sm text-slate-400">{d}</p>
            </div>
          ))}
        </div>

        <div className="mt-10 rounded-xl border border-slate-800 bg-slate-900 p-6">
          <h2 className="text-lg font-semibold text-white">How onboarding works</h2>
          <ol className="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-300">
            <li>Apply with your organisation details and answer a few qualification questions.</li>
            <li>Large or digitally-ready organisations are routed to a <b className="text-white">dedicated county server</b>.</li>
            <li>Everyone else is placed on the <b className="text-white">shared portfolio template</b>, pending review by KICC.</li>
            <li>Your team signs in on mobile and starts publishing.</li>
          </ol>
          <a href="/apply" className="mt-6 inline-block rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500">
            Start application
          </a>
        </div>
      </main>
    </div>
  )
}
