export const API_URL = 'https://kicctest.org/api';
export const CDN_URL = 'https://kicc-r2-media.techhubltd254.workers.dev/storage';

export interface County {
  id: number;
  name: string;
  slug: string;
  tagline: string;
  description: string;
  profile_image: string;
  primary_sectors: string[];
  population_2024: number;
  area_km2: number;
  economic_zone: string;
  latitude: number;
  longitude: number;
  tourism_highlights: string[];
}

export interface Sector {
  id: number;
  name: string;
  slug: string;
  icon?: string;
  description?: string;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  short_description: string;
  price: number;
  image_url?: string;
  unit: string;
  county_id: number;
  category_id: number;
  is_featured: boolean;
}

export function cdn(relativePath?: string): string {
  if (!relativePath) return '';
  return `${CDN_URL}/${relativePath.replace(/^\//, '')}`;
}

export async function apiGet<T>(path: string, token?: string): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  const res = await fetch(`${API_URL}${path}`, { headers });
  if (!res.ok) {
    const body = await res.text();
    throw new Error(body || `Request failed: ${res.status}`);
  }
  return res.json() as Promise<T>;
}

export async function apiPost<T>(path: string, body: any, token?: string): Promise<T> {
  const headers: Record<string, string> = { 'Content-Type': 'application/json', Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  const res = await fetch(`${API_URL}${path}`, { method: 'POST', headers, body: JSON.stringify(body) });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(text || `Request failed: ${res.status}`);
  }
  return res.json() as Promise<T>;
}