import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });

test('admin publishes contacts and a policy document for My school', async ({ page, baseURL }) => {
  const errors = collectErrors(page, baseURL!);
  await signIn(page, accounts.adminB);
  await page.goto('/school-information');
  const phone = page.getByRole('textbox', { name: 'School phone', exact: true });
  const previous = await phone.inputValue();
  const title = `School policy ${Date.now()}`;
  try {
    for (const width of [1024, 1280]) {
      await page.setViewportSize({ width, height: 800 });
      expect(await seriousViolations(page)).toEqual([]);
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    }
    await phone.fill('012 345 6789');
    await page.locator('#school-information').getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: /successfully/i })).toBeVisible();
    await page.getByRole('textbox', { name: 'Document title', exact: true }).fill(title);
    await page.getByRole('combobox', { name: 'Document kind', exact: true }).selectOption('code_of_conduct');
    await page.getByLabel('PDF document', { exact: false }).setInputFiles({
      name: 'code-of-conduct.pdf', mimeType: 'application/pdf',
      buffer: Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF'),
    });
    await page.getByRole('button', { name: 'Publish document', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'School document published.' })).toBeVisible();
    const link = page.getByRole('link', { name: title, exact: true });
    await expect(link).toBeVisible();
    const info = await page.request.get('/api/v2/schools/2/my-school', { headers: { Origin: baseURL! } });
    expect(info.status()).toBe(200);
    const data = (await info.json()).data;
    expect(data.phone).toBe('012 345 6789');
    const document = data.documents.find((row: { title: string }) => row.title === title);
    expect(document.url).toContain('/storage/schools/2/school-documents/');
    const response = await page.request.get(document.url);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/pdf');
    await page.getByRole('button', { name: `Unlist document ${title}`, exact: true }).click();
    await expect(link).toHaveCount(0);
    expect(errors).toEqual([]);
  } finally {
    await page.goto('/school-information');
    await phone.fill(previous);
    await page.locator('#school-information').getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: /successfully/i })).toBeVisible();
  }
});

test('teacher can read school information but cannot edit it', async ({ page, baseURL }) => {
  await signIn(page, accounts.teacher);
  expect((await page.goto('/school-information'))?.status()).toBe(403);
  expect((await page.request.get('/api/v2/schools/1/my-school', { headers: { Origin: baseURL! } })).status()).toBe(200);
  expect((await page.request.get('/api/v2/schools/2/my-school', { headers: { Origin: baseURL! } })).status()).toBe(404);
});
