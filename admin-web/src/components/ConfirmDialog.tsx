import { useEffect, useState, type ReactNode } from 'react'
import Modal from './Modal'

interface ConfirmDialogProps {
  open: boolean
  title: string
  message: ReactNode
  confirmLabel?: string
  cancelLabel?: string
  danger?: boolean
  busy?: boolean
  onConfirm: () => void
  onCancel: () => void
}

/**
 * Styled confirmation dialog (replaces native confirm()).
 * Reuses Modal for focus trap, Escape, scroll lock and focus restore.
 */
export default function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  danger = true,
  busy = false,
  onConfirm,
  onCancel
}: ConfirmDialogProps) {
  const [armed, setArmed] = useState(false)

  useEffect(() => {
    if (open) setArmed(false)
  }, [open])

  const confirm = () => {
    setArmed(true)
    onConfirm()
  }

  return (
    <Modal
      open={open}
      onClose={busy ? () => {} : onCancel}
      title={title}
      footer={
        <>
          <button
            onClick={onCancel}
            disabled={busy}
            className="btn-ghost h-11 px-5 rounded-xl"
          >
            {cancelLabel}
          </button>
          <button
            onClick={confirm}
            disabled={busy}
            className={`rounded-xl h-11 px-6 font-semibold text-white transition-all duration-150 disabled:opacity-50 ${
              danger ? 'bg-red-600 hover:bg-red-500' : 'bg-blue-600 hover:bg-blue-500'
            }`}
          >
            {busy ? 'Working…' : confirmLabel}
          </button>
        </>
      }
    >
      <div className="text-sm text-slate-300 leading-relaxed">{message}</div>
    </Modal>
  )
}
