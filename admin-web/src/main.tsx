import { StrictMode, useEffect } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter, useNavigate } from 'react-router-dom'
import App from './App'
import { AuthProvider } from './lib/auth'
import { LocaleProvider } from './lib/i18n'
import { SESSION_EXPIRED_EVENT } from './lib/api'
import ErrorBoundary from './components/ErrorBoundary'
import './index.css'

/** Redirects to /login?expired=1 when a session dies (401 survived refresh). */
function SessionExpiryListener() {
  const navigate = useNavigate()
  useEffect(() => {
    const onExpired = () => navigate('/login?expired=1', { replace: true })
    window.addEventListener(SESSION_EXPIRED_EVENT, onExpired)
    return () => window.removeEventListener(SESSION_EXPIRED_EVENT, onExpired)
  }, [navigate])
  return null
}

function Root() {
  return (
    <StrictMode>
      <BrowserRouter>
        <LocaleProvider>
          <AuthProvider>
            <SessionExpiryListener />
            <ErrorBoundary>
              <App />
            </ErrorBoundary>
          </AuthProvider>
        </LocaleProvider>
      </BrowserRouter>
    </StrictMode>
  )
}

createRoot(document.getElementById('root')!).render(<Root />)
