import { expect, test } from '@playwright/test';
import { accounts, csrfToken, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
import { artisanFixture } from '../support/artisan-fixture';

test.use({ storageState: { cookies: [], origins: [] } });

for (const width of [375, 1280]) {
  test(`attendance and leave task screens stay separate and accessible at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(180_000);
    await page.setViewportSize({ width, height: 850 });
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    for (const path of ['/attendance-monthly', '/attendance-report',
      '/leave-request', '/student-leave-request', '/staff-leave', '/leave-master',
      '/leave-master?view=create', '/leave-calendar', '/facilitator-leave', '/school-modules/readiness']) {
      expect((await page.goto(path))?.status(), path).toBe(200);
      expect(new URL(page.url()).pathname, path).toBe(new URL(path, baseURL!).pathname);
      await expect(page.locator('main h1'), path).toHaveCount(1);
      for (const table of await page.locator('main table[data-page-table-init]').all()) await expect(table, path).toHaveAttribute('aria-busy', 'false');
      expect(await page.locator('main table[data-page-table-init]').evaluateAll(nodes => nodes.every(node => !node.closest('.bootstrap-table'))), path).toBe(true);
      expect(await horizontalOverflow(page), path).toBeLessThanOrEqual(1);
      expect(await seriousViolations(page), path).toEqual([]);
    }
    await page.goto('/leave-master');
    await expect(page.locator('#leave-configuration-create')).toHaveCount(0);
    const configuration = page.locator('[data-leave-record]').first();
    await expect(configuration).toBeVisible();
    await expect(page.locator('.bootstrap-table table[data-page-table-init]')).toHaveCount(0);
    await configuration.click();
    await expect(page.getByRole('dialog', { name: 'Edit leave configuration' })).toBeVisible();
    await expect(page.locator('#leave-configuration-edit [name="total_leave"]')).not.toHaveValue('');
    await expect(page.locator('.bootstrap-table table[data-page-table-init]')).toHaveCount(0);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    await page.keyboard.press('Escape');
    await expect(configuration).toBeFocused();
    expect(errors).toEqual([]);
  });

  test(`a teacher loads the actual register and leave day portions at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(90_000);
    await page.setViewportSize({ width, height: 850 });
    await signIn(page, accounts.teacher);
    const errors = collectErrors(page, baseURL!);
    expect((await page.goto('/view-attendance'))?.status()).toBe(200);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect((await page.goto('/attendance'))?.status()).toBe(200);
    const save = page.getByRole('button', { name: 'Save attendance', exact: true });
    await expect(save).toBeDisabled();
    await page.locator('#capture-class').selectOption({ index: 1 });
    const date = await page.locator('#capture-date').getAttribute('min');
    expect(date).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    await page.locator('#capture-date').fill(date!);
    await page.locator('#capture-date').dispatchEvent('change');
    await expect(page.locator('#capture-roster tr').first()).toBeVisible();
    await expect(save).toBeEnabled();
    await expect(page.locator('#capture-date-payload')).toHaveValue(date!.split('-').reverse().join('-'));
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    await page.locator('#capture-holiday').check();
    await expect(page.getByRole('alertdialog')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('#capture-holiday')).not.toBeChecked();

    expect((await page.goto('/leave?view=create'))?.status()).toBe(200);
    await expect(page.locator('#leave-create-form')).toBeVisible();
    const firstDate = await page.locator('#leave-from').getAttribute('min');
    expect(firstDate).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    const last = new Date(`${firstDate}T12:00:00Z`);
    last.setUTCDate(last.getUTCDate() + 6);
    const lastDate = [last.toISOString().slice(0, 10), await page.locator('#leave-to').getAttribute('max')].filter(Boolean).sort()[0]!;
    await page.locator('#leave-from').fill(firstDate!);
    await page.locator('#leave-to').fill(lastDate);
    await page.locator('#leave-to').dispatchEvent('change');
    const portions = page.locator('[data-leave-portions] select');
    await expect(portions.first()).toBeVisible();
    await portions.first().selectOption('First Half');
    await page.locator('#leave-to').dispatchEvent('change');
    await expect(portions.first()).toHaveValue('First Half');
    await expect(page.getByRole('button', { name: 'Submit leave request', exact: true })).toBeVisible();
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(errors).toEqual([]);
  });
}

for (const width of [375, 1280]) {
  test(`leave history shows approved dates without private request data at ${width}px`, async ({ page, baseURL }) => {
    test.setTimeout(90_000);
    const fixture = JSON.parse(artisanFixture("echo json_encode(require base_path('e2e/support/leave-calendar-history-fixture.php'));"));
    await page.setViewportSize({ width, height: 850 });
    await signIn(page, fixture.email);
    const errors = collectErrors(page, baseURL!);
    await page.goto(`/leave-calendar?person_type=staff&week=${fixture.date}`);
    const card = page.locator('article').filter({ has: page.getByRole('heading', { name: fixture.name, exact: true }) });
    await expect(card).toContainText(fixture.date);
    await expect(page.locator('main')).not.toContainText(fixture.reason);
    const [response] = await Promise.all([
      page.waitForResponse(result => new URL(result.url()).pathname === `/leave-calendar/${fixture.record}`),
      card.getByRole('link', { name: 'Open request', exact: true }).click(),
    ]);
    expect(response.status()).toBe(200);
    expect(response.headers()['cache-control']).toContain('no-store');
    await expect(page.getByRole('heading', { name: 'Leave request', exact: true })).toBeVisible();
    await expect(page.locator('main')).toContainText('This history view shows approved dates and day portions.');
    await expect(page.locator('main')).toContainText(fixture.date);
    await expect(page.locator('main')).toContainText('Full');
    await expect(page.locator('main')).not.toContainText(fixture.reason);
    await expect(page.getByRole('heading', { name: 'Original reason', exact: true })).toHaveCount(0);
    expect(await page.content()).not.toContain(fixture.file);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    expect(errors).toEqual([]);
  });
}


test('leave configuration saves retain their selected year without switching the global year', async ({ page }) => {
  test.setTimeout(120_000);
  await page.setViewportSize({ width: 375, height: 850 });
  await signIn(page, accounts.admin);
  const originalYear = await page.locator('#filter_session_year_global').inputValue();
  const stamp = String(Date.now());
  const prepareYear = async (offset: number) => {
    const year = new Date().getFullYear() + offset;
    const response = await page.request.post('/new-year/year', {
      headers: { 'X-CSRF-TOKEN': await csrfToken(page), Accept: 'application/json' },
      data: { name: `Leave configuration ${year} ${stamp}`, start_date: `${year}-01-01`, end_date: `${year}-12-31`,
        fee_due_date: `${year}-02-01`, fee_due_charges: 0, terms: [] },
    });
    expect(response.ok()).toBe(true);
    const saved = await response.json();
    expect(saved.error).toBe(false);
    return String(saved.data.id);
  };
  const firstYear = await prepareYear(10), secondYear = await prepareYear(11);
  await page.goto('/leave-master?view=create');
  await page.locator('#create-monthly-leave').fill('2');
  await page.locator('#create-leave-year').selectOption(firstYear);
  await page.locator('#leave-configuration-create [name="holiday_days[]"][value="Sunday"]').check();
  await page.locator('#leave-configuration-create [data-submit]').click();
  await expect(page).toHaveURL(new RegExp(`/leave-master\\?configuration_year=${firstYear}$`));
  await expect(page.locator('#leave-configuration-year')).toHaveValue(firstYear);
  await expect(page.locator('[data-leave-record]')).toHaveCount(1);
  await expect(page.locator('#filter_session_year_global')).toHaveValue(originalYear);
  await page.locator('[data-leave-record]').click();
  await page.locator('#edit-leave-year').selectOption(secondYear);
  await page.locator('#leave-configuration-edit [data-submit]').click();
  await expect(page).toHaveURL(new RegExp(`/leave-master\\?configuration_year=${secondYear}$`));
  await expect(page.locator('#leave-configuration-year')).toHaveValue(secondYear);
  await expect(page.locator('[data-leave-record]')).toHaveCount(1);
  await expect(page.locator('#filter_session_year_global')).toHaveValue(originalYear);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await seriousViolations(page)).toEqual([]);
});
