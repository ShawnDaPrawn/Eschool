import { expect, test } from '@playwright/test';

/**
 * A Phase 0 security check: System Update and the GET maintenance URLs are
 * gone and answer 404 (removed by stream S1). Asked without signing in,
 * so nothing could run if one were still there.
 */
for (const path of ['/system-update', '/migrate', '/seeder_install', '/storage-link']) {
  test(`${path} is gone`, async ({ request }) => {
    const response = await request.get(path, { maxRedirects: 0, failOnStatusCode: false });
    expect(response.status()).toBe(404);
  });
}
