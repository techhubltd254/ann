import React, { useEffect, useState } from 'react';
import {
  View, Text, FlatList, Image, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl,
} from 'react-native';
import { RouteProp, useNavigation, useRoute } from '@react-navigation/native';
import { apiGet, cdn } from '../lib/api';

type RouteParams = { slug: string; sector: string };

const SECTOR_META: Record<string, { label: string; color: string }> = {
  tourism: { label: 'Tourism & Attractions', color: '#0f9d58' },
  hotels: { label: 'Hotels & Hospitality', color: '#046BD2' },
  farms: { label: 'Agriculture & Farms', color: '#e39b2b' },
  products: { label: 'Products & Trade', color: '#7b3ff2' },
  institutions: { label: 'Education & Institutions', color: '#e0503a' },
  transport: { label: 'Transport & Logistics', color: '#0e8a8a' },
  health: { label: 'Healthcare', color: '#d2426e' },
  culture: { label: 'Culture & Heritage', color: '#6b4a8a' },
};

export default function SectorScreen() {
  const nav = useNavigation<any>();
  const route = useRoute<RouteProp<Record<string, RouteParams>, string>>();
  const { slug, sector } = route.params ?? { slug: 'mombasa', sector: 'tourism' };
  const meta = SECTOR_META[sector] ?? { label: sector, color: '#046BD2' };
  const [entities, setEntities] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = async (refresh = false) => {
    if (refresh) setRefreshing(true); else setLoading(true);
    try {
      const res = await apiGet<Record<string, any[]>>(`/county-sector/${slug}/data`);
      setEntities(res[sector] ?? []);
    } catch (e: any) {
      console.warn('Failed to load sector data', e.message);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => { load(); }, [slug, sector]);

  const openEntity = (entity: any) => {
    if (sector === 'products') {
      nav.navigate('Product', {
        county: slug,
        productId: entity.id,
        name: entity.name,
        description: entity.description,
        price: entity.price,
        unit: entity.unit,
        image_url: entity.image_url,
        category: entity.category,
        user_id: entity.user_id,
      });
    }
  };

  if (loading) {
    return <View style={styles.center}><ActivityIndicator size="large" color={meta.color} /></View>;
  }

  const imageUrl = (e: any) => e.image_url ? cdn(e.image_url) : '';

  return (
    <FlatList
      data={entities}
      keyExtractor={(e, i) => String(e.id ?? i)}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} />}
      ListHeaderComponent={() => (
        <View style={styles.header}>
          <Text style={[styles.title, { color: meta.color }]}>{meta.label}</Text>
          <Text style={styles.count}>{entities.length} listings in {slug}</Text>
        </View>
      )}
      ListEmptyComponent={() => <Text style={styles.empty}>No listings yet for this sector.</Text>}
      renderItem={({ item }) => (
        <TouchableOpacity style={styles.card} onPress={() => openEntity(item)}>
          {imageUrl(item) ? (
            <Image source={{ uri: imageUrl(item) }} style={styles.image} resizeMode="cover" />
          ) : (
            <View style={[styles.image, styles.placeholder, { backgroundColor: meta.color + '22' }]}>
              <Text style={[styles.placeholderText, { color: meta.color }]}>{meta.label}</Text>
            </View>
          )}
          <View style={styles.body}>
            <Text style={styles.name}>{item.name}</Text>
            {item.location ? <Text style={styles.location}>📍 {item.location}</Text> : null}
            <Text style={styles.desc} numberOfLines={2}>{item.description}</Text>
            {item.latitude ? (
              <Text style={styles.coords}>{item.latitude.toFixed ? item.latitude.toFixed(4) : item.latitude}, {item.longitude?.toFixed ? item.longitude.toFixed(4) : item.longitude}</Text>
            ) : null}
            {sector === 'products' && item.price ? <Text style={styles.price}>KES {item.price}</Text> : null}
          </View>
        </TouchableOpacity>
      )}
    />
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#fff' },
  list: { padding: 16, backgroundColor: '#f5f7fa', paddingBottom: 40 },
  header: { marginBottom: 12 },
  title: { fontSize: 22, fontWeight: '800' },
  count: { fontSize: 12, color: '#5a6b80', marginTop: 2 },
  empty: { textAlign: 'center', color: '#5a6b80', marginTop: 40 },
  card: { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 12, marginBottom: 12, overflow: 'hidden', elevation: 1, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, shadowOffset: { width: 0, height: 1 } },
  image: { width: 96, height: 96 },
  placeholder: { alignItems: 'center', justifyContent: 'center' },
  placeholderText: { fontSize: 11, fontWeight: '700' },
  body: { flex: 1, padding: 10 },
  name: { fontSize: 15, fontWeight: '700', color: '#0b1f33' },
  location: { fontSize: 11, color: '#046BD2', marginTop: 2 },
  desc: { fontSize: 12, color: '#3a4a5f', marginTop: 4, lineHeight: 16 },
  coords: { fontSize: 10, color: '#9aa5b4', marginTop: 4 },
  price: { fontSize: 14, fontWeight: '700', color: '#0f9d58', marginTop: 4 },
});