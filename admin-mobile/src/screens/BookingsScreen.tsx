import { useEffect, useState } from 'react'
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native'
import { listBookings, listPayments, type BookingView, type PaymentView } from '../lib/adminApi'

const STATUS_COLOR: Record<string, string> = {
  PENDING: '#d97706',
  CONFIRMED: '#16a34a',
  CANCELLED: '#dc2626',
  PAID: '#16a34a',
  REFUNDED: '#64748b',
  FAILED: '#dc2626'
}

export default function BookingsScreen({ onBack }: { onBack: () => void }) {
  const [bookings, setBookings] = useState<BookingView[]>([])
  const [payments, setPayments] = useState<PaymentView[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    Promise.all([listBookings(), listPayments().catch(() => [] as PaymentView[])])
      .then(([b, p]) => {
        setBookings(b)
        setPayments(p)
      })
      .catch((e) => setError((e as Error).message))
      .finally(() => setLoading(false))
  }, [])

  const paid = payments.filter((p) => p.status === 'PAID').reduce((s, p) => s + p.amount, 0)
  const pending = payments.filter((p) => p.status === 'PENDING' || p.status === 'INITIATED').reduce((s, p) => s + p.amount, 0)

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Pressable onPress={onBack}>
          <Text style={styles.back}>← Back</Text>
        </Pressable>
        <Text style={styles.title}>Bookings & payments</Text>
        <Text style={styles.headerSpacer} />
      </View>
      {loading && <ActivityIndicator color="#1a5fb4" style={{ marginTop: 30 }} />}
      {error !== '' && <Text style={styles.error}>{error}</Text>}
      <ScrollView>
        <View style={styles.cardsRow}>
          <View style={styles.statCard}>
            <Text style={styles.statValue}>{bookings.length}</Text>
            <Text style={styles.statLabel}>Bookings</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statValue}>KSh {paid.toLocaleString()}</Text>
            <Text style={styles.statLabel}>Collected</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statValue}>KSh {pending.toLocaleString()}</Text>
            <Text style={styles.statLabel}>Pending</Text>
          </View>
        </View>

        {bookings.map((b) => (
          <View key={b.id} style={styles.row}>
            <View style={{ flex: 1 }}>
              <Text style={styles.rowTitle}>{b.reference}</Text>
              <Text style={styles.rowMeta}>
                booth #{b.boothId} · qty {b.quantity} · KSh {b.total.toLocaleString()} · {b.createdAt.slice(0, 10)}
              </Text>
            </View>
            <Text style={[styles.status, { color: STATUS_COLOR[b.status] ?? '#334155' }]}>{b.status}</Text>
          </View>
        ))}
        {!loading && bookings.length === 0 && <Text style={styles.empty}>No bookings yet</Text>}

        {!loading && payments.length > 0 && (
          <View style={{ marginTop: 20 }}>
            <Text style={[styles.rowTitle, { paddingHorizontal: 12, marginBottom: 8 }]}>Payments ({payments.length})</Text>
            {payments.map((p) => (
              <View key={p.id} style={styles.row}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.rowTitle}>#{p.id} · booking #{p.bookingId} · {p.method?.toUpperCase()}</Text>
                  <Text style={styles.rowMeta}>KSh {p.amount.toLocaleString()} · {p.createdAt?.slice(0, 10)}</Text>
                </View>
                <Text style={[styles.status, { color: STATUS_COLOR[p.status] ?? '#334155' }]}>{p.status}</Text>
              </View>
            ))}
          </View>
        )}
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
  cardsRow: { flexDirection: 'row', gap: 10, padding: 12 },
  statCard: { flex: 1, backgroundColor: '#fff', borderRadius: 10, padding: 12, alignItems: 'center' },
  statValue: { fontWeight: 'bold', fontSize: 16, color: '#0f2e5c' },
  statLabel: { color: '#94a3b8', fontSize: 11, marginTop: 2 },
  row: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', marginHorizontal: 12, marginTop: 8, padding: 12, borderRadius: 8 },
  rowTitle: { fontWeight: '600', color: '#334155' },
  rowMeta: { color: '#94a3b8', fontSize: 11, marginTop: 2 },
  status: { fontWeight: 'bold', fontSize: 12 },
  empty: { textAlign: 'center', color: '#94a3b8', marginTop: 30 }
})
