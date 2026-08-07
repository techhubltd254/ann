import { useCallback, useEffect, useState } from 'react'
import { ActivityIndicator, Alert, Modal, Pressable, ScrollView, StyleSheet, Switch, Text, TextInput, View } from 'react-native'
import { useAuth } from '../lib/auth'
import { countsPerType, lastSyncedAt, pendingRows, rotateEncryptionKey } from '../lib/db'
import { hasBiometrics, isBiometricUnlockEnabled, setBiometricUnlock } from '../lib/keychain'
import { getServerUrl, hasCustomServerUrl, setServerUrl } from '../lib/settings'
import { sync } from '../lib/sync'

export const RESOURCES = [
  'attractions',
  'hotels',
  'farms',
  'health-facilities',
  'institutions',
  'transport',
  'culture-sites',
  'products',
  'sector-entities'
]

export default function DashboardScreen({ onOpen, onAdmin }: { onOpen: (entityType: string) => void; onAdmin: (section: string) => void }) {
  const { me, logout } = useAuth()
  const [counts, setCounts] = useState<Record<string, number>>({})
  const [pending, setPending] = useState(0)
  const [lastSync, setLastSync] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const [status, setStatus] = useState('')
  const [biometrics, setBiometrics] = useState(false)
  const [bioAvailable, setBioAvailable] = useState(false)
  const [serverUrl, setServerUrlState] = useState('')
  const [serverModal, setServerModal] = useState(false)
  const [serverDraft, setServerDraft] = useState('')

  const privs = new Set(me?.privileges ?? [])
  const adminSections: [string, string][] = []
  if (privs.has('USERS_MANAGE')) adminSections.push(['users', 'Users'])
  if (privs.has('DELEGATE')) adminSections.push(['delegations', 'Delegations'])
  if (privs.has('DELEGATE')) adminSections.push(['audit', 'Audit log'])
  if (privs.has('CONTENT_MANAGE')) adminSections.push(['media', 'Media library'])
  if (privs.has('BOOKINGS_MANAGE')) adminSections.push(['bookings', 'Bookings & payments'])

  const refresh = useCallback(() => {
    setCounts(countsPerType())
    setPending(pendingRows().length)
    setLastSync(lastSyncedAt())
  }, [])

  useEffect(() => {
    refresh()
    ;(async () => {
      setBioAvailable(await hasBiometrics())
      setBiometrics(await isBiometricUnlockEnabled())
      setServerUrlState(await getServerUrl())
    })()
    const iv = setInterval(refresh, 5000)
    return () => clearInterval(iv)
  }, [refresh])

  const saveServerUrl = async () => {
    try {
      await setServerUrl(serverDraft)
      setServerModal(false)
      setServerUrlState(await getServerUrl())
      setStatus('Server changed — signing out to reconnect')
      setTimeout(() => {
        logout()
      }, 800)
    } catch (e) {
      setStatus(`✗ ${(e as Error).message}`)
    }
  }

  const resetServerUrl = async () => {
    await setServerUrl('')
    setServerModal(false)
    setServerUrlState(await getServerUrl())
    setStatus('Server reset to default — signing out to reconnect')
    setTimeout(() => {
      logout()
    }, 800)
  }

  const toggleBio = async (value: boolean) => {
    if (value && !(await hasBiometrics())) {
      Alert.alert('Biometrics unavailable', 'No fingerprint/face enrolled on this device')
      return
    }
    await setBiometricUnlock(value)
    setBiometrics(value)
    setStatus(value ? '✓ Biometric unlock enabled — app restart will require it' : 'Biometric unlock disabled')
  }

  const rotate = async () => {
    setBusy(true)
    try {
      await rotateEncryptionKey()
      setStatus('✓ SQLCipher key rotated — DB re-encrypted with new key')
    } catch (e) {
      setStatus(`✗ Rotation failed: ${(e as Error).message}`)
    } finally {
      setBusy(false)
    }
  }

  const run = async (fn: () => Promise<{ ok: boolean; message: string }>, label: string) => {
    setBusy(true)
    setStatus(`${label}…`)
    try {
      const r = await fn()
      setStatus(r.ok ? `✓ ${r.message}` : `✗ ${r.message}`)
    } catch (e) {
      setStatus(`✗ ${(e as Error).message}`)
    } finally {
      setBusy(false)
      refresh()
    }
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 48 }}>
      <Text style={styles.title}>KICC Mobile</Text>
      <Text style={styles.meta}>
        {me?.fullName} · {me?.tier}
        {me?.countySlug ? ` · ${me.countySlug}` : ''}
      </Text>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Offline data (SQLCipher)</Text>
        {RESOURCES.map((r) => (
          <Pressable key={r} style={styles.resource} onPress={() => onOpen(r)}>
            <Text style={styles.resourceName}>{r}</Text>
            <Text style={styles.resourceCount}>{counts[r] ?? 0}</Text>
          </Pressable>
        ))}
        <Text style={styles.footnote}>Last sync: {lastSync ? new Date(lastSync).toLocaleString() : 'never'}</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Sync</Text>
        <View style={styles.row}>
          <Pressable style={styles.button} onPress={() => run(() => sync.pull(me?.countySlug ?? ''), 'Pulling')} disabled={busy}>
            {busy ? <ActivityIndicator color="#fff" size="small" /> : <Text style={styles.buttonText}>Pull county data</Text>}
          </Pressable>
          <Pressable style={styles.button} onPress={() => run(() => sync.push(me?.countySlug ?? ''), 'Pushing')} disabled={busy}>
            <Text style={styles.buttonText}>Push ({pending})</Text>
          </Pressable>
        </View>
        {status !== '' && <Text style={styles.status}>{status}</Text>}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Security</Text>
        <View style={styles.row}>
          <Pressable style={styles.button} onPress={rotate} disabled={busy}>
            <Text style={styles.buttonText}>Rotate DB key</Text>
          </Pressable>
          {bioAvailable && (
            <View style={[styles.row, { flex: 1 }]}>
              <Text style={{ color: '#334155', fontSize: 13 }}>Biometric unlock</Text>
              <Switch value={biometrics} onValueChange={toggleBio} />
            </View>
          )}
        </View>
        <Pressable style={styles.resource} onPress={() => { setServerDraft(serverUrl); setServerModal(true) }}>
          <Text style={styles.resourceName}>Server: {serverUrl}</Text>
          <Text style={styles.resourceCount}>›</Text>
        </Pressable>
      </View>

      <Modal visible={serverModal} transparent animationType="fade" onRequestClose={() => setServerModal(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.cardTitle}>Server URL</Text>
            <Text style={styles.footnote}>Point this device at your local KICC server (e.g. http://192.168.1.20:8091). Default is used when empty.</Text>
            <TextInput
              style={styles.input}
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="url"
              value={serverDraft}
              onChangeText={setServerDraft}
              placeholder="http://192.168.1.20:8091"
            />
            <View style={styles.row}>
              <Pressable style={styles.button} onPress={resetServerUrl}>
                <Text style={styles.buttonText}>Reset</Text>
              </Pressable>
              <Pressable style={styles.button} onPress={saveServerUrl}>
                <Text style={styles.buttonText}>Save</Text>
              </Pressable>
            </View>
            <Pressable style={styles.resource} onPress={() => setServerModal(false)}>
              <Text style={styles.logoutText}>Cancel</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      {adminSections.length > 0 && (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Admin</Text>
          {adminSections.map(([key, label]) => (
            <Pressable key={key} style={styles.resource} onPress={() => onAdmin(key)}>
              <Text style={styles.resourceName}>{label}</Text>
              <Text style={styles.resourceCount}>›</Text>
            </Pressable>
          ))}
        </View>
      )}

      <Pressable style={styles.logout} onPress={logout}>
        <Text style={styles.logoutText}>Sign out</Text>
      </Pressable>
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f1f5f9' },
  title: { fontSize: 26, fontWeight: 'bold', color: '#0f2e5c' },
  meta: { color: '#475569', marginBottom: 16 },
  card: { backgroundColor: '#fff', borderRadius: 10, padding: 16, marginBottom: 12 },
  cardTitle: { fontWeight: 'bold', fontSize: 15, marginBottom: 8, color: '#0f2e5c' },
  resource: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  resourceName: { color: '#334155', fontSize: 14 },
  resourceCount: { color: '#1a5fb4', fontWeight: 'bold' },
  footnote: { marginTop: 8, fontSize: 11, color: '#94a3b8' },
  row: { flexDirection: 'row', gap: 8 },
  button: { backgroundColor: '#1a5fb4', borderRadius: 8, paddingVertical: 12, paddingHorizontal: 16, flex: 1, alignItems: 'center' },
  buttonText: { color: '#fff', fontWeight: 'bold', fontSize: 14 },
  status: { marginTop: 10, color: '#0f766e', fontSize: 13 },
  logout: { marginTop: 8, alignSelf: 'center' },
  logoutText: { color: '#dc2626', fontWeight: '600' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'center', padding: 24 },
  modalCard: { backgroundColor: '#fff', borderRadius: 12, padding: 20 },
  input: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 10, marginVertical: 10, backgroundColor: '#fff' }
})
