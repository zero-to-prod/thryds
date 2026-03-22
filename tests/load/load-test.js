import http from 'k6/http';
import { check, group } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://web';

export const options = {
    vus: 20,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<500'],
        http_req_failed: ['rate<0.01'],
    },
};

export function setup() {
    const res = http.get(`${BASE_URL}/`);
    check(res, { 'app is reachable': (r) => r.status === 200 });

    return ['/', '/about', '/login', '/register'];
}

export default function (routes) {
    for (const route of routes) {
        group(route, () => {
            const res = http.get(`${BASE_URL}${route}`);
            check(res, {
                'status 200': (r) => r.status === 200,
                'has body': (r) => r.body.length > 0,
            });
        });
    }
}

export function teardown() {
    const res = http.get(`${BASE_URL}/_opcache/status`);
    if (res.status === 200) {
        const status = JSON.parse(res.body);
        console.log(`OPcache hit rate: ${(status.opcache_statistics?.hit_rate ?? 0).toFixed(2)}%`);
        console.log(`OPcache memory used: ${((status.memory_usage?.used_memory ?? 0) / 1024 / 1024).toFixed(2)} MB`);
        console.log(`OPcache cached scripts: ${status.opcache_statistics?.num_cached_scripts ?? 'n/a'}`);
    }
}
