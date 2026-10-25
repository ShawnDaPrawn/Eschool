import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
function fixture(file: string): Record<string, any> {
  const raw = execFileSync('php', ['artisan', 'tinker', '--execute', `echo json_encode(require base_path('e2e/support/${file}'));`], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
  return JSON.parse(raw.trim());
}
for (const width of [390, 820, 1280]) {
  test(`no-account application, private resume, office offer and enrolment at ${width}px`, async ({ page, browser, baseURL }) => {
    const data = fixture('admissions-fixture.php');
    const errors = collectErrors(page, baseURL!);
    await page.setViewportSize({ width, height: 1000 });
    await page.goto(data.path);
    await page.locator('[name="intake_grade_id"]').selectOption(String(data.grade));
    for (const [key, value] of Object.entries({ first_name: 'Browser', last_name: 'Guardian', email: `application-${width}-${Date.now()}@example.test`, mobile: '+27820000000', relationship: 'guardian' })) {
      await page.locator(`[name="contact[${key}]"]`).fill(value);
    }
    await page.locator(`[name="answers[${data.fields['user.first_name']}]"]`).fill(`Browser learner ${width}`);
    await page.locator(`[name="answers[${data.fields['user.last_name']}]"]`).fill('Synthetic');
    await page.locator(`[name="answers[${data.fields['user.dob']}]"]`).fill('2015-01-10');
    // Exercise the real CSRF-protected form and minimum-age spam challenge.
    await page.waitForTimeout(2100);
    await page.getByRole('button', { name: 'Save and email my resume link', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Application status: Draft', exact: true })).toBeVisible();
    const reference = (await page.locator('p').filter({ hasText: 'Application reference' }).innerText()).match(/[a-f0-9-]{36}/)![0];
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const resume = new URL(fixture('admissions-mail-fixture.php').url);
    await page.goto(resume.pathname + resume.hash);
    await expect(page).not.toHaveURL(/#/);
    await page.getByRole('button', { name: 'Open my application securely', exact: true }).click();
    await expect(page.getByText('Contact email confirmed.', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Submit saved application', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Application status: Submitted', exact: true })).toBeVisible();
    const officeContext = await browser.newContext({ viewport: { width, height: 1000 } });
    const office = await officeContext.newPage();
    await signIn(office, accounts.admin);
    await office.goto(`/admissions?intake_id=${data.id}`);
    const application = office.getByRole('row').filter({ hasText: reference });
    await application.locator('[name="selected[]"]').check();
    await office.getByRole('button', { name: 'Offer places to selected families', exact: true }).click();
    await expect(office.getByRole('row').filter({ hasText: reference })).toContainText('Offered');
    // A normal reload sees the fresh office decision; no prior body snapshot is reused.
    await page.reload();
    await page.getByRole('button', { name: 'Accept place', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Application status: Accepted', exact: true })).toBeVisible();
    await office.reload();
    await office.getByRole('row').filter({ hasText: reference }).getByRole('link', { name: 'Open application', exact: true }).click();
    await expect(office.getByRole('heading', { name: 'Application review', exact: true })).toBeVisible();
    await office.locator('[name="reference"]').fill(`Synthetic receipt ${width}`);
    await office.getByRole('button', { name: 'Record paid at office', exact: true }).click();
    await office.locator('form[method="post"] [name="starts_on"]').fill(data.starts_on);
    await office.locator('[name="category_id"]').selectOption(String(data.category));
    await office.getByRole('button', { name: 'Enrol and send guardian invitation', exact: true }).click();
    await expect(office.getByText('Status: Enrolled', { exact: true })).toBeVisible();
    expect(await seriousViolations(office)).toEqual([]);
    expect(await horizontalOverflow(office)).toBeLessThanOrEqual(1);
    await page.reload();
    await expect(page.getByRole('heading', { name: 'Application status: Enrolled', exact: true })).toBeVisible();
    await expect(page.getByText('Enrolment is complete. Check your email for the guardian invitation and approval steps.', { exact: true })).toBeVisible();
    expect(errors).toEqual([]);
    await officeContext.close();
  });
}
