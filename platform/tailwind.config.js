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
          red: '#901C1E',
          'red-hover': '#7b1618',
          gold: '#FFCD05',
          navy: '#0B1E57',
          'navy-hover': '#0D2A7A',
          text: '#FFFFFF',
          muted: 'rgba(255,255,255,0.3)',
          border: 'rgba(255,255,255,0.08)',
        },
      },
      borderRadius: {
        '2xl': '16px',
        xl: '12px',
      },
      fontFamily: {
        sans: ['system-ui', '-apple-system', 'sans-serif'],
        mono: ['ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
};
