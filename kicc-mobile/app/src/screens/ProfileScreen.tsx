import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ActivityIndicator, ScrollView, KeyboardAvoidingView, Platform,
} from 'react-native';
import { useAuth } from '../lib/auth';
import { apiGet } from '../lib/api';

export default function ProfileScreen() {
  const { user, token, login, logout, isLoading } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [me, setMe] = useState<any>(null);

  const doLogin = async () => {
    setSubmitting(true);
    setError('');
    try {
      await login(email, password);
      setPassword('');
    } catch (e: any) {
      setError(e.message);
    } finally {
      setSubmitting(false);
    }
  };

  React.useEffect(() => {
    if (token) {
      apiGet('/auth/me', token).then(setMe).catch(() => {});
    } else {
      setMe(null);
    }
  }, [token]);

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#046BD2" /></View>;
  }

  if (!token) {
    return (
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.loginWrap}>
          <Text style={styles.logo}>KICC</Text>
          <Text style={styles.loginTitle}>Sign in to your account</Text>
          <Text style={styles.loginSub}>Track orders, manage your escrow, and verify as a buyer or seller.</Text>
          <TextInput style={styles.input} placeholder="Email" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" />
          <TextInput style={styles.input} placeholder="Password" value={password} onChangeText={setPassword} secureTextEntry />
          {error ? <Text style={styles.error}>{error}</Text> : null}
          <TouchableOpacity style={styles.loginBtn} onPress={doLogin} disabled={submitting}>
            {submitting ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginBtnText}>Sign In</Text>}
          </TouchableOpacity>
        </ScrollView>
      </KeyboardAvoidingView>
    );
  }

  const u = me ?? user ?? {};
  return (
    <ScrollView contentContainerStyle={styles.profileWrap}>
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>{(u.name ?? 'U').toString().charAt(0).toUpperCase()}</Text>
      </View>
      <Text style={styles.name}>{u.name ?? 'KICC User'}</Text>
      <Text style={styles.email}>{u.email}</Text>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Account</Text>
        <InfoRow label="Verification" value={u.verification_status ?? 'not started'} />
        <InfoRow label="Verification tier" value={String(u.verification_tier ?? '—')} />
        <InfoRow label="Trust score" value={u.trust_score != null ? String(u.trust_score) : '—'} />
        <InfoRow label="Trust grade" value={u.trust_grade ?? '—'} />
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Shopping</Text>
        <InfoRow label="Orders" value="View orders (coming soon)" />
        <InfoRow label="Escrow protection" value="Active on all purchases" />
      </View>

      <TouchableOpacity style={styles.logoutBtn} onPress={logout}>
        <Text style={styles.logoutText}>Sign Out</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue} numberOfLines={2}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#fff' },
  loginWrap: { flexGrow: 1, justifyContent: 'center', padding: 24, backgroundColor: '#fff' },
  logo: { fontSize: 40, fontWeight: '900', color: '#046BD2', textAlign: 'center', marginBottom: 8 },
  loginTitle: { fontSize: 20, fontWeight: '800', color: '#0b1f33', textAlign: 'center' },
  loginSub: { fontSize: 13, color: '#5a6b80', textAlign: 'center', marginTop: 6, marginBottom: 24, lineHeight: 18 },
  input: { backgroundColor: '#f0f3f7', borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, fontSize: 15, marginBottom: 12 },
  error: { color: '#e0503a', fontSize: 13, marginBottom: 8 },
  loginBtn: { backgroundColor: '#046BD2', borderRadius: 12, paddingVertical: 15, alignItems: 'center' },
  loginBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  profileWrap: { padding: 20, backgroundColor: '#f5f7fa', alignItems: 'center', flexGrow: 1 },
  avatar: { width: 84, height: 84, borderRadius: 42, backgroundColor: '#046BD2', alignItems: 'center', justifyContent: 'center', marginTop: 12 },
  avatarText: { fontSize: 34, fontWeight: '800', color: '#fff' },
  name: { fontSize: 20, fontWeight: '800', color: '#0b1f33', marginTop: 10 },
  email: { fontSize: 13, color: '#5a6b80', marginTop: 2 },
  card: { width: '100%', backgroundColor: '#fff', borderRadius: 14, padding: 16, marginTop: 16 },
  cardTitle: { fontSize: 13, fontWeight: '700', color: '#046BD2', textTransform: 'uppercase', marginBottom: 8 },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 7, borderBottomWidth: 1, borderBottomColor: '#f0f3f7' },
  infoLabel: { fontSize: 14, color: '#3a4a5f' },
  infoValue: { fontSize: 14, fontWeight: '700', color: '#0b1f33', maxWidth: '55%', textAlign: 'right' },
  logoutBtn: { marginTop: 24, paddingVertical: 13, paddingHorizontal: 40, borderRadius: 12, backgroundColor: '#fff', borderWidth: 1, borderColor: '#e0503a' },
  logoutText: { color: '#e0503a', fontSize: 15, fontWeight: '700' },
});