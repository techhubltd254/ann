import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://kicctest.org';
const CDN_URL = 'https://kicc-r2-media.techhubltd254.workers.dev/storage';

const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');

export const options = {
  // Measured production profile (kicctest.org, 2-vCPU origin):
  //  - Public API + home/county pages are Redis-cached (60s TTL) -> serve from cache.
  //  - Sector/marketplace/search pages render live (Blade SSR) -> the real load.
  //  - Origin sustains ~30 req/s of live SSR + near-unlimited cached req/s.
  // For 10K concurrent, scale horizontally (more droplets + LB) and move
  // heavy SSR pages behind the CDN; this script is the measurement tool.
  stages: [
    { duration: '30s', target: 200 },
    { duration: '1m', target: 1000 },
    { duration: '1m', target: 5000 },
    { duration: '1m', target: 10000 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    errors: ['rate<0.05'],
    http_req_duration: ['p(95)<5000'],
    response_time: ['avg<2000'],
  },
  discardResponseBodies: true,
};

const SECTOR_SLUGS = ['tourism', 'hotels', 'products', 'farms', 'institutions', 'transport', 'health', 'culture'];
const COUNTY_SLUGS = ['muranga', 'nairobi-city', 'kiambu', 'nakuru', 'mombasa', 'kisumu', 'baringo', 'kilifi'];

export default function () {
  const headers = { 'User-Agent': 'k6-load-test/1.0', 'Accept-Encoding': 'gzip' };

  // 1. Homepage (web)
  {
    const r = http.get(`${BASE_URL}/`, { headers });
    check(r, { 'homepage 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    responseTime.add(r.timings.duration);
    sleep(0.3);
  }

  // 2. Counties index (web)
  {
    const r = http.get(`${BASE_URL}/counties`, { headers });
    check(r, { 'counties 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 3. County detail (web)
  {
    const slug = COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)];
    const r = http.get(`${BASE_URL}/counties/${slug}`, { headers });
    check(r, { 'county 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 4. Sector page (web)
  {
    const county = COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)];
    const sector = SECTOR_SLUGS[Math.floor(Math.random() * SECTOR_SLUGS.length)];
    const r = http.get(`${BASE_URL}/counties/${county}/sector/${sector}`, { headers });
    check(r, { 'sector valid': (r) => r.status === 200 || r.status === 404 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 5. Marketplace
  {
    const r = http.get(`${BASE_URL}/marketplace`, { headers });
    check(r, { 'marketplace 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 6. Exhibitions
  {
    const r = http.get(`${BASE_URL}/exhibitions`, { headers });
    check(r, { 'exhibitions 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 7. Search
  {
    const q = COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)];
    const r = http.get(`${BASE_URL}/search?q=${q}`, { headers });
    check(r, { 'search 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 8. API: counties (mobile HomeScreen)
  {
    const r = http.get(`${BASE_URL}/api/counties`, { headers });
    check(r, { 'api counties 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    responseTime.add(r.timings.duration);
    sleep(0.2);
  }

  // 9. API: county sector data (mobile SectorScreen — public now)
  {
    const county = COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)];
    const r = http.get(`${BASE_URL}/api/county-sector/${county}/data`, { headers });
    check(r, { 'api sector data 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }

  // 10. CDN: profile image (mimics image loading on home/county cards)
  {
    const r = http.get(`${CDN_URL}/counties/${COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)]}/profile.jpg`, { headers });
    check(r, { 'cdn image 200': (r) => r.status === 200 });
    errorRate.add(r.status >= 500);
    sleep(0.2);
  }
}