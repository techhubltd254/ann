// KICC platform load test — k6.
// Run: k6 run infra/k6-load.js   (target: 10K concurrent API calls per blueprint §19)
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 50 },    // ramp
    { duration: '1m', target: 200 },    // sustained
    { duration: '30s', target: 500 },   // peak
    { duration: '30s', target: 0 },     // ramp down
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],       // <1% errors
    http_req_duration: ['p(95)<1500'],    // 95% under 1.5s
  },
};

const BASE = __ENV.BASE_URL || 'https://kicctest.org';

export default function () {
  const endpoints = ['/', '/counties', '/api/counties', '/national', '/healthz'];
  const url = BASE + endpoints[Math.floor(Math.random() * endpoints.length)];
  const res = http.get(url);
  check(res, {
    'status 200': (r) => r.status === 200,
    'no 5xx': (r) => r.status < 500,
  });
  sleep(0.3);
}
