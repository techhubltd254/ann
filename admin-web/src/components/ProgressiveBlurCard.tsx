import type { ReactNode } from 'react'

interface ProgressiveBlurCardProps {
  image?: string
  alt?: string
  children?: ReactNode
  className?: string
  /** Swaps the blur frame fill to deep black for a premium dark glass effect */
  dark?: boolean
  /** Height of the blurred container as a CSS value */
  blurHeight?: string
  imageClassName?: string
}

/**
 * Progressive-blur glass card.
 * Base image fills the frame; a container overlays the bottom portion with a
 * 0→80px gradient-masked backdrop blur. Text and actions live inside the
 * blurred container. Swap `dark` for deep-black glassmorphism.
 * Omit `image` to use only the frame (when the media is already rendered
 * underneath) — avoids double image downloads.
 */
export default function ProgressiveBlurCard({
  image,
  alt = '',
  children,
  className = '',
  dark = false,
  blurHeight = '60%',
  imageClassName = ''
}: ProgressiveBlurCardProps) {
  return (
    <div className={`relative overflow-hidden ${className}`}>
      {image && (
        <img
          src={image}
          alt={alt}
          loading="lazy"
          className={`absolute inset-0 h-full w-full object-cover ${imageClassName}`}
        />
      )}
      <div
        className={`progressive-blur-frame ${dark ? 'progressive-blur-frame--deep' : 'progressive-blur-frame--light'}`}
        style={{ height: blurHeight }}
        aria-hidden
      />
      <div className="relative z-10 flex h-full flex-col justify-end p-4 pt-24">
        {children}
      </div>
    </div>
  )
}
