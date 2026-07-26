/* KICC Design Tokens — Tailwind Config */
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            colors: {
                kicc: {
                    bg:     '#07090F',
                    card:   '#0D1220',
                    surface:'#141B2E',
                    red:    '#901C1E',
                    'red-dark': '#7b1618',
                    'red-light': '#e86f71',
                    gold:   '#FFCD05',
                    'gold-dark': '#e6b904',
                    navy:   '#0B1E57',
                    cream:  '#F9FAFB',
                    muted:  '#5A6480',
                },
                semantic: {
                    success: '#34D399',
                    warning: '#FBBF24',
                    error:   '#e86f71',
                    info:    '#60A5FA',
                }
            },
            boxShadow: {
                'card': '0 4px 24px rgba(0,0,0,0.25)',
            }
        }
    }
};