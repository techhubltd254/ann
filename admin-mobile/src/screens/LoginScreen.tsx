import { useEffect, useState } from 'react'
import { ActivityIndicator, Alert, Modal, Pressable, StyleSheet, Text, TextInput, View } from 'react-native'
import { useAuth } from '../lib/auth'
import { getServerUrl, setServerUrl } from '../lib/settings'

export default function LoginScreen() {
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [busy, setBusy] = useState(false)
  const [serverUrl, setServerUrlState] = useState('')
  const [serverModal, setServerModal] = useState(false)
  const [serverDraft, setServerDraft] = useState('')

  useEffect(() => {
    ;(async () => setServerUrlState(await getServerUrl()))()
  }, [])

  const submit = async () => {
    setBusy(true)
    try {
      await login(email, password)
    } catch (e) {
      Alert.alert('Login failed', (e as Error).message)
    } finally {
      setBusy(false)
    }
  }

  const saveServerUrl = async () => {
    await setServerUrl(serverDraft)
    setServerModal(false)
    setServerUrlState(await getServerUrl())
  }

  return (
    <View style={styles.container}>
      <Text style={styles.brand}>KICC</Text>
      <Text style={styles.sub}>Digital Economy Platform — Mobile</Text>
      <TextInput style={styles.input} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" placeholder="Email" placeholderTextColor="#94a3b8" />
      <TextInput style={styles.input} value={password} onChangeText={setPassword} secureTextEntry placeholder="Password" placeholderTextColor="#94a3b8" />
      <Pressable style={styles.button} onPress={submit} disabled={busy}>
        {busy ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Sign in</Text>}
      </Pressable>
      <Pressable style={styles.serverLink} onPress={() => { setServerDraft(serverUrl); setServerModal(true) }}>
        <Text style={styles.serverLinkText}>Server: {serverUrl}</Text>
      </Pressable>

      <Modal visible={serverModal} transparent animationType="fade" onRequestClose={() => setServerModal(false)}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Server URL</Text>
            <Text style={styles.modalHint}>Point this device at your local KICC server (e.g. http://192.168.1.20:8091).</Text>
            <TextInput
              style={styles.modalInput}
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="url"
              value={serverDraft}
              onChangeText={setServerDraft}
              placeholder="http://192.168.1.20:8091"
            />
            <View style={styles.modalRow}>
              <Pressable style={styles.modalButton} onPress={() => setServerModal(false)}>
                <Text style={styles.modalButtonText}>Cancel</Text>
              </Pressable>
              <Pressable style={styles.modalButton} onPress={saveServerUrl}>
                <Text style={styles.modalButtonText}>Save</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0f2e5c', justifyContent: 'center', padding: 24 },
  brand: { color: '#fff', fontSize: 40, fontWeight: 'bold', textAlign: 'center' },
  sub: { color: '#93b4e8', textAlign: 'center', marginBottom: 32, fontSize: 13 },
  input: { backgroundColor: '#fff', borderRadius: 8, padding: 12, marginBottom: 12, fontSize: 16 },
  button: { backgroundColor: '#1a5fb4', borderRadius: 8, padding: 14, alignItems: 'center', marginTop: 8 },
  buttonText: { color: '#fff', fontWeight: 'bold', fontSize: 16 },
  serverLink: { marginTop: 20, alignSelf: 'center' },
  serverLinkText: { color: '#93b4e8', fontSize: 12, textDecorationLine: 'underline' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)', justifyContent: 'center', padding: 24 },
  modalCard: { backgroundColor: '#fff', borderRadius: 12, padding: 20 },
  modalTitle: { fontWeight: 'bold', color: '#0f2e5c', marginBottom: 8 },
  modalHint: { color: '#64748b', fontSize: 12, marginBottom: 10 },
  modalInput: { borderWidth: 1, borderColor: '#cbd5e1', borderRadius: 8, padding: 10, marginBottom: 12, backgroundColor: '#fff' },
  modalRow: { flexDirection: 'row', gap: 10 },
  modalButton: { backgroundColor: '#1a5fb4', borderRadius: 8, paddingVertical: 10, paddingHorizontal: 18, flex: 1, alignItems: 'center' },
  modalButtonText: { color: '#fff', fontWeight: '600' }
})
