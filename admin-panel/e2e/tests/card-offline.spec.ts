import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'initial', card = '', publicKey = ''): Record<string, string | number> {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/card-offline-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, CARD_FIXTURE_ACTION: action, CARD_FIXTURE_ID: card, CARD_BROWSER_PUBLIC_KEY: publicKey },
  }).trim());
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`protected offline identity, durable replay and exact reconciliation at ${width}px`, async ({ page, context }) => {
    await page.setViewportSize({ width, height: 1000 });
    if (width === 390) await page.clock.install();
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/card-reader/offline');
    await page.getByRole('button', { name: 'Create protected reader key', exact: true }).click();
    const publicField = page.getByLabel('Public key for school reader registration', { exact: true });
    await expect(publicField).toHaveValue(/^[A-Za-z0-9+/]{43}=$/);
    const publicKey = await publicField.inputValue();
    const setup = fixture('initial', '', publicKey), card = String(setup.card), credential = String(setup.credential);
    const pair = async () => {
      await page.getByLabel('Private reader credential', { exact: true }).fill(credential);
      await page.getByRole('button', { name: 'Prepare offline cache', exact: true }).click();
      await expect(page.locator('[data-offline-ready]')).toBeVisible();
    };
    await pair();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.filter((value, index) => ids.indexOf(value) !== index)).toEqual([]);
    const proof = String(fixture('proof', card).credential);
    await context.setOffline(true);
    await page.getByLabel('Current signed app code', { exact: true }).fill(proof);
    const started = Date.now();
    await page.getByRole('button', { name: 'Verify cached identity', exact: true }).click();
    const photo = page.getByRole('img', { name: 'Private card portrait from the current cache', exact: true });
    await expect(photo).toBeVisible();
    await expect.poll(() => photo.evaluate((image: HTMLImageElement) => Math.max(image.naturalWidth, image.naturalHeight))).toBe(128);
    expect(Date.now() - started).toBeLessThan(2000); // local browser evidence, not physical gate qualification
    expect(fixture('scan_count', card).count).toBe(0);
    await page.getByLabel('Current signed app code', { exact: true }).fill(proof);
    await page.getByRole('button', { name: 'Verify cached identity', exact: true }).click();
    await expect(page.locator('[data-offline-result]')).toBeHidden();
    await expect(page.locator('[data-offline-status]')).toContainText('already used');
    await context.setOffline(false);
    await page.reload();
    await expect(publicField).toHaveValue(publicKey); // nonextractable CryptoKey survives IndexedDB roundtrip
    await pair(); // saved exact operation is synchronized automatically
    await expect.poll(() => fixture('scan_count', card).count).toBe(1);
    await page.getByLabel('Current signed app code', { exact: true }).fill(proof);
    await page.getByRole('button', { name: 'Verify cached identity', exact: true }).click();
    await expect(page.locator('[data-offline-result]')).toBeHidden(); // durable nonce survives page/key restore
    await expect(page.locator('[data-offline-status]')).toContainText('could not be saved or was already used');
    expect(fixture('scan_count', card).count).toBe(1);
    // A real committed response is lost: encrypted retry keeps its original UUID.
    const nextProof = String(fixture('proof', card).credential), operations: string[] = [];
    let interrupted = false;
    await page.route('**/api/v2/card-device/offline/reconcile', async route => {
      const request = route.request(), headers = await request.allHeaders();
      expect(headers.cookie).toBeUndefined(); expect(headers.referer).toBeUndefined();
      expect(headers.authorization).toBe(`Bearer ${credential}`); expect(headers['x-school-id']).toBe('1');
      operations.push(request.postDataJSON().operation_id);
      if (!interrupted) { interrupted = true; const response = await route.fetch(); expect(response.status()).toBe(200); await route.abort('failed'); }
      else await route.continue();
    });
    await page.getByLabel('Current signed app code', { exact: true }).fill(nextProof);
    await page.getByRole('button', { name: 'Verify cached identity', exact: true }).click();
    await expect(page.locator('[data-offline-status]')).toContainText('outcome is unconfirmed');
    await page.getByRole('button', { name: 'Synchronize saved scans', exact: true }).click();
    await expect(page.locator('[data-offline-pending]')).toContainText(': 0');
    expect(operations).toHaveLength(2); expect(operations[0]).toBe(operations[1]);
    expect(fixture('scan_count', card).count).toBe(2);
    if (width === 390) {
      await page.clock.fastForward(59000);
      await expect(page.locator('[data-offline-cache-status]')).toContainText(': 0');
      await expect(page.locator('[data-offline-portrait]')).not.toHaveAttribute('src', /blob:/);
      await expect(page.locator('[data-offline-status]')).toContainText('Offline cache expired');
    }
    await page.evaluate(() => { Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'hidden' }); document.dispatchEvent(new Event('visibilitychange')); Reflect.deleteProperty(document, 'visibilityState'); });
    await expect(page.locator('[data-offline-portrait]')).not.toHaveAttribute('src', /blob:/);
    await expect(page.getByLabel('Private reader credential', { exact: true })).toHaveValue('');
    await expect(page.locator('[data-offline-ready]')).toBeHidden();
    expect(errors).toEqual([]);
  });
}

test('disabled script forms never put private reader/proof fields in URL or request body', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ baseURL, javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('/card-reader');
  await page.getByLabel('Private reader credential', { exact: true }).fill('synthetic-sensitive-reader-credential');
  const request = page.waitForRequest(value => value.isNavigationRequest() && value.method() === 'POST');
  await page.getByRole('button', { name: 'Start reader', exact: true }).click();
  const submitted = await request;
  expect(submitted.url()).not.toContain('synthetic-sensitive');
  expect(submitted.postData() ?? '').not.toContain('synthetic-sensitive');
  expect(submitted.url()).not.toContain('?');
  await context.close();
});

test('real IndexedDB atomically excludes two-tab nonce replay and persists only encrypted scan payloads', async ({ page }) => {
  await page.goto('/card-reader/offline');
  const result = await page.evaluate(async () => {
    const load = new Function('path', 'return import(path)') as (path: string) => Promise<any>;
    const { OfflineReaderStore, decryptPendingRecord } = await load('/assets/js/card-offline-store.mjs');
    const { createOfflineReaderKey } = await load('/assets/js/card-offline-crypto.mjs');
    const database = `synthetic-offline-atomic-${crypto.randomUUID()}`;
    const first = await OfflineReaderStore.open(database), second = await OfflineReaderStore.open(database);
    const created = await first.createKey(await createOfflineReaderKey());
    const deviceId = crypto.randomUUID(), scope = { schoolId: 1, deviceId, keyId: created.id };
    const key = await first.bind(created.id, 1, deviceId);
    const input = { operation_id: crypto.randomUUID(), manifest: 'NEOS1.synthetic-private-manifest', credential: 'NEQ1.' + crypto.randomUUID(), scanned_at: 1000 };
    const hash = 'a'.repeat(64);
    const writes = await Promise.allSettled([first.claimAndQueue(key, scope, input, hash, 1025), second.claimAndQueue(key, scope, { ...input, operation_id: crypto.randomUUID() }, hash, 1025)]);
    const pending = await first.pending(scope);
    const clear = await decryptPendingRecord(key.privateKey, scope, pending[0].id, pending[0].packet);
    const rebound = await Promise.allSettled([first.bind(key.id, 2, deviceId)]);
    first.close(); second.close();
    const restored = await OfflineReaderStore.open(database), storedKey = await restored.key(key.id);
    const replay = await Promise.allSettled([restored.claimAndQueue(storedKey, scope, { ...input, operation_id: crypto.randomUUID() }, hash, 1025)]);
    const output = { accepted: writes.filter(item => item.status === 'fulfilled').length, records: pending.length, encrypted: !JSON.stringify(pending).includes(input.credential) && !JSON.stringify(pending).includes(input.manifest), restored: storedKey.privateKey.extractable === false, decoded: clear.credential === input.credential, deniedRebind: rebound[0].status === 'rejected', deniedReplay: replay[0].status === 'rejected' };
    restored.close(); indexedDB.deleteDatabase(database);
    return output;
  });
  expect(result).toEqual({ accepted: 1, records: 1, encrypted: true, restored: true, decoded: true, deniedRebind: true, deniedReplay: true });
});
