import { Component, type ReactNode } from 'react'

interface Props {
  children: ReactNode
}

interface State {
  error: Error | null
}

/**
 * App-wide error boundary — a crash in one page shows a styled retry screen
 * instead of unmounting the whole SPA.
 */
export default class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null }

  static getDerivedStateFromError(error: Error): State {
    return { error }
  }

  render() {
    if (this.state.error) {
      return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-surface p-6">
          <div className="card w-full max-w-md p-6 text-center">
            <div className="text-3xl mb-3" aria-hidden>⚠️</div>
            <h1 className="text-lg font-bold text-white">Something went wrong</h1>
            <p className="mt-2 text-sm text-slate-400 break-words">
              {this.state.error.message || 'An unexpected error occurred.'}
            </p>
            <button
              onClick={() => this.setState({ error: null })}
              className="target-min mt-5 rounded-xl bg-gradient-brand px-6 h-11 font-semibold text-white hover:brightness-110 active:scale-95 transition-all duration-150"
            >
              Try again
            </button>
          </div>
        </div>
      )
    }
    return this.props.children
  }
}
