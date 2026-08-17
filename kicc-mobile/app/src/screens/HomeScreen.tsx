import React, { useEffect, useState } from 'react';
import {
  View, Text, FlatList, Image, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl, Dimensions,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { apiGet, cdn, County } from '../lib/api';

const { width } = Dimensions.get('window');
const CARD = (width - 48) / 2;

export default function HomeScreen() {
  const nav = useNavigation<any>();
  const [counties, setCounties] = useState<County[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = async (refresh = false) => {
    if (refresh) setRefreshing(true); else setLoading(true);
    try {
      const res = await apiGet<{ data: County[] }>('/counties');
      setCounties(res.data ?? []);
    } catch (e: any) {
      console.warn('Failed to load counties', e.message);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => { load(); }, []);

  const openCounty = (slug: string) => nav.navigate('Counties', { slug });
  const openSector = (slug: string, sector: string) => nav.navigate('Sector', { slug, sector });

  if (loading) {
    return (
      <View style={styles.center}><ActivityIndicator size="large" color="#046BD2" /></View>
    );
  }

  return (
    <FlatList
      data={counties}
      keyExtractor={(c) => c.slug}
      numColumns={2}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} />}
      ListHeaderComponent={() => (
        <View>
          <Text style={styles.title}>Explore Kenya by County</Text>
          <Text style={styles.subtitle}>47 counties · 7 sectors · real products & places</Text>
        </View>
      )}
      renderItem={({ item }) => (
        <TouchableOpacity style={styles.card} onPress={() => openCounty(item.slug)}>
          <Image
            source={{ uri: cdn(item.profile_image) }}
            style={styles.cardImage}
            resizeMode="cover"
          />
          <View style={styles.cardBody}>
            <Text style={styles.cardName}>{item.name}</Text>
            <Text style={styles.cardTag} numberOfLines={2}>{item.tagline}</Text>
            <View style={styles.sectorRow}>
              {(item.primary_sectors ?? []).slice(0, 3).map((s) => (
                <TouchableOpacity key={s} onPress={() => openSector(item.slug, s)}>
                  <Text style={styles.sectorChip}>{s}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        </TouchableOpacity>
      )}
    />
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#fff' },
  list: { padding: 16, backgroundColor: '#f5f7fa' },
  title: { fontSize: 22, fontWeight: '800', color: '#0b1f33', marginBottom: 4 },
  subtitle: { fontSize: 13, color: '#5a6b80', marginBottom: 16 },
  card: { width: CARD, backgroundColor: '#fff', borderRadius: 14, marginBottom: 16, overflow: 'hidden', elevation: 2, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 6, shadowOffset: { width: 0, height: 2 } },
  cardImage: { width: CARD, height: 100 },
  cardBody: { padding: 10 },
  cardName: { fontSize: 15, fontWeight: '700', color: '#0b1f33' },
  cardTag: { fontSize: 11, color: '#5a6b80', marginTop: 2, marginBottom: 6 },
  sectorRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 4 },
  sectorChip: { backgroundColor: '#e7f1fb', color: '#046BD2', fontSize: 10, paddingHorizontal: 8, paddingVertical: 3, borderRadius: 10, overflow: 'hidden' },
});