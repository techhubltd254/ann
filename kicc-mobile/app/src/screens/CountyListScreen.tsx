import React, { useEffect, useState } from 'react';
import {
  View, Text, Image, ScrollView, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl,
} from 'react-native';
import { RouteProp, useNavigation, useRoute } from '@react-navigation/native';
import { apiGet, cdn, County, Sector } from '../lib/api';

type RouteParams = { slug: string };

export default function CountyListScreen() {
  const nav = useNavigation<any>();
  const route = useRoute<RouteProp<Record<string, RouteParams>, string>>();
  const slug = route.params?.slug ?? 'mombasa';
  const [county, setCounty] = useState<County | null>(null);
  const [sectors, setSectors] = useState<Sector[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = async (refresh = false) => {
    if (refresh) setRefreshing(true); else setLoading(true);
    try {
      const res = await apiGet<{ county: County; current_month: any }>(`/counties/${slug}`);
      setCounty(res.county);
      const s = await apiGet<Sector[]>(`/counties/${slug}/sectors`);
      setSectors(s ?? []);
    } catch (e: any) {
      console.warn('Failed to load county', e.message);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => { load(); }, [slug]);

  const openSector = (sector: string) => nav.navigate('Sector', { slug, sector });

  if (loading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#046BD2" /></View>;
  }
  if (!county) return <View style={styles.center}><Text>County not found</Text></View>;

  return (
    <ScrollView
      contentContainerStyle={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} />}
    >
      <Image source={{ uri: cdn(county.profile_image) }} style={styles.hero} resizeMode="cover" />
      <View style={styles.body}>
        <Text style={styles.name}>{county.name} County</Text>
        <Text style={styles.tagline}>{county.tagline}</Text>
        <Text style={styles.desc}>{county.description}</Text>

        <View style={styles.statsRow}>
          <View style={styles.stat}><Text style={styles.statNum}>{(county.population_2024 / 1_000_000).toFixed(1)}M</Text><Text style={styles.statLabel}>Population</Text></View>
          <View style={styles.stat}><Text style={styles.statNum}>{county.area_km2} km²</Text><Text style={styles.statLabel}>Area</Text></View>
          <View style={styles.stat}><Text style={styles.statNum}>{county.economic_zone}</Text><Text style={styles.statLabel}>Zone</Text></View>
        </View>

        {(county.tourism_highlights ?? []).length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Highlights</Text>
            {county.tourism_highlights.map((h) => <Text key={h} style={styles.highlight}>• {h}</Text>)}
          </View>
        )}

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Sectors</Text>
          {sectors.map((s) => (
            <TouchableOpacity key={s.id} style={styles.sectorRow} onPress={() => openSector(s.slug ?? s.name)}>
              <View style={styles.sectorIcon}><Text style={styles.sectorIconText}>{s.icon ?? '▦'}</Text></View>
              <Text style={styles.sectorName}>{s.name}</Text>
              <Text style={styles.sectorChevron}>›</Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#fff' },
  container: { backgroundColor: '#fff', paddingBottom: 40 },
  hero: { width: '100%', height: 200 },
  body: { padding: 16 },
  name: { fontSize: 24, fontWeight: '800', color: '#0b1f33' },
  tagline: { fontSize: 13, color: '#046BD2', marginTop: 2, marginBottom: 8, fontStyle: 'italic' },
  desc: { fontSize: 14, lineHeight: 21, color: '#3a4a5f' },
  statsRow: { flexDirection: 'row', marginTop: 16, gap: 10 },
  stat: { flex: 1, backgroundColor: '#f0f5fb', borderRadius: 10, padding: 12, alignItems: 'center' },
  statNum: { fontSize: 15, fontWeight: '700', color: '#0b1f33' },
  statLabel: { fontSize: 10, color: '#5a6b80', marginTop: 2, textAlign: 'center' },
  section: { marginTop: 24 },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: '#0b1f33', marginBottom: 10 },
  highlight: { fontSize: 13, color: '#3a4a5f', marginBottom: 4 },
  sectorRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#eef1f5' },
  sectorIcon: { width: 36, height: 36, borderRadius: 10, backgroundColor: '#046BD2', alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  sectorIconText: { color: '#fff', fontSize: 16 },
  sectorName: { flex: 1, fontSize: 15, color: '#0b1f33' },
  sectorChevron: { fontSize: 20, color: '#b0b8c4' },
});