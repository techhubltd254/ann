import { useSearchParams } from 'react-router-dom'

export default function Result() {
  const [params] = useSearchParams()
  const route = params.get('route')
  const org = params.get('org') ?? 'your organisation'

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 px-6 py-5">
        <a href="/" className="text-sm text-slate-400 hover:text-white">← Back to home</a>
      </header>
      <main className="mx-auto max-w-2xl px-6 py-16">
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-8">
          <h1 className="text-2xl font-bold text-white">
            {route === 'OWN_SERVER' ? 'You are routed to a dedicated county server' : 'Application received — queued for review'}
          </h1>
          <p className="mt-3 text-slate-300">
            {route === 'OWN_SERVER' ? (
              <>
                Thank you, <b className="text-white">{org}</b>. Based on your answers your organisation qualifies for a
                <b className="text-white"> dedicated county server</b> — the most resilient option, keeping your data
                and operations fully under your county's control.
              </>
            ) : (
              <>
                Thank you, <b className="text-white">{org}</b>. Your application is now with the KICC onboarding team for
                review. Once approved you will receive sign-in details for the{' '}
                <b className="text-white">shared portfolio template</b>, where you can publish products, services and
                exhibition listings right away.
              </>
            )}
          </p>

          {route === 'OWN_SERVER' && (
            <div className="mt-6 rounded-lg border border-blue-800 bg-blue-950/50 p-5 text-sm text-slate-200">
              <p className="font-semibold text-blue-200">Next steps for a dedicated server</p>
              <ul className="mt-2 list-disc space-y-1 pl-5">
                <li>KICC ops will contact you to provision your county server.</li>
                <li>You receive the server package (engine + install guide) on a distribution drive.</li>
                <li>Your officers install the mobile app and point it at your server.</li>
              </ul>
            </div>
          )}

          {route !== 'OWN_SERVER' && (
            <div className="mt-6 rounded-lg border border-slate-700 bg-slate-950/50 p-5 text-sm text-slate-200">
              <p className="font-semibold text-slate-100">What happens next</p>
              <ul className="mt-2 list-disc space-y-1 pl-5">
                <li>Review typically takes 2–5 working days.</li>
                <li>You will be notified by email with your sign-in details.</li>
                <li>Questions? Contact the KICC digital economy team.</li>
              </ul>
            </div>
          )}

          <a href="/" className="mt-8 inline-block rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500">
            Return home
          </a>
        </div>
      </main>
    </div>
  )
}
