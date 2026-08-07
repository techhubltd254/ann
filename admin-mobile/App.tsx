import { StatusBar } from 'expo-status-bar'
import { useEffect, useState } from 'react'
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native'
import { SafeAreaProvider, SafeAreaView } from 'react-native-safe-area-context'
import { AuthProvider, useAuth } from './src/lib/auth'
import { initDb } from './src/lib/db'
import { hasBiometrics, isBiometricUnlockEnabled, promptBiometric } from './src/lib/keychain'
import AuditScreen from './src/screens/AuditScreen'
import BookingsScreen from './src/screens/BookingsScreen'
import DashboardScreen from './src/screens/DashboardScreen'
import DataScreen from './src/screens/DataScreen'
import DelegationsScreen from './src/screens/DelegationsScreen'
import LoginScreen from './src/screens/LoginScreen'
import MediaScreen from './src/screens/MediaScreen'
import UsersScreen from './src/screens/UsersScreen'

type Route = { name: 'dashboard' } | { name: 'data'; entityType: string } | { name: 'admin'; section: string }

function LockedScreen({ onRetry }: { onRetry: () => void }) {
  return (
    <View style={styles.centered}>
      <Text style={styles.lockTitle}>🔒 KICC locked</Text>
      <Text style={styles.lockSub}>Biometric verification required to unlock offline data</Text>
      <Pressable style={styles.button} onPress={onRetry}>
        <Text style={styles.buttonText}>Unlock</Text>
      </Pressable>
    </View>
  )
}

function Main() {
  const { me, loading } = useAuth()
  const [boot, setBoot] = useState<'init' | 'ready' | 'locked'>('init')
  const [route, setRoute] = useState<Route>({ name: 'dashboard' })

  const bootDb = async () => {
    setBoot('init')
    try {
      if ((await hasBiometrics()) && (await isBiometricUnlockEnabled())) {
        const ok = await promptBiometric()
        if (!ok) {
          setBoot('locked')
          return
        }
      }
      await initDb()
      setBoot('ready')
    } catch {
      setBoot('locked')
    }
  }

  useEffect(() => {
    bootDb()
  }, [])

  if (boot === 'init' || loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#1a5fb4" />
      </View>
    )
  }
  if (boot === 'locked') return <LockedScreen onRetry={bootDb} />
  if (!me) return <LoginScreen />
  if (route.name === 'data') return <DataScreen entityType={route.entityType} onBack={() => setRoute({ name: 'dashboard' })} />
  if (route.name === 'admin') {
    const back = () => setRoute({ name: 'dashboard' })
    switch (route.section) {
      case 'users':
        return <UsersScreen onBack={back} />
      case 'delegations':
        return <DelegationsScreen onBack={back} />
      case 'audit':
        return <AuditScreen onBack={back} />
      case 'media':
        return <MediaScreen onBack={back} />
      case 'bookings':
        return <BookingsScreen onBack={back} />
      default:
        return <DashboardScreen onOpen={(t) => setRoute({ name: 'data', entityType: t })} onAdmin={(s) => setRoute({ name: 'admin', section: s })} />
    }
  }
  return (
    <DashboardScreen
      onOpen={(entityType) => setRoute({ name: 'data', entityType })}
      onAdmin={(section) => setRoute({ name: 'admin', section })}
    />
  )
}

export default function App() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <SafeAreaView style={styles.safeArea} edges={['top', 'bottom']}>
          <Main />
        </SafeAreaView>
        <StatusBar style="light" />
      </AuthProvider>
    </SafeAreaProvider>
  )
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#f1f5f9' },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#f1f5f9' },
  lockTitle: { fontSize: 24, fontWeight: 'bold', color: '#0f2e5c' },
  lockSub: { color: '#64748b', marginTop: 8, marginBottom: 24, textAlign: 'center', paddingHorizontal: 32 },
  button: { backgroundColor: '#1a5fb4', borderRadius: 8, paddingVertical: 12, paddingHorizontal: 32 },
  buttonText: { color: '#fff', fontWeight: 'bold' }
})
