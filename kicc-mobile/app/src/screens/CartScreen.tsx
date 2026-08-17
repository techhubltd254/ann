import React, { useState } from 'react';
import {
  View, Text, FlatList, TouchableOpacity, StyleSheet, Alert, ActivityIndicator,
} from 'react-native';
import { useCart } from '../lib/cart';
import { useAuth } from '../lib/auth';
import { apiPost } from '../lib/api';

export default function CartScreen() {
  const { items, setQty, remove, clear, total, count } = useCart();
  const { token } = useAuth();
  const [checkingOut, setCheckingOut] = useState(false);

  const checkout = async () => {
    if (!token) {
      Alert.alert('Sign in required', 'Please sign in to check out.');
      return;
    }
    if (items.length === 0) return;
    setCheckingOut(true);
    try {
      const first = items[0];
      await apiPost('/escrow', {
        seller_id: first.sellerId ?? 60001,
        amount: total,
        reference_type: 'cart',
        reference_id: first.id,
      }, token);
      clear();
      Alert.alert('Order placed', 'Your order is now in escrow. Payment is held safely until delivery.');
    } catch (e: any) {
      Alert.alert('Checkout failed', e.message);
    } finally {
      setCheckingOut(false);
    }
  };

  if (count === 0) {
    return (
      <View style={styles.emptyWrap}>
        <Text style={styles.emptyEmoji}>🛒</Text>
        <Text style={styles.emptyTitle}>Your cart is empty</Text>
        <Text style={styles.emptySub}>Browse counties and add products to start shopping.</Text>
      </View>
    );
  }

  return (
    <FlatList
      data={items}
      keyExtractor={(i) => String(i.id)}
      contentContainerStyle={styles.list}
      renderItem={({ item }) => (
        <View style={styles.row}>
          <View style={styles.rowBody}>
            <Text style={styles.rowName}>{item.name}</Text>
            <Text style={styles.rowPrice}>KES {Number(item.price).toLocaleString()}{item.unit ? ` / ${item.unit}` : ''}</Text>
            <View style={styles.qtyRow}>
              <TouchableOpacity style={styles.qtyBtn} onPress={() => setQty(item.id, item.qty - 1)}><Text style={styles.qtyBtnText}>−</Text></TouchableOpacity>
              <Text style={styles.qty}>{item.qty}</Text>
              <TouchableOpacity style={styles.qtyBtn} onPress={() => setQty(item.id, item.qty + 1)}><Text style={styles.qtyBtnText}>+</Text></TouchableOpacity>
            </View>
          </View>
          <TouchableOpacity onPress={() => remove(item.id)}><Text style={styles.remove}>✕</Text></TouchableOpacity>
        </View>
      )}
      ListFooterComponent={() => (
        <View style={styles.footer}>
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Total ({count} items)</Text>
            <Text style={styles.totalValue}>KES {total.toLocaleString()}</Text>
          </View>
          <TouchableOpacity style={[styles.checkoutBtn, checkingOut && styles.disabled]} onPress={checkout} disabled={checkingOut}>
            {checkingOut ? <ActivityIndicator color="#fff" /> : <Text style={styles.checkoutText}>Checkout Securely</Text>}
          </TouchableOpacity>
          <Text style={styles.escrowNote}>Payments held in escrow until delivery is confirmed. Buyer & seller protection included.</Text>
        </View>
      )}
    />
  );
}

const styles = StyleSheet.create({
  emptyWrap: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#fff', padding: 32 },
  emptyEmoji: { fontSize: 44 },
  emptyTitle: { fontSize: 18, fontWeight: '700', color: '#0b1f33', marginTop: 12 },
  emptySub: { fontSize: 13, color: '#5a6b80', textAlign: 'center', marginTop: 6, lineHeight: 18 },
  list: { padding: 16, backgroundColor: '#f5f7fa', paddingBottom: 40 },
  row: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 14, marginBottom: 10 },
  rowBody: { flex: 1 },
  rowName: { fontSize: 15, fontWeight: '700', color: '#0b1f33' },
  rowPrice: { fontSize: 13, color: '#0f9d58', marginTop: 2 },
  qtyRow: { flexDirection: 'row', alignItems: 'center', marginTop: 8, gap: 10 },
  qtyBtn: { width: 30, height: 30, borderRadius: 8, backgroundColor: '#e7f1fb', alignItems: 'center', justifyContent: 'center' },
  qtyBtnText: { fontSize: 18, color: '#046BD2', fontWeight: '700' },
  qty: { fontSize: 16, fontWeight: '700', color: '#0b1f33', minWidth: 24, textAlign: 'center' },
  remove: { fontSize: 16, color: '#e0503a', padding: 8 },
  footer: { marginTop: 12 },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  totalLabel: { fontSize: 15, color: '#3a4a5f' },
  totalValue: { fontSize: 20, fontWeight: '800', color: '#0b1f33' },
  checkoutBtn: { backgroundColor: '#0f9d58', borderRadius: 12, paddingVertical: 15, alignItems: 'center' },
  disabled: { opacity: 0.6 },
  checkoutText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  escrowNote: { fontSize: 11, color: '#5a6b80', marginTop: 10, textAlign: 'center', lineHeight: 16 },
});