import { useEffect, useState } from 'react'
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native'
import { listAuditLogs, type AuditLogView } from '../lib/adminApi'

export default function AuditScreen({ onBack }: { onBack: () => void }) {
  const [logs, setLogs] = useState<AuditLogView[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    listAuditLogs()
      .then(setLogs)
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false))
  }, [])

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Pressable onPress={onBack}>
          <Text style={styles.back}>← Back</Text>
        </Pressable>
        <Text style={styles.title}>Audit log</Text>
        <Text style={styles.headerSpacer} />
      </View>
      {loading && <ActivityIndicator color="#1a5fb4" style={{ marginTop: 30 }} />}
      {error !== '' && <Text style={styles.error}>{error}</Text>}
      <ScrollView>
        {logs.map((l) => (
          <View key={l.id} style={styles.row}>
            <View style={styles.actionBadge}>
              <Text style={styles.actionText}>{l.action}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.rowTitle}>{l.detail ?? l.action}</Text>
              <Text style={styles.rowMeta}>
                {l.actorEmail ?? `user#${l.actorUserId}`}
                {l.targetUserId ? ` → user#${l.targetUserId}` : ''} · {l.createdAt.replace('T', ' ').slice(0, 16)}
              </Text>
            </View>
          </View>
        ))}
        {!loading && logs.length === 0 && <Text style={styles.empty}>No audit entries</Text>}
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
  error: { color: '#dc2626', textAlign: 'center', marginTop: 20 },
  row: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', marginHorizontal: 12, marginTop: 8, padding: 12, borderRadius: 8, gap: 10 },
  actionBadge: { backgroundColor: '#e0e7ff', borderRadius: 6, paddingHorizontal: 8, paddingVertical: 4 },
  actionText: { color: '#3730a3', fontWeight: 'bold', fontSize: 11 },
  rowTitle: { fontWeight: '600', color: '#334155', fontSize: 13 },
  rowMeta: { color: '#94a3b8', fontSize: 11, marginTop: 2 },
  empty: { textAlign: 'center', color: '#94a3b8', marginTop: 40 }
})
