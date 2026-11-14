import { expect, test, type Page } from '@playwright/test';

const fixture = '/__ui-rework-table';
const list = '/__ui-rework-table-list';
const bulk = '/__ui-rework-table-bulk';

async function openTable(page: Page) {
  await page.route(`**${fixture}`, route => route.fulfill({ contentType: 'text/html', body: `<!doctype html><html lang="en"><head><title>Table recovery</title></head><body>
    <main><h1>Learners</h1><div data-page-table="learners">
    <button type="button" data-table-refresh="learners">Refresh learners</button>
    <div data-bulk-bar="learners" hidden><span data-bulk-count></span><button type="button" data-bulk-action="${bulk}">Update selected</button></div>
    <div class="page-table__scroll"><table id="learners" data-page-table-init data-url="${list}"><thead><tr><th data-checkbox="true">Select</th><th data-field="name">Name</th></tr></thead><tbody></tbody></table></div>
    <div data-table-state></div><template data-error-template="learners">Learners could not be loaded.</template>
    </div></main><script src="/assets/js/custom/page.js"></script></body></html>` }));
  await page.goto(fixture);
  await expect(page.getByRole('cell', { name: 'First learner', exact: true })).toBeVisible();
}

test('a failed reload clears old bulk selections and recovery acts only on fresh rows', async ({ page }) => {
  let reads = 0;
  let release!: () => void;
  const hold = new Promise<void>(resolve => { release = resolve; });
  await page.route(`**${list}?*`, async route => {
    reads++;
    if (reads === 2) { await hold; await route.fulfill({ status: 500, json: { error: true } }); return; }
    await route.fulfill({ json: { total: 1, rows: [{ id: reads === 1 ? 1 : 2, name: reads === 1 ? 'First learner' : 'Fresh learner' }] } });
  });
  const writes: string[] = [];
  await page.route(`**${bulk}`, route => { writes.push(route.request().postData() || ''); return route.fulfill({ json: { error: false } }); });
  await openTable(page);
  await page.getByRole('checkbox', { name: 'Select row 1', exact: true }).check();
  await expect(page.getByRole('button', { name: 'Update selected' })).toBeVisible();
  await page.getByRole('button', { name: 'Refresh learners' }).click();
  await expect(page.locator('#learners')).toHaveAttribute('aria-busy', 'true');
  await expect(page.getByRole('checkbox', { name: 'Select row 1', exact: true })).toBeDisabled();
  expect(await page.evaluate(() => (window as any).PageTable.selections('learners'))).toEqual([]);
  await page.locator('[data-bulk-action]').dispatchEvent('click');
  expect(writes).toHaveLength(0);
  release();
  await expect(page.getByText('Learners could not be loaded.')).toBeVisible();
  expect(await page.evaluate(() => (window as any).PageTable.get('learners').rows)).toEqual([]);
  await expect(page.locator('[data-bulk-bar]')).toBeHidden();
  await page.getByRole('button', { name: 'Refresh learners' }).click();
  await expect(page.getByRole('cell', { name: 'Fresh learner', exact: true })).toBeVisible();
  await page.getByRole('checkbox', { name: 'Select row 1', exact: true }).check();
  await page.getByRole('button', { name: 'Update selected' }).click();
  await expect.poll(() => writes.length).toBe(1);
  expect(writes[0]).toMatch(/name="ids\[\]"\r?\n\r?\n2/);
});

test('a confirmation cannot submit selections from a replaced result set', async ({ page }) => {
  await page.route(`**${list}?*`, route => route.fulfill({ json: { total: 1, rows: [{ id: 1, name: 'First learner' }] } }));
  let writes = 0;
  await page.route(`**${bulk}`, route => { writes++; return route.fulfill({ json: { error: false } }); });
  await openTable(page);
  await page.evaluate(() => {
    document.querySelector('[data-bulk-action]')!.setAttribute('data-bulk-confirm', 'Update selected learners?');
    (window as any).pageConfirm = () => new Promise(resolve => { (window as any).resolveTableConfirmation = resolve; });
  });
  await page.getByRole('checkbox', { name: 'Select row 1', exact: true }).check();
  await page.getByRole('button', { name: 'Update selected' }).click();
  await page.evaluate(() => (window as any).PageTable.refresh('learners'));
  await page.evaluate(() => (window as any).resolveTableConfirmation(true));
  await expect(page.locator('[data-bulk-bar]')).toBeHidden();
  expect(writes).toBe(0);
});
