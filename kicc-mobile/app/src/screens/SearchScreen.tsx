import React, { useState } from 'react';
import {
  View, Text, TextInput, FlatList, TouchableOpacity, StyleSheet,
  ActivityIndicator, Keyboard,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { apiGet, cdn } from '../lib/api';

interface Hit {
  embeddable_type: string;
  embeddable_id: number;
  score: number;
  metadata: { name?: string; slug?: string; county?: string; description?: string; image_url?: string; type?: string };
}

export default function SearchScreen() {
  const nav = useNavigation<any>();
  const [q, setQ] = useState('');
  const [hits, setHits] = useState<Hit[]>([]);
  const [searching, setSearching] = useState(false);
  const [error, setError] = useState('');

  const run = async () => {
    if (q.trim().length < 2) return;
    Keyboard.dismiss();
    setSearching(true);
    setError('');
    try {
      const res = await apiGet<{ hits: Hit[] }>(`/search/semantic?q=${encodeURIComponent(q.trim())}&limit=20`);
      setHits(res.hits ?? []);
    } catch (e: any) {
      setError(e.message);
      setHits([]);
    } finally {
      setSearching(false);
    }
  };

  const openHit = (h: Hit) => {
    const slug = h.metadata?.slug;
    if (h.embeddable_type === 'App\\Models\\County' && slug) {
      nav.navigate('Counties', { slug });
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.searchBar}>
        <TextInput
          style={styles.input}
          placeholder="Search products, counties, attractions…"
          value={q}
          onChangeText={setQ}
          onSubmitEditing={run}
          returnKeyType="search"
          autoCapitalize="none"
        />
        <TouchableOpacity style={styles.btn} onPress={run} disabled={searching}>
          {searching ? <ActivityIndicator color="#fff" /> : <Text style={styles.btnText}>Search</Text>}
        </TouchableOpacity>
      </View>

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <FlatList
        data={hits}
        keyExtractor={(h) => `${h.embeddable_type}-${h.embeddable_id}`}
        contentContainerStyle={styles.list}
        ListEmptyComponent={() => !searching && q.length >= 2 ? <Text style={styles.empty}>No results. Try different words.</Text> : null}
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => openHit(item)}>
            {item.metadata?.image_url ? (
              <View style={styles.thumb}><Text style={styles.thumbText}>🖼</Text></View>
            ) : null}
            <View style={styles.rowBody}>
              <Text style={styles.rowName}>{item.metadata?.name ?? 'Untitled'}</Text>
              <Text style={styles.rowType}>
                {(item.metadata?.type ?? item.embeddable_type.split('\\').pop() ?? 'item')} · {item.metadata?.county ?? 'KICC'}
              </Text>
              <Text style={styles.rowScore}>Match {(item.score * 100).toFixed(1)}%</Text>
              {item.metadata?.description ? <Text style={styles.rowDesc} numberOfLines={2}>{item.metadata.description}</Text> : null}
            </View>
          </TouchableOpacity>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f7fa' },
  searchBar: { flexDirection: 'row', padding: 12, backgroundColor: '#fff', gap: 8 },
  input: { flex: 1, backgroundColor: '#f0f3f7', borderRadius: 10, paddingHorizontal: 14, paddingVertical: 10, fontSize: 14 },
  btn: { backgroundColor: '#046BD2', borderRadius: 10, paddingHorizontal: 16, alignItems: 'center', justifyContent: 'center' },
  btnText: { color: '#fff', fontWeight: '700' },
  error: { color: '#e0503a', padding: 12 },
  list: { padding: 12 },
  empty: { textAlign: 'center', color: '#5a6b80', marginTop: 40 },
  row: { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 12, padding: 12, marginBottom: 8 },
  thumb: { width: 44, height: 44, borderRadius: 8, backgroundColor: '#e7f1fb', alignItems: 'center', justifyContent: 'center', marginRight: 10 },
  thumbText: { fontSize: 18 },
  rowBody: { flex: 1 },
  rowName: { fontSize: 15, fontWeight: '700', color: '#0b1f33' },
  rowType: { fontSize: 11, color: '#046BD2', marginTop: 2 },
  rowScore: { fontSize: 11, color: '#0f9d58', fontWeight: '700', marginTop: 2 },
  rowDesc: { fontSize: 12, color: '#5a6b80', marginTop: 4, lineHeight: 16 },
});