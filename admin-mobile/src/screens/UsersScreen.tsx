import { useEffect, useState } from 'react'
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native'
import { createUser, deactivateUser, listUsers, type UserView } from '../lib/adminApi'
import { useAuth } from '../lib/auth'

const TIERS = ['EXHIBITOR', 'COUNTY', 'NATIONAL', 'KICC']

export default function UsersScreen({ onBack }: { onBack: () => void }) {
  const { me } = useAuth()
  const [users, setUsers] = useState<UserView[]>([])
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [form, setForm] = useState({ email: '', fullName: '', tier: 'EXHIBITOR', password: '', countySlug: '' })
  const [error, setError] = useState('')

  const load = async () => {
    setLoading(true)
    try {
      setUsers(await listUsers())
    } catch (e) {
      setError((e as Error).message)
    }
    setLoading(false)
  }

  useEffect(() => {
    load()
  }, [])

  const allowedTiers = me ? TIERS.slice(0, TIERS.indexOf(me.tier) + 1) : ['EXHIBITOR']

  const submit = async () => {
    setError('')
    setBusy(true)
    try {
      const created = await createUser({ ...form, countySlug: form.countySlug || undefined })
      Alert.alert('User created', `${created.fullName} (${created.tier})`)
      setForm({ email: '', fullName: '', tier: 'EXHIBITOR', password: '', countySlug: '' })
      await load()
    } catch (e) {
      setError((e as Error).message)
    }
    setBusy(false)
  }

  const deactivate = (u: UserView) => {
    Alert.alert('Deactivate user', `${u.fullName} (${u.email}) — they will no longer be able to sign in.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Deactivate',
        style: 'destructive',
        onPress: async () => {
          try {
            await deactivateUser(u.id)
            await load()
          } catch (e) {
            Alert.alert('Failed', (e as Error).message)
          }
        }
      }
    ])
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Pressable onPress={onBack}>
          <Text style={styles.back}>← Back</Text>
        </Pressable>
        <Text style={styles.title}>Users</Text>
        <Text style={styles.headerSpacer} />
      </View>
      <ScrollView>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Create user</Text>
          <TextInput style={styles.input} placeholder="Full name" value={form.fullName} onChangeText={(v) => setForm({ ...form, fullName: v })} />
          <TextInput style={styles.input} placeholder="Email" autoCapitalize="none" keyboardType="email-address" value={form.email} onChangeText={(v) => setForm({ ...form, email: v })} />
          <TextInput style={styles.input} placeholder="Password (min 8 chars)" secureTextEntry value={form.password} onChangeText={(v) => setForm({ ...form, password: v })} />
          <View style={styles.tierRow}>
            {allowedTiers.map((t) => (
              <Pressable key={t} style={[styles.tierChip, form.tier === t && styles.tierChipActive]} onPress={() => setForm({ ...form, tier: t })}>
                <Text style={[styles.tierChipText, form.tier === t && styles.tierChipTextActive]}>{t}</Text>
              </Pressable>
            ))}
          </View>
          {form.tier === 'COUNTY' && (
            <TextInput style={styles.input} placeholder="County slug (e.g. muranga)" autoCapitalize="none" value={form.countySlug} onChangeText={(v) => setForm({ ...form, countySlug: v })} />
          )}
          <Pressable style={styles.button} onPress={submit} disabled={busy}>
            <Text style={styles.buttonText}>{busy ? 'Creating…' : 'Create user'}</Text>
          </Pressable>
          {error !== '' && <Text style={styles.error}>{error}</Text>}
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Users ({users.length})</Text>
          {loading && <ActivityIndicator color="#1a5fb4" />}
          {users.map((u) => (
            <View key={u.id} style={styles.userRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.userName}>{u.fullName}</Text>
                <Text style={styles.userMeta}>
                  {u.email} · {u.tier}
                  {u.countySlug ? ` · ${u.countySlug}` : ''} · {u.active ? 'active' : 'deactivated'}
                </Text>
              </View>
              {u.active && u.email !== me?.email && (
                <Pressable style={styles.smallDanger} onPress={() => deactivate(u)}>
                  <Text style={styles.smallDangerText}>Deactivate</Text>
                </Pressable>
              )}
            </View>
          ))}
        </View>
      </ScrollView>
    </View>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f1f5f9' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#e2e8f0' },
  back: { color: '#1a5fb4', fontWeight: '600' },
  title: { fontWeight: 'bold', fontSize: 16, color: '#0f2e5c' },
  headerSpacer: { width: 70 },
  card: { backgroundColor: '#fff', borderRadius: 10, padding: 16, margin: 12, marginTop: 4 },
  cardTitle: { fontWeight: 'bold', color: '#0f2e5c', marginBottom: 10 },
  input: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 10, marginBottom: 8, backgroundColor: '#fff' },
  tierRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 10 },
  tierChip: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 14, paddingHorizontal: 12, paddingVertical: 5 },
  tierChipActive: { backgroundColor: '#1a5fb4', borderColor: '#1a5fb4' },
  tierChipText: { color: '#334155', fontSize: 12, fontWeight: '600' },
  tierChipTextActive: { color: '#fff' },
  button: { backgroundColor: '#1a5fb4', borderRadius: 8, padding: 12, alignItems: 'center' },
  buttonText: { color: '#fff', fontWeight: 'bold' },
  error: { color: '#dc2626', marginTop: 8, fontSize: 13 },
  userRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  userName: { fontWeight: '600', color: '#334155' },
  userMeta: { color: '#94a3b8', fontSize: 11, marginTop: 2 },
  smallDanger: { backgroundColor: '#fee2e2', borderRadius: 6, paddingHorizontal: 10, paddingVertical: 6 },
  smallDangerText: { color: '#dc2626', fontWeight: '600', fontSize: 12 }
})
