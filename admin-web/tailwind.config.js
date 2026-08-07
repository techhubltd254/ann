/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'Manrope', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Manrope', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif']
      },
      colors: {
        kicc: {
          50: '#eef9f2',
          100: '#d6f0e0',
          200: '#ade1c2',
          300: '#7ccb9d',
          400: '#4aae77',
          500: '#2f915c',
          600: '#22744a',
          700: '#1d5c3d',
          800: '#194a32',
          900: '#143c29',
          950: '#0a2116'
        },
        brand: {
          red: '#901C1E',
          'red-bright': '#AC2323',
          gold: '#FFCD05',
          navy: '#142556',
          ink: '#0C1734',
          burgundy: '#440034',
          teal: '#0C5F55',
          lime: '#E5DC1E',
          green: '#11820B',
          gray: '#54595F',
          'gray-text': '#7A7A7A'
        }
      },
      backgroundImage: {
        'gradient-brand': 'linear-gradient(135deg, #2563eb 0%, #1f7a4f 100%)',
        'gradient-surface': 'radial-gradient(1200px 600px at 15% -10%, rgba(59,130,246,0.14), transparent 60%), radial-gradient(1000px 500px at 90% 110%, rgba(47,145,92,0.12), transparent 60%)'
      },
      boxShadow: {
        'glow-blue': '0 0 24px rgba(59,130,246,0.35)',
        'glass': '0 8px 32px rgba(2,6,23,0.55), inset 0 1px 0 rgba(255,255,255,0.08)'
      },
      keyframes: {
        'slide-up': {
          '0%': { opacity: '0', transform: 'translateY(14px) scale(0.98)' },
          '100%': { opacity: '1', transform: 'translateY(0) scale(1)' }
        },
        'pulse-soft': {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.55' }
        },
        shimmer: {
          from: { backgroundPosition: '200% 0' },
          to: { backgroundPosition: '-200% 0' }
        }
      },
      animation: {
        'slide-up': 'slide-up 320ms cubic-bezier(0.22, 1, 0.36, 1)',
        'pulse-soft': 'pulse-soft 2.4s ease-in-out infinite',
        shimmer: 'shimmer 1.4s linear infinite'
      }
    }
  },
  plugins: []
}
