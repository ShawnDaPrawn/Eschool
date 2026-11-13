import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect as playwrightExpect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { horizontalOverflow, seriousViolations } from '../support/checks';

const expect = playwrightExpect.configure({ timeout: 30_000 });
test.use({ storageState: { cookies: [], origins: [] } });
test.setTimeout(300_000);

test.beforeAll(() => {
  execFileSync('php', ['artisan', 'tinker', '--execute', String.raw`app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1, function () {
    $actor = \App\Models\User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $year = \App\Models\SessionYear::where('name', '2026')->firstOrFail();
    foreach (['mastery', 'early_years', 'peer_tutoring', 'early_warning'] as $module) app(\App\Services\Platform\Modules\ModuleGate::class)->change($module, true, $actor);
    if (!\App\Models\Academics\AcademicLeadership::where('user_id', $actor->id)->where('kind', 'principal')->where('session_year_id', $year->id)->exists()) {
      \App\Models\Academics\AcademicLeadership::create(['user_id' => $actor->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $year->id, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic workspace browser review']);
    }
  });`], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' });
});

for (const width of [320, 1280]) {
  test(`source workspaces separate operations and configuration at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await signIn(page, accounts.admin);
    const check = async () => {
      await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1);
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      expect(await seriousViolations(page)).toEqual([]);
    };
    await page.goto('/early-years');
    await expect(page.locator('input[name=description]')).toHaveCount(0);
    await page.getByRole('link', { name: 'Configuration', exact: true }).click();
    await page.getByRole('link', { name: 'Add a development area', exact: true }).click();
    const area = `Independent routines ${width} ${Date.now()}`;
    await page.getByRole('textbox', { name: 'Name', exact: true }).fill(area);
    await page.getByRole('textbox', { name: 'Description', exact: true }).fill('Synthetic school observation area');
    await check();
    await page.getByRole('button', { name: 'Add area', exact: true }).click();
    await expect(page.getByRole('link', { name: `Edit ${area}`, exact: true })).toBeVisible();
    await expect(page).toHaveURL(/view=configuration/);

    await page.goto('/academic-transitions?view=configuration');
    await expect(page.getByRole('form', { name: 'Save export retention', exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'Find a learner', exact: true })).toHaveCount(0);
    await check();

    await page.goto('/academic-timetable?view=rooms');
    await expect(page.getByRole('form', { name: 'Save a room', exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'Preview a lesson', exact: true })).toHaveCount(0);
    await check();
    await page.getByRole('link', { name: 'Bell schedules', exact: true }).click();
    await expect(page.getByRole('form', { name: 'Build bell schedule', exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'Save a room', exact: true })).toHaveCount(0);
    await check();
    await page.getByRole('link', { name: 'Import timetable', exact: true }).click();
    await expect(page.getByRole('form', { name: 'Read timetable sheet', exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'Preview a lesson', exact: true })).toHaveCount(0);
    await check();

    await page.goto('/peer-tutoring');
    await expect(page.locator('#peer-history')).toBeVisible();
    await expect(page.locator('#peer-schedule')).toHaveCount(0);
    await page.getByRole('link', { name: 'Schedule supervised peer tutoring', exact: true }).click();
    await expect(page.locator('#peer-schedule')).toBeVisible();
    await expect(page.locator('#peer-history')).toHaveCount(0);
    await check();

    await page.goto('/learner-notes?mode=configuration');
    await expect(page.getByRole('form', { name: 'Update school progress bands', exact: true })).toBeVisible();
    await expect(page.getByRole('form', { name: 'Choose learner notes', exact: true })).toHaveCount(0);
    await check();
    await page.goto('/risk-interventions/unseen?view=configuration');
    await expect(page.locator('input[name=school_days]')).toBeVisible();
    await expect(page.locator('#unseen-grade')).toHaveCount(0);
    await check();
    await page.goto('/risk-interventions/library?view=create');
    await expect(page.locator('[data-structured-list]')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Library actions', exact: true })).toHaveCount(0);
    await check();
  });
}
