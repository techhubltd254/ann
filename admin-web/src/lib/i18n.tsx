import { useState, useCallback, useEffect, createContext, useContext, type ReactNode } from 'react'

type Locale = 'en' | 'sw'

const translations: Record<Locale, Record<string, string>> = {
  en: {
    'admin': 'Admin',
    'dashboard': 'Dashboard',
    'users': 'Users',
    'onboarding': 'Onboarding',
    'complaints': 'Complaints',
    'audit_log': 'Audit log',
    'delegations': 'Delegations',
    'settlements': 'Settlements',
    'payments': 'Payments',
    'bookings': 'Bookings',
    'counties': 'Counties',
    'sectors': 'Sectors',
    'analytics': 'Analytics',
    'orders': 'Orders',
    'media_library': 'Media library',
    'settings': 'Settings',
    'sign_out': 'Sign out',
    'public_site': 'Public site',
    'loading': 'Loading…',
    'save': 'Save',
    'cancel': 'Cancel',
    'create': 'Create',
    'edit': 'Edit',
    'delete': 'Delete',
    'confirm': 'Confirm',
    'search': 'Search',
    'filter': 'Filter',
    'all': 'All',
    'active': 'Active',
    'inactive': 'Inactive',
    'status': 'Status',
    'actions': 'Actions',
    'name': 'Name',
    'email': 'Email',
    'tier': 'Tier',
    'county': 'County',
    'priority': 'Priority',
    'category': 'Category',
    'subject': 'Subject',
    'reference': 'Reference',
    'notes': 'Notes',
    'detail': 'Detail',
    'overdue': 'Overdue',
    'total': 'Total',
    'amount': 'Amount',
    'revenue': 'Revenue',
    'date': 'Date',
    'from': 'From',
    'to': 'To',
    'view': 'View',
    'export': 'Export',
    'back': 'Back',
    'home': 'Home'
  },
  sw: {
    'admin': 'Msimamizi',
    'dashboard': 'Dashibodi',
    'users': 'Watumiaji',
    'onboarding': 'Usajili',
    'complaints': 'Malalamiko',
    'audit_log': 'Rekodi za ukaguzi',
    'delegations': 'Wawakilishi',
    'settlements': 'Malipo ya kaunti',
    'payments': 'Malipo',
    'bookings': 'Uhifadhi',
    'counties': 'Kaunti',
    'sectors': 'Sekta',
    'analytics': 'Takwimu',
    'orders': 'Maagizo',
    'media_library': 'Maktaba ya media',
    'settings': 'Mipangilio',
    'sign_out': 'Ondoka',
    'public_site': 'Tovuti ya umma',
    'loading': 'Inapakia…',
    'save': 'Hifadhi',
    'cancel': 'Ghairi',
    'create': 'Tengeneza',
    'edit': 'Hariri',
    'delete': 'Futa',
    'confirm': 'Thibitisha',
    'search': 'Tafuta',
    'filter': 'Chuja',
    'all': 'Zote',
    'active': 'Inatumika',
    'inactive': 'Haijatumika',
    'status': 'Hali',
    'actions': 'Vitendo',
    'name': 'Jina',
    'email': 'Barua pepe',
    'tier': 'Kiwango',
    'county': 'Kaunti',
    'priority': 'Kipaumbele',
    'category': 'Aina',
    'subject': 'Somo',
    'reference': 'Rejea',
    'notes': 'Maelezo',
    'detail': 'Maelezo ya kina',
    'overdue': 'Imechelewa',
    'total': 'Jumla',
    'amount': 'Kiasi',
    'revenue': 'Mapato',
    'date': 'Tarehe',
    'from': 'Kutoka',
    'to': 'Hadi',
    'view': 'Tazama',
    'export': 'Hamisha',
    'back': 'Rudi',
    'home': 'Nyumbani'
  }
}

interface LocaleContextType {
  locale: Locale
  setLocale: (l: Locale) => void
  t: (key: string) => string
}

const LocaleContext = createContext<LocaleContextType>({ locale: 'en', setLocale: () => {}, t: (k) => k })

const STORAGE_KEY = 'kicc-locale'

function initialLocale(): Locale {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved === 'en' || saved === 'sw') return saved
    const nav = navigator.language?.toLowerCase() ?? ''
    return nav.startsWith('sw') ? 'sw' : 'en'
  } catch {
    return 'en'
  }
}

export function LocaleProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>(initialLocale)
  const t = useCallback((key: string) => translations[locale]?.[key] ?? key, [locale])

  const setLocale = useCallback((l: Locale) => {
    setLocaleState(l)
    try { localStorage.setItem(STORAGE_KEY, l) } catch { /* storage unavailable */ }
  }, [])

  useEffect(() => {
    document.documentElement.lang = locale
  }, [locale])

  return <LocaleContext.Provider value={{ locale, setLocale, t }}>{children}</LocaleContext.Provider>
}

export function useT() {
  const ctx = useContext(LocaleContext)
  return ctx
}
