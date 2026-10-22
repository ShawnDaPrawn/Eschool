// k6 smoke test for one NavigateEd environment (Build Bible 5.2: "A k6 smoke
// test runs against staging at each gate"). It checks that the panel, the
// public API and a few v1 reads answer correctly, and holds them to the
// Bible's p95 budgets: 300 ms for list endpoints and, once API v2 serves it,
// 150 ms for GET /api/v2/me/context.
//
// Run by hand:
//   k6 run -e BASE_URL=https://staging-admin.navigateed.co.za engineering/perf/k6-smoke.js
// or from GitHub: Actions > k6 smoke > Run workflow (.github/workflows/k6-smoke.yml).
//
// Environment:
//   BASE_URL       required: the panel and API address, no trailing slash needed
//   STUDENT_TOKEN  optional: a staging demo learner's app token (Sanctum bearer)
//   TEACHER_TOKEN  optional: a staging demo teacher's app token
//   DURATION       optional: how long to run (default 2m)
// Never use a real user's token, and never point it at production with load.
//
// The API allows 60 requests a minute per token and per address, so this
// is a smoke test, not a load test: one virtual user, one round every five
// seconds (about 40 requests a minute per token at most).

import http from 'k6/http';
import { check, group, sleep } from 'k6';

const BASE_URL = (__ENV.BASE_URL || '').replace(/\/+$/, '');
if (!BASE_URL) {
    throw new Error('Set BASE_URL, for example: k6 run -e BASE_URL=https://staging-admin.navigateed.co.za engineering/perf/k6-smoke.js');
}
const STUDENT_TOKEN = __ENV.STUDENT_TOKEN || '';
const TEACHER_TOKEN = __ENV.TEACHER_TOKEN || '';
const CONTEXT_TOKEN = STUDENT_TOKEN || TEACHER_TOKEN;

const thresholds = {
    // Correctness: almost nothing may fail.
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    // Bible 5.2 budgets.
    'http_req_duration{type:list}': ['p(95)<300'],
};
if (CONTEXT_TOKEN) {
    thresholds['http_req_duration{type:context}'] = ['p(95)<150'];
}

export const options = {
    scenarios: {
        smoke: {
            executor: 'constant-vus',
            vus: 1,
            duration: __ENV.DURATION || '2m',
        },
    },
    thresholds,
    summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'max'],
};

function bearer(token) {
    return { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } };
}

// The v1 and v2 envelope: {error, message, data, code}.
function envelopeOk(response) {
    try {
        return response.json('error') === false;
    } catch (e) {
        return false;
    }
}

function getJson(name, path, type, params = { headers: { Accept: 'application/json' } }) {
    const response = http.get(`${BASE_URL}${path}`, { ...params, tags: { name, type } });
    check(response, {
        [`${name}: 200`]: (r) => r.status === 200,
        [`${name}: envelope error=false`]: (r) => envelopeOk(r),
    });
    return response;
}

export function setup() {
    // /api/v2/me/context arrives with the API v2 skeleton; until then it is skipped.
    let contextExists = false;
    if (CONTEXT_TOKEN) {
        const probe = http.get(`${BASE_URL}/api/v2/me/context`, { ...bearer(CONTEXT_TOKEN), tags: { name: 'probe', type: 'probe' } });
        contextExists = probe.status !== 404;
    }
    console.log(`k6 smoke against ${BASE_URL}: learner reads ${STUDENT_TOKEN ? 'on' : 'off'}, teacher reads ${TEACHER_TOKEN ? 'on' : 'off'}, /api/v2/me/context ${contextExists ? 'on' : 'off'}`);
    return { contextExists };
}

export default function (data) {
    group('health', () => {
        const response = http.get(`${BASE_URL}/up`, { tags: { name: 'up', type: 'health' } });
        check(response, { 'up: 200': (r) => r.status === 200 });
    });

    group('panel', () => {
        const response = http.get(`${BASE_URL}/login`, { tags: { name: 'login page', type: 'page' } });
        check(response, {
            'login page: 200': (r) => r.status === 200,
            'login page: has the form': (r) => typeof r.body === 'string' && r.body.includes('<form'),
            'login page: X-Request-Id': (r) => Boolean(r.headers['X-Request-Id']),
        });
    });

    group('public API (v1)', () => {
        getJson('holidays', '/api/holidays', 'list');
        getJson('events', '/api/get-events-list', 'list');
        getJson('app settings', '/api/settings?type=app_settings', 'read');
    });

    if (STUDENT_TOKEN) {
        group('learner API (v1)', () => {
            getJson('student timetable', '/api/student/timetable', 'list', bearer(STUDENT_TOKEN));
            getJson('student announcements', '/api/student/announcements', 'list', bearer(STUDENT_TOKEN));
            getJson('student assignments', '/api/student/assignments', 'list', bearer(STUDENT_TOKEN));
        });
    }

    if (TEACHER_TOKEN) {
        group('teacher API (v1)', () => {
            getJson('teacher classes', '/api/teacher/classes', 'list', bearer(TEACHER_TOKEN));
            getJson('teacher timetable', '/api/teacher/teacher_timetable', 'list', bearer(TEACHER_TOKEN));
            getJson('teacher notifications', '/api/teacher/get-notification', 'list', bearer(TEACHER_TOKEN));
        });
    }

    if (data.contextExists) {
        group('API v2', () => {
            getJson('me context', '/api/v2/me/context', 'context', bearer(CONTEXT_TOKEN));
        });
    }

    sleep(5);
}
