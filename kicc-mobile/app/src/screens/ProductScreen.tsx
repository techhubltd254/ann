import React from 'react';
import {
  View, Text, ScrollView, Image, TouchableOpacity, StyleSheet, Alert,
} from 'react-native';
import { RouteProp, useRoute } from '@react-navigation/native';
import { cdn } from '../lib/api';
import { useCart } from '../lib/cart';

type RouteParams = { county: string; productId: number };

export default function ProductScreen() {
  const route = useRoute<RouteProp<Record<string, RouteParams>, string>>();
  const { add } = useCart();
  const product = route.params as any;
  const id = product?.productId;

  const entity = {
    id,
    name: product?.name ?? 'Product',
    description: product?.description ?? 'No description available.',
    price: product?.price ?? 0,
    unit: product?.unit ?? '',
    image_url: product?.image_url ?? '',
    category: product?.category ?? 'general',
    sellerId: product?.user_id ?? undefined,
  };

  const addToCart = () => {
    add({ id: entity.id, name: entity.name, price: entity.price, unit: entity.unit, sellerId: entity.sellerId });
    Alert.alert('Added to cart', `${entity.name} added to your cart.`);
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      {entity.image_url ? (
        <Image source={{ uri: cdn(entity.image_url) }} style={styles.image} resizeMode="cover" />
      ) : (
        <View style={[styles.image, styles.placeholder]}><Text style={styles.placeholderText}>{entity.name}</Text></View>
      )}
      <View style={styles.body}>
        <Text style={styles.name}>{entity.name}</Text>
        <Text style={styles.category}>{entity.category}</Text>
        <Text style={styles.price}>KES {Number(entity.price).toLocaleString()} {entity.unit ? `/ ${entity.unit}` : ''}</Text>
        <Text style={styles.desc}>{entity.description}</Text>
        <TouchableOpacity style={styles.button} onPress={addToCart}>
          <Text style={styles.buttonText}>Add to Cart</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.button, styles.buyButton]} onPress={addToCart}>
          <Text style={styles.buttonText}>Buy Now</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { backgroundColor: '#fff', paddingBottom: 40 },
  image: { width: '100%', height: 240 },
  placeholder: { backgroundColor: '#e7f1fb', alignItems: 'center', justifyContent: 'center' },
  placeholderText: { fontSize: 20, fontWeight: '800', color: '#046BD2' },
  body: { padding: 16 },
  name: { fontSize: 22, fontWeight: '800', color: '#0b1f33' },
  category: { fontSize: 12, color: '#046BD2', textTransform: 'uppercase', marginTop: 2 },
  price: { fontSize: 20, fontWeight: '800', color: '#0f9d58', marginTop: 8 },
  desc: { fontSize: 14, lineHeight: 21, color: '#3a4a5f', marginTop: 12 },
  button: { backgroundColor: '#046BD2', borderRadius: 12, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  buyButton: { backgroundColor: '#0f9d58', marginTop: 10 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});