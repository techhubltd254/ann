import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'https://kicctest.org';

const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');

export const options = {
  stages: [
    { duration: '30s', target: 20 },   // Ramp up to 20 users
    { duration: '1m', target: 50 },    // Ramp to 50
    { duration: '1m', target: 100 },   // Peak 100
    { duration: '30s', target: 0 },    // Ramp down
  ],
  thresholds: {
    errors: ['rate<0.05'],             // <5% error rate
    http_req_duration: ['p(95)<2000'], // 95% under 2s
    response_time: ['avg<500'],        // Avg under 500ms
  },
};

const SECTOR_SLUGS = ['agriculture', 'tourism', 'health', 'education', 'culture', 'transport', 'commerce', 'hospitality'];
const COUNTY_SLUGS = ['muranga', 'nairobi', 'kiambu', 'nakuru', 'mombasa', 'kisumu'];

export default function () {
  const headers = { 'User-Agent': 'k6-load-test/1.0' };

  // 1. Homepage
  {
    const r = http.get(`${BASE_URL}/`, { headers });
    check(r, { 'homepage 200': (r) => r.status === 200 });
    errorRate.add(r.status !== 200);
    responseTime.add(r.timings.duration);
    sleep(0.5);
  }

  // 2. County page
  {
    const slug = COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)];
    const r = http.get(`${BASE_URL}/counties/${slug}`, { headers });
    check(r, { 'county 200': (r) => r.status === 200 });
    errorRate.add(r.status !== 200);
    sleep(0.3);
  }

  // 3. Sector page
  {
    const county = COUNTY_SLUGS[Math.floor(Math.random() * COUNTY_SLUGS.length)];
    const sector = SECTOR_SLUGS[Math.floor(Math.random() * SECTOR_SLUGS.length)];
    const r = http.get(`${BASE_URL}/counties/${county}/sector/${sector}`, { headers });
    // 404 is valid (sector may not exist for that county)
    check(r, { 'sector valid': (r) => r.status === 200 || r.status === 404 });
    errorRate.add(r.status >= 500);
    sleep(0.3);
  }

  // 4. Marketplace / products
  {
    const r = http.get(`${BASE_URL}/products`, { headers });
    check(r, { 'products 200': (r) => r.status === 200 });
    errorRate.add(r.status !== 200);
    sleep(0.3);
  }

  // 5. Exhibitions
  {
    const r = http.get(`${BASE_URL}/exhibitions`, { headers });
    check(r, { 'exhibitions 200': (r) => r.status === 200 });
    errorRate.add(r.status !== 200);
    sleep(0.2);
  }

  // 6. HLS video stream (verify the playlist loads)
  {
    const r = http.get(`${BASE_URL}/storage/kicc/4d/hls/kicc_hero/master.m3u8`, { headers });
    check(r, { 'hls playlist 200': (r) => r.status === 200 });
    errorRate.add(r.status !== 200);
    sleep(0.2);
  }

  // 7. Search
  {
    const r = http.get(`${BASE_URL}/search?q=muranga`, { headers });
    check(r, { 'search 200': (r) => r.status === 200 });
    errorRate.add(r.status !== 200);
    sleep(0.3);
  }
}
