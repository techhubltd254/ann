/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './src/**/*.{ts,tsx}',
    './resources/js/**/*.{ts,tsx}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        kicc: {
          bg: '#07090F',
          surface: '#0D1220',
          card: '#141B2E',
          red: '#B4432A',
          'red-hover': '#93331b',
          gold: '#E0A22B',
          navy: '#0B1E57',
          'navy-hover': '#0D2A7A',
          text: '#2E2118',
          muted: 'rgba(46,33,24,0.70)',
          border: 'rgba(46,33,24,0.11)',
        },
        canvas: {
          dark: 'var(--dark-canvas, #0A1024)',
          surface: 'var(--surface, #141B2E)',
        },
        glass: {
          bg: 'var(--glass-bg, rgba(255,253,248,0.72))',
          border: 'var(--glass-border, rgba(46,33,24,0.11))',
        },
      },
      borderRadius: {
        '2xl': '18px',
        xl: '12px',
        sm: '9px',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        heading: ['Montserrat', 'sans-serif'],
        mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
};
