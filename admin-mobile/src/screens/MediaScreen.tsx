import * as ImagePicker from 'expo-image-picker'
import { useEffect, useState } from 'react'
import { ActivityIndicator, Alert, Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native'
import { deleteMedia, listMedia, uploadMedia, type MediaView } from '../lib/adminApi'
import { getServerUrl } from '../lib/settings'

export default function MediaScreen({ onBack }: { onBack: () => void }) {
  const [items, setItems] = useState<MediaView[]>([])
  const [serverUrl, setServerUrl] = useState('')
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  const load = async () => {
    setLoading(true)
    try {
      setItems(await listMedia())
    } catch (e) {
      setError((e as Error).message)
    }
    setLoading(false)
  }

  useEffect(() => {
    ;(async () => setServerUrl(await getServerUrl()))()
    load()
  }, [])

  const pick = async () => {
    const perm = await ImagePicker.requestMediaLibraryPermissionsAsync()
    if (!perm.granted) {
      Alert.alert('Permission needed', 'Allow photo access to upload media.')
      return
    }
    const res = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.7 })
    if (res.canceled || !res.assets?.length) return
    const asset = res.assets[0]
    setBusy(true)
    try {
      const mime = asset.mimeType ?? 'image/jpeg'
      await uploadMedia(asset.uri, mime, asset.fileName ?? `photo-${Date.now()}.jpg`)
      await load()
    } catch (e) {
      Alert.alert('Upload failed', (e as Error).message)
    }
    setBusy(false)
  }

  const remove = (m: MediaView) => {
    Alert.alert('Delete media', `${m.key.split('/').pop()} — cannot be undone.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: async () => {
          try {
            await deleteMedia(m.key)
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
        <Text style={styles.title}>Media library</Text>
        <Pressable onPress={pick} disabled={busy}>
          <Text style={styles.add}>{busy ? 'Uploading…' : '+ Upload'}</Text>
        </Pressable>
      </View>
      {loading && <ActivityIndicator color="#1a5fb4" style={{ marginTop: 30 }} />}
      {error !== '' && <Text style={styles.error}>{error}</Text>}
      <ScrollView contentContainerStyle={styles.grid}>
        {items.map((m) => (
          <View key={m.id} style={styles.cell}>
            {m.contentType.startsWith('image/') ? (
              <Image source={{ uri: `${serverUrl}${m.thumbUrl ?? m.url}` }} style={styles.thumb} resizeMode="cover" />
            ) : (
              <View style={[styles.thumb, styles.fileThumb]}>
                <Text style={styles.fileIcon}>📄</Text>
              </View>
            )}
            <Text numberOfLines={1} style={styles.name}>{m.key.split('/').pop()}</Text>
            <Pressable style={styles.delBtn} onPress={() => remove(m)}>
              <Text style={styles.delText}>Delete</Text>
            </Pressable>
          </View>
        ))}
        {!loading && items.length === 0 && <Text style={styles.empty}>No media yet — tap + Upload</Text>}
      </ScrollView>
    </View>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f1f5f9' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#e2e8f0' },
  back: { color: '#1a5fb4', fontWeight: '600' },
  title: { fontWeight: 'bold', fontSize: 16, color: '#0f2e5c' },
  add: { color: '#1a5fb4', fontWeight: 'bold' },
  error: { color: '#dc2626', textAlign: 'center', marginTop: 20 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', padding: 12, gap: 10 },
  cell: { width: '47%', backgroundColor: '#fff', borderRadius: 10, padding: 8 },
  thumb: { width: '100%', height: 110, borderRadius: 8, backgroundColor: '#e2e8f0' },
  fileThumb: { alignItems: 'center', justifyContent: 'center' },
  fileIcon: { fontSize: 36 },
  name: { color: '#334155', fontSize: 12, fontWeight: '600', marginTop: 6 },
  delBtn: { marginTop: 6, alignSelf: 'flex-start' },
  delText: { color: '#dc2626', fontSize: 12, fontWeight: '600' },
  empty: { width: '100%', textAlign: 'center', color: '#94a3b8', marginTop: 30 }
})
