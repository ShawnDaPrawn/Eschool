import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(action = 'initial', card = ''): Record<string, string | number> {
  const value = execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/cards-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, CARD_FIXTURE_ACTION: action, CARD_FIXTURE_ID: card },
  });
  return JSON.parse(value.trim());
}
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`private cards, CR80 print and exact reader retry at ${width}px`, async ({ page, browser, baseURL }) => {
    const setup = fixture();
    await page.setViewportSize({ width, height: 1000 });
    if (width === 390) await page.clock.install();
    const errors = collectErrors(page, baseURL!);
    await signIn(page, accounts.admin);
    await page.goto(`/school-cards?type=learner&person_id=${setup.person}`);
    await expect(page.getByRole('heading', { name: String(setup.name), exact: true }).first()).toBeVisible();
    await page.getByRole('button', { name: 'Issue card', exact: true }).click();
    const article = page.locator('article').filter({ has: page.getByRole('heading', { name: String(setup.name), exact: true }) }).filter({ has: page.getByRole('button', { name: 'Download printer PDF', exact: true }) }).first();
    await expect(article.getByRole('button', { name: 'Download printer PDF', exact: true })).toBeVisible();
    const image = article.getByRole('img', { name: 'Private card portrait', exact: true });
    await expect(image).toBeVisible();
    const id = (await image.getAttribute('src'))!.match(/cards\/([a-f0-9-]{36})\/portrait/)![1];
    for (const layout of ['landscape', 'portrait']) {
      await article.getByRole('combobox', { name: 'Printer layout', exact: true }).selectOption(layout);
      const download = page.waitForEvent('download');
      await article.getByRole('button', { name: 'Download printer PDF', exact: true }).click();
      expect((await download).suggestedFilename()).toBe('school-cards.pdf');
    }
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.goto('/school-card-readers');
    await page.getByRole('textbox', { name: 'Reader name', exact: true }).fill(`Synthetic gate ${width}`);
    await page.getByRole('textbox', { name: 'Device public encryption key', exact: true }).fill(String(setup.public_key));
    await page.getByRole('button', { name: 'Register reader', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Pair a card reader', exact: true })).toBeVisible();
    const credential = (await page.locator('code').textContent())!.trim();
    expect(credential).toMatch(/^NED1\./);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    // Admin cookies deliberately remain in this context: device transport must omit them.
    await page.goto('/card-reader');
    await page.getByLabel('Private reader credential', { exact: true }).fill(credential);
    await page.getByRole('button', { name: 'Start reader', exact: true }).click();
    await page.getByLabel('Secure app code or personalized tap', { exact: true }).fill(`NELOC1.1.${id}`);
    await page.getByRole('button', { name: 'Verify card', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('A printed locator is not a secure proof.');
    const proof = fixture('proof', id);
    let interrupted = false;
    const operations: string[] = [];
    await page.route('**/api/v2/card-device/scan/qr', async route => {
      const request = route.request();
      const headers = await request.allHeaders();
      expect(headers['cookie']).toBeUndefined();
      expect(headers['referer']).toBeUndefined();
      expect(headers['authorization']).toBe(`Bearer ${credential}`);
      expect(headers['x-school-id']).toBe('1');
      operations.push(request.postDataJSON().operation_id);
      if (!interrupted) {
        interrupted = true;
        const committed = await route.fetch();
        expect(committed.status()).toBe(200);
        await route.abort('failed');
      } else await route.continue();
    });
    await page.getByLabel('Secure app code or personalized tap', { exact: true }).fill(String(proof.credential));
    await page.getByRole('button', { name: 'Verify card', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Retry last verification', exact: true })).toBeVisible();
    expect(fixture('scan_count', id).count).toBe(1);
    await page.getByRole('button', { name: 'Retry last verification', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Verified card', exact: true })).toBeVisible();
    const privateImage = page.getByRole('img', { name: 'Current private card portrait', exact: true });
    await expect.poll(() => privateImage.evaluate((node: HTMLImageElement) => node.naturalWidth)).toBeGreaterThan(0);
    expect(operations).toHaveLength(2);
    expect(operations[0]).toBe(operations[1]);
    expect(fixture('scan_count', id).count).toBe(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    if (width === 390) {
      // Keep the body pending after real server authorization. A full-body timeout
      // must erase old pixels and retain the exact operation for safe recovery.
      await page.clock.fastForward(16000);
      await expect(page.locator('[data-reader-photo]')).not.toHaveAttribute('src', /blob:/);
      await expect(page.getByRole('status')).toHaveText('Scan a school card.');
      const next = fixture('proof', id);
      let delayed = false;
      await page.route('**/api/v2/card-device/scans/*/portrait', async route => {
        if (delayed) { await route.continue(); return; }
        delayed = true;
        const imageResponse = await route.fetch();
        expect(imageResponse.status()).toBe(200);
        await page.clock.fastForward(16000);
        await route.fulfill({ response: imageResponse }).catch(() => { /* The client deliberately aborted this expired body. */ });
      });
      await page.getByLabel('Secure app code or personalized tap', { exact: true }).fill(String(next.credential));
      await page.getByRole('button', { name: 'Verify card', exact: true }).click();
      await expect(page.getByRole('button', { name: 'Retry last verification', exact: true })).toBeVisible();
      await expect(page.locator('[data-reader-photo]')).not.toHaveAttribute('src', /blob:/);
      await page.getByRole('button', { name: 'Retry last verification', exact: true }).click();
      await expect(page.getByRole('heading', { name: 'Verified card', exact: true })).toBeVisible();
      expect(operations).toHaveLength(4);
      expect(operations[2]).toBe(operations[3]);
      expect(fixture('scan_count', id).count).toBe(2);
    }
    // Headless Chromium does not change tab visibility on bringToFront. Exercise
    // the registered visibility handler with a controlled hidden document state.
    await page.evaluate(() => {
      Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'hidden' });
      document.dispatchEvent(new Event('visibilitychange'));
      Reflect.deleteProperty(document, 'visibilityState');
    });
    await expect(page.locator('[data-reader-photo]')).not.toHaveAttribute('src', /blob:/);
    await expect(page.getByRole('button', { name: 'Verify card', exact: true })).toBeHidden();
    await expect(page.getByLabel('Private reader credential', { exact: true })).toHaveValue('');
    fixture('freeze', id);
    await page.goto('/school-cards');
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.filter((value, index) => ids.indexOf(value) !== index)).toEqual([]);
    expect(errors.filter(value => !value.includes('net::ERR_FAILED') && !value.includes('Failed to fetch'))).toEqual([]);
    const teacherContext = await browser.newContext({ baseURL });
    const teacher = await teacherContext.newPage();
    await signIn(teacher, accounts.teacher);
    expect((await teacher.request.get('/school-cards')).status()).toBe(403);
    await teacherContext.close();
  });
}
