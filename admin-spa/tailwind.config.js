/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        'deep': '#0B0D11',
        'slate-dark': '#11141A',
        'surface': '#161A22',
        'surface-hover': '#1C212D',
        'surface-light': '#222832',
        'border': 'rgba(255,255,255,0.07)',
        'border-glow': 'rgba(99,102,241,0.2)',
        'violet': { DEFAULT: '#6366F1', 400: '#818CF8', 500: '#6366F1', 600: '#4F46E5' },
        'indigo': { DEFAULT: '#7C3AED', 400: '#8B5CF6', 500: '#7C3AED', 600: '#6D28D9' },
        'emerald': { DEFAULT: '#10B981', 400: '#34D399', 500: '#10B981', 600: '#059669' },
        'cyan': { DEFAULT: '#06B6D4', 400: '#22D3EE', 500: '#06B6D4', 600: '#0891B2' },
        'amber': { DEFAULT: '#F59E0B', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706' },
        'rose': { DEFAULT: '#F43F5E', 400: '#FB7185', 500: '#F43F5E', 600: '#E11D48' },
        'text-primary': '#F1F5F9',
        'text-secondary': '#94A3B8',
        'text-muted': '#64748B',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-out',
        'slide-up': 'slideUp 0.4s ease-out',
        'glow': 'glow 2s ease-in-out infinite alternate',
      },
      keyframes: {
        fadeIn: { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
        slideUp: { '0%': { transform: 'translateY(10px)', opacity: 0 }, '100%': { transform: 'translateY(0)', opacity: 1 } },
        glow: { '0%': { boxShadow: '0 0 20px rgba(99,102,241,0.1)' }, '100%': { boxShadow: '0 0 40px rgba(99,102,241,0.25)' } },
      },
    },
  },
  plugins: [],
};