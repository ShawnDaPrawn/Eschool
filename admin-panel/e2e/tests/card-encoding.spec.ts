import { execFileSync } from 'node:child_process';
import { generateKeyPairSync, randomUUID } from 'node:crypto';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { horizontalOverflow, seriousViolations } from '../support/checks';

function fixture(publicKey = '', action = 'initial'): Record<string, string> {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/card-encoding-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8', env: { ...process.env, CARD_FIXTURE_ACTION: action, CARD_BROWSER_PUBLIC_KEY: publicKey },
  }).trim());
}
test.describe.configure({ mode: 'serial' });
for (const width of [390, 820, 1280]) {
  test(`encrypted chip plan, real emulator SUN confirmation and retiring workbench at ${width}px`, async ({ page }) => {
    const pair = generateKeyPairSync('x25519');
    const publicKey = pair.publicKey.export({ type: 'spki', format: 'der' }).subarray(-32).toString('base64');
    const privateKey = pair.privateKey.export({ type: 'pkcs8', format: 'der' }).subarray(-32).toString('base64');
    const setup = fixture(publicKey);
    await page.setViewportSize({ width, height: 1100 });
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await signIn(page, accounts.admin);
    await page.goto('/school-cards');
    const link = page.locator(`a[href*="/cards/${setup.card}/encoding"]`);
    await expect(link).toBeVisible(); await link.click();
    await expect(page.getByRole('heading', { name: 'Personalize secure chip', exact: true })).toBeVisible();
    const download = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Download local encoding utility', exact: true }).click();
    const zip = await download;
    expect(zip.suggestedFilename()).toBe('navigateed-card-encoder.zip');
    const archive = await zip.path();
    const members = execFileSync('python3', ['-c', 'import zipfile,sys,json; print(json.dumps(zipfile.ZipFile(sys.argv[1]).namelist()))', archive!], { encoding: 'utf8' });
    const names = JSON.parse(members) as string[];
    expect(names).toContain('nexencoder/__main__.py'); expect(names).toContain('requirements.txt');
    expect(names.some(name => /\.pem|outbox|__pycache__|tests\//.test(name))).toBe(false);
    await page.getByRole('combobox', { name: 'Registered encoder', exact: true }).selectOption(setup.device);
    await page.getByRole('textbox', { name: 'Inspected chip UID', exact: true }).fill(setup.uid);
    await page.getByRole('button', { name: 'Prepare encrypted chip plan', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Awaiting verified chip personalization' })).toBeVisible();
    const plan = (await page.locator('code').allTextContents()).find(value => /^[a-f0-9-]{36}$/.test(value))!;
    expect(plan).toMatch(/^[a-f0-9-]{36}$/);
    const headers = { Authorization: `Bearer ${setup.credential}`, 'X-School-Id': '1', Accept: 'application/json' };
    const response = await page.request.get(`/api/v2/card-encoder/plans/${plan}`, { headers });
    expect(response.status()).toBe(200);
    const envelope = (await response.json()).data;
    expect(JSON.stringify(envelope)).not.toContain(setup.uid);
    const emulated = JSON.parse(execFileSync('python3', [path.resolve('support/card-encoding-emulate.py')], {
      cwd: process.cwd(), input: JSON.stringify({ envelope, private: privateKey, plan }), encoding: 'utf8',
    }));
    expect(emulated.changes).toEqual([1, 2, 3, 4, 0]); expect(emulated.writes).toBe(2);
    const intent = { operation_id: randomUUID(), ...emulated.proof };
    const confirmed = await page.request.post(`/api/v2/card-encoder/plans/${plan}/confirm`, { headers, data: intent });
    expect(confirmed.status()).toBe(200);
    const receipt = (await confirmed.json()).data;
    expect(receipt.verified_counter).toBe(emulated.counter); expect(receipt.collection_authorized).toBe(false); expect(receipt.sign_in_authorized).toBe(false);
    expect((await (await page.request.post(`/api/v2/card-encoder/plans/${plan}/confirm`, { headers, data: intent })).json()).data).toEqual(receipt);
    await page.reload();
    await expect(page.getByRole('status').filter({ hasText: 'Secure chip verified' })).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.filter((value, index) => ids.indexOf(value) !== index)).toEqual([]);
    fixture('', 'withdraw_encoder');
    await page.evaluate(() => window.dispatchEvent(new Event('focus')));
    await expect(page.locator('[data-encoding-private]')).toBeEmpty();
    await expect(page.locator('[data-encoding-retired]')).toBeVisible();
    expect(errors).toEqual([]);
  });
}
