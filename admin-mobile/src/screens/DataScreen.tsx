import { useState } from 'react'
import { Alert, Pressable, ScrollView, StyleSheet, Text, TextInput, View, Switch } from 'react-native'
import { listLocal, deleteLocal, type LocalRow } from '../lib/db'
import { sync } from '../lib/sync'

type FieldDef = { key: string; label: string; type?: 'text' | 'number' | 'boolean' | 'multiline' }

const ENTITY_FIELDS: Record<string, FieldDef[]> = {
  attractions: [
    { key: 'name', label: 'Name' }, { key: 'category', label: 'Category' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'entryFee', label: 'Entry Fee' },
    { key: 'openingHours', label: 'Hours' }, { key: 'contact', label: 'Contact' },
    { key: 'latitude', label: 'Latitude', type: 'number' }, { key: 'longitude', label: 'Longitude', type: 'number' },
    { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  hotels: [
    { key: 'name', label: 'Name' }, { key: 'category', label: 'Category' },
    { key: 'starRating', label: 'Star Rating', type: 'number' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' }, { key: 'website', label: 'Website' },
    { key: 'latitude', label: 'Latitude', type: 'number' }, { key: 'longitude', label: 'Longitude', type: 'number' },
    { key: 'priceRangeMin', label: 'Price Min', type: 'number' }, { key: 'priceRangeMax', label: 'Price Max', type: 'number' },
    { key: 'amenities', label: 'Amenities' }, { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  farms: [
    { key: 'name', label: 'Name' }, { key: 'type', label: 'Type' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'contact', label: 'Contact' },
    { key: 'sizeAcres', label: 'Size (acres)', type: 'number' },
    { key: 'mainCrops', label: 'Main Crops' }, { key: 'products', label: 'Products' },
    { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  'health-facilities': [
    { key: 'name', label: 'Name' }, { key: 'type', label: 'Type' }, { key: 'level', label: 'Level' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' }, { key: 'services', label: 'Services' },
    { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  institutions: [
    { key: 'name', label: 'Name' }, { key: 'type', label: 'Type' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' }, { key: 'website', label: 'Website' },
    { key: 'studentCount', label: 'Students', type: 'number' }, { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  transport: [
    { key: 'name', label: 'Name' }, { key: 'type', label: 'Type' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'operator', label: 'Operator' },
    { key: 'contact', label: 'Contact' }, { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  'culture-sites': [
    { key: 'name', label: 'Name' }, { key: 'type', label: 'Type' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'location', label: 'Location' }, { key: 'community', label: 'Community' },
    { key: 'contact', label: 'Contact' }, { key: 'latitude', label: 'Latitude', type: 'number' },
    { key: 'longitude', label: 'Longitude', type: 'number' }, { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  products: [
    { key: 'name', label: 'Name' }, { key: 'category', label: 'Category' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'price', label: 'Price (KES)', type: 'number' }, { key: 'unit', label: 'Unit' },
    { key: 'status', label: 'Status' }, { key: 'isPublished', label: 'Published', type: 'boolean' }
  ],
  'sector-entities': [
    { key: 'name', label: 'Name' }, { key: 'sectorType', label: 'Sector Type' },
    { key: 'description', label: 'Description', type: 'multiline' },
    { key: 'captureStatus', label: 'Status' }, { key: 'sponsorFunderTag', label: 'Sponsor' },
    { key: 'contactInfo', label: 'Contact' }, { key: 'socialLinks', label: 'Social' },
    { key: 'latitude', label: 'Latitude', type: 'number' }, { key: 'longitude', label: 'Longitude', type: 'number' },
    { key: 'languagePrimary', label: 'Language' }, { key: 'tags', label: 'Tags' },
    { key: 'isPublished', label: 'Published', type: 'boolean' }
  ]
}

export default function DataScreen({ entityType, onBack }: { entityType: string; onBack: () => void }) {
  const [rows, setRows] = useState<LocalRow[]>(() => listLocal(entityType))
  const [editing, setEditing] = useState<LocalRow | null>(null)
  const [form, setForm] = useState<Record<string, string>>({})
  const [useJson, setUseJson] = useState(false)
  const [json, setJson] = useState('')

  const refresh = () => setRows(listLocal(entityType))
  const fields = ENTITY_FIELDS[entityType]

  const startEdit = (row: LocalRow) => {
    setEditing(row)
    if (fields) {
      const f: Record<string, string> = {}
      fields.forEach(({ key }) => { f[key] = String(row.payload[key] ?? '') })
      setForm(f)
      setUseJson(false)
    } else {
      setJson(JSON.stringify(row.payload, null, 2))
      setUseJson(true)
    }
  }

  const startNew = () => {
    const localId = ''
    setEditing({
      entityType,
      localId,
      payload: {},
      contentHash: '',
      updatedAt: '',
      synced: false
    } as LocalRow)
    if (fields) {
      const f: Record<string, string> = {}
      fields.forEach(({ key }) => { f[key] = '' })
      setForm(f)
      setUseJson(false)
    } else {
      setJson('{\n  "name": ""\n}')
      setUseJson(true)
    }
  }

  const save = async () => {
    if (!editing) return
    try {
      if (useJson) {
        const parsed = JSON.parse(json) as Record<string, unknown>
        sync.saveOffline(entityType, parsed)
      } else {
        const payload: Record<string, unknown> = {}
        fields?.forEach(({ key, type }) => {
          const val = form[key]
          if (type === 'number') payload[key] = val ? Number(val) : null
          else if (type === 'boolean') payload[key] = val === 'true'
          else payload[key] = val || ''
        })
        sync.saveOffline(entityType, payload)
      }
      setEditing(null)
      refresh()
    } catch (e) {
      Alert.alert('Save failed', (e as Error).message)
    }
  }

  const del = (row: LocalRow) => {
    Alert.alert('Delete', `Delete "${String(row.payload.name ?? row.localId)}"?`, [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: () => { deleteLocal(row.localId); refresh() } }
    ])
  }

  const setField = (key: string, value: string) => setForm({ ...form, [key]: value })

  if (editing) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <Pressable onPress={() => setEditing(null)}>
            <Text style={styles.back}>← Back</Text>
          </Pressable>
          <Text style={styles.title}>{editing.localId ? 'Edit' : 'New'} {entityType}</Text>
          {fields && (
            <Pressable onPress={() => setUseJson(!useJson)}>
              <Text style={styles.toggle}>{useJson ? 'Form' : 'JSON'}</Text>
            </Pressable>
          )}
        </View>
        <ScrollView style={styles.formScroll} contentContainerStyle={{ padding: 12 }}>
          {useJson ? (
            <TextInput style={styles.editor} value={json} onChangeText={setJson} multiline autoCapitalize="none" autoCorrect={false} textAlignVertical="top" />
          ) : fields?.map(({ key, label, type }) => (
            <View key={key} style={styles.fieldWrap}>
              <Text style={styles.label}>{label}</Text>
              {type === 'boolean' ? (
                <Switch value={form[key] === 'true'} onValueChange={(v) => setField(key, String(v))} />
              ) : type === 'multiline' ? (
                <TextInput style={[styles.input, styles.multiline]} value={form[key] ?? ''} onChangeText={(v) => setField(key, v)} multiline textAlignVertical="top" />
              ) : (
                <TextInput style={styles.input} value={form[key] ?? ''} onChangeText={(v) => setField(key, v)} keyboardType={type === 'number' ? 'decimal-pad' : 'default'} />
              )}
            </View>
          ))}
        </ScrollView>
        <Pressable style={styles.button} onPress={save}>
          <Text style={styles.buttonText}>Save (queues offline push)</Text>
        </Pressable>
      </View>
    )
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Pressable onPress={onBack}>
          <Text style={styles.back}>← Back</Text>
        </Pressable>
        <Text style={styles.title}>{entityType}</Text>
        <Pressable onPress={startNew}>
          <Text style={styles.new}>+ New</Text>
        </Pressable>
      </View>
      <ScrollView>
        {rows.length === 0 && <Text style={styles.empty}>No rows yet — pull county data from the dashboard</Text>}
        {rows.map((r) => (
          <View key={r.localId} style={styles.row}>
            <Pressable style={{ flex: 1 }} onPress={() => startEdit(r)}>
              <Text style={styles.rowTitle}>{String(r.payload.name ?? r.localId)}</Text>
              <Text style={styles.rowMeta}>{r.localId.slice(0, 8)} · {r.updatedAt.slice(0, 10)}</Text>
              <Text style={[styles.badge, { color: r.synced ? '#16a34a' : '#d97706' }]}>{r.synced ? 'synced' : 'pending'}</Text>
            </Pressable>
            <Pressable onPress={() => del(r)} style={styles.delBtn}>
              <Text style={styles.delText}>✕</Text>
            </Pressable>
          </View>
        ))}
      </ScrollView>
    </View>
  )
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f1f5f9' },
  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#e2e8f0' },
  back: { color: '#1a5fb4', fontWeight: '600' },
  new: { color: '#1a5fb4', fontWeight: 'bold' },
  toggle: { color: '#64748b', fontWeight: '600', fontSize: 13 },
  title: { fontWeight: 'bold', fontSize: 16, color: '#0f2e5c' },
  row: { backgroundColor: '#fff', marginHorizontal: 12, marginTop: 8, padding: 12, borderRadius: 8, flexDirection: 'row', alignItems: 'center' },
  rowTitle: { fontWeight: '600', color: '#334155' },
  rowMeta: { color: '#94a3b8', fontSize: 11, marginTop: 2 },
  badge: { fontWeight: 'bold', fontSize: 12, marginTop: 2 },
  delBtn: { padding: 8, marginLeft: 8 },
  delText: { color: '#dc2626', fontWeight: 'bold', fontSize: 16 },
  empty: { textAlign: 'center', color: '#94a3b8', marginTop: 40 },
  formScroll: { flex: 1 },
  fieldWrap: { marginBottom: 12 },
  label: { color: '#475569', fontSize: 12, fontWeight: '600', marginBottom: 4 },
  input: { backgroundColor: '#fff', borderRadius: 8, padding: 10, fontSize: 14, borderWidth: 1, borderColor: '#e2e8f0', color: '#334155' },
  multiline: { minHeight: 80, textAlignVertical: 'top' },
  editor: { backgroundColor: '#0f172a', color: '#e2e8f0', fontFamily: 'monospace', fontSize: 13, padding: 12, borderRadius: 8, minHeight: 200, textAlignVertical: 'top' },
  button: { backgroundColor: '#1a5fb4', borderRadius: 8, padding: 14, margin: 12, alignItems: 'center' },
  buttonText: { color: '#fff', fontWeight: 'bold' }
})
