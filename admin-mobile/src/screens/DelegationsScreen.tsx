import { useEffect, useState } from 'react'
import { ActivityIndicator, Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native'
import { grantDelegation, listDelegations, listUsers, revokeDelegation, type DelegationView, type UserView } from '../lib/adminApi'

const ROLES = ['EXHIBITOR', 'COUNTY', 'NATIONAL']
const SCOPE_TYPES = ['COUNTY', 'SECTOR', 'BOOTH', 'GLOBAL']

export default function DelegationsScreen({ onBack }: { onBack: () => void }) {
  const [delegations, setDelegations] = useState<DelegationView[]>([])
  const [users, setUsers] = useState<UserView[]>([])
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [activeOnly, setActiveOnly] = useState(true)
  const [error, setError] = useState('')
  const [form, setForm] = useState({ userId: '', role: 'COUNTY', scopeType: 'COUNTY', scopeValue: '', expiresAt: '' })

  const load = async () => {
    setLoading(true)
    try {
      const [d, u] = await Promise.all([listDelegations(activeOnly), listUsers().catch(() => [] as UserView[])])
      setDelegations(d)
      setUsers(u)
    } catch (e) {
      setError((e as Error).message)
    }
    setLoading(false)
  }

  useEffect(() => {
    load()
  }, [activeOnly])

  const submit = async () => {
    setError('')
    const userId = Number(form.userId)
    if (!userId) {
      setError('Select a user')
      return
    }
    setBusy(true)
    try {
      await grantDelegation({
        subjectUserId: userId,
        role: form.role,
        scopeType: form.scopeType,
        scopeValue: form.scopeValue || undefined,
        expiresAt: form.expiresAt ? new Date(form.expiresAt).toISOString() : undefined
      })
      Alert.alert('Delegation granted')
      setForm({ ...form, userId: '', scopeValue: '', expiresAt: '' })
      await load()
    } catch (e) {
      setError((e as Error).message)
    }
    setBusy(false)
  }

  const revoke = (d: DelegationView) => {
    Alert.alert('Revoke delegation', `${d.role} for ${d.subjectEmail} — effective immediately.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Revoke',
        style: 'destructive',
        onPress: async () => {
          try {
            await revokeDelegation(d.id)
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
        <Text style={styles.title}>Delegations</Text>
        <Text style={styles.headerSpacer} />
      </View>
      <ScrollView>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Grant a delegation</Text>
          <Text style={styles.label}>User</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <View style={styles.userRow}>
              {users.filter((u) => u.active).map((u) => (
                <Pressable key={u.id} style={[styles.userChip, form.userId === String(u.id) && styles.userChipActive]} onPress={() => setForm({ ...form, userId: String(u.id) })}>
                  <Text style={[styles.userChipText, form.userId === String(u.id) && styles.userChipTextActive]}>{u.fullName}</Text>
                </Pressable>
              ))}
            </View>
          </ScrollView>
          <Text style={styles.label}>Role</Text>
          <View style={styles.chipRow}>
            {ROLES.map((r) => (
              <Pressable key={r} style={[styles.chip, form.role === r && styles.chipActive]} onPress={() => setForm({ ...form, role: r })}>
                <Text style={[styles.chipText, form.role === r && styles.chipTextActive]}>{r}</Text>
              </Pressable>
            ))}
          </View>
          <Text style={styles.label}>Scope</Text>
          <View style={styles.chipRow}>
            {SCOPE_TYPES.map((s) => (
              <Pressable key={s} style={[styles.chip, form.scopeType === s && styles.chipActive]} onPress={() => setForm({ ...form, scopeType: s })}>
                <Text style={[styles.chipText, form.scopeType === s && styles.chipTextActive]}>{s}</Text>
              </Pressable>
            ))}
          </View>
          {form.scopeType !== 'GLOBAL' && (
            <TextInput style={styles.input} placeholder={form.scopeType === 'COUNTY' ? 'County slug (e.g. kilifi)' : 'Scope value'} value={form.scopeValue} onChangeText={(v) => setForm({ ...form, scopeValue: v })} />
          )}
          <TextInput style={styles.input} placeholder="Expiry (optional, e.g. 2026-12-31)" value={form.expiresAt} onChangeText={(v) => setForm({ ...form, expiresAt: v })} />
          <Pressable style={styles.button} onPress={submit} disabled={busy}>
            <Text style={styles.buttonText}>{busy ? 'Granting…' : 'Grant delegation'}</Text>
          </Pressable>
          {error !== '' && <Text style={styles.error}>{error}</Text>}
        </View>

        <View style={styles.card}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={styles.cardTitle}>Delegations ({delegations.length})</Text>
            <Pressable onPress={() => setActiveOnly(!activeOnly)}>
              <Text style={styles.link}>{activeOnly ? 'Show all' : 'Active only'}</Text>
            </Pressable>
          </View>
          {loading && <ActivityIndicator color="#1a5fb4" />}
          {delegations.map((d) => (
            <View key={d.id} style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={styles.rowTitle}>{d.subjectEmail} → {d.role}</Text>
                <Text style={styles.rowMeta}>
                  {d.scopeType}
                  {d.scopeValue ? `:${d.scopeValue}` : ''} · {d.revokedAt ? `revoked ${d.revokedAt.slice(0, 10)}` : `granted ${d.grantedAt.slice(0, 10)}`}
                  {d.expiresAt ? ` · expires ${d.expiresAt.slice(0, 10)}` : ''}
                </Text>
              </View>
              {!d.revokedAt && (
                <Pressable style={styles.smallDanger} onPress={() => revoke(d)}>
                  <Text style={styles.smallDangerText}>Revoke</Text>
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
  label: { color: '#64748b', fontSize: 12, fontWeight: '600', marginTop: 6, marginBottom: 4 },
  input: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 10, marginBottom: 8, backgroundColor: '#fff' },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 8 },
  chip: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 14, paddingHorizontal: 12, paddingVertical: 5 },
  chipActive: { backgroundColor: '#1a5fb4', borderColor: '#1a5fb4' },
  chipText: { color: '#334155', fontSize: 12, fontWeight: '600' },
  chipTextActive: { color: '#fff' },
  userRow: { flexDirection: 'row', gap: 6, marginBottom: 8 },
  userChip: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 14, paddingHorizontal: 12, paddingVertical: 5 },
  userChipActive: { backgroundColor: '#0f766e', borderColor: '#0f766e' },
  userChipText: { color: '#334155', fontSize: 12, fontWeight: '600' },
  userChipTextActive: { color: '#fff' },
  button: { backgroundColor: '#1a5fb4', borderRadius: 8, padding: 12, alignItems: 'center' },
  buttonText: { color: '#fff', fontWeight: 'bold' },
  error: { color: '#dc2626', marginTop: 8, fontSize: 13 },
  link: { color: '#1a5fb4', fontWeight: '600', fontSize: 13 },
  row: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  rowTitle: { fontWeight: '600', color: '#334155' },
  rowMeta: { color: '#94a3b8', fontSize: 11, marginTop: 2 },
  smallDanger: { backgroundColor: '#fee2e2', borderRadius: 6, paddingHorizontal: 10, paddingVertical: 6 },
  smallDangerText: { color: '#dc2626', fontWeight: '600', fontSize: 12 }
})
