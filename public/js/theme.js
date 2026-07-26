/* KICC Design Tokens — Tailwind Config */
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            colors: {
                kicc: { bg: '#07090F', card: '#0D1220', surface: '#141B2E', footer: '#050709', red: '#901C1E', gold: '#FFCD05', navy: '#0B1E57', cream: '#F9FAFB' }
            }
        }
    }
}
