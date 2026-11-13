import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

type Setup = {group: number; learner: number; learner_name: string; today: string};
function fixture(): Setup {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/competition-panel-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
  }).trim());
}
async function teamSheet(page: Page, setup: Setup) {
  await page.getByRole('link', {name: 'Team sheet', exact: true}).click();
  const form = page.getByRole('form', {name: 'Your team sheet', exact: true});
  await form.getByRole('checkbox', {name: setup.learner_name, exact: true}).check();
  await form.locator(`[name="label[${setup.learner}]"]`).fill('Entrant 7');
  await form.locator(`[name="position[${setup.learner}]"]`).fill('Captain');
  await form.getByLabel('Private notes (only your school)', {exact: true}).fill('Synthetic private team evidence');
  await form.getByRole('button', {name: 'Save the team sheet', exact: true}).click();
  await expect(form.locator(`[name="label[${setup.learner}]"]`)).toHaveValue('Entrant 7');
}
async function accessible(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
  expect(ids.length).toBe(new Set(ids).size);
}

test.describe.configure({mode: 'serial'});
test.setTimeout(420_000);
test.use({storageState: {cookies: [], origins: []}});
for (const width of [390, 1280]) {
  test(`sports fixture creation, private team sheet and result at ${width}px`, async ({page, baseURL}) => {
    const setup = fixture();
    await page.setViewportSize({width, height: 1000});
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/activity-fixtures?screen=fixture-create');
    const form = page.getByRole('form', {name: 'Arrange a fixture', exact: true});
    await form.locator('[name="external_opponent"]').fill('Synthetic visiting school');
    await form.locator('[name="activity_group_id"]').selectOption(String(setup.group));
    await form.locator('[name="team_label"]').fill('U14 soccer boys');
    await form.locator('[name="discipline"]').fill('Soccer');
    await form.locator('[name="on_date"]').fill(setup.today);
    await form.locator('[name="venue"]').fill('Synthetic school field');
    await form.getByRole('button', {name: 'Arrange the fixture', exact: true}).click();
    await expect(page.getByRole('heading', {name: 'The fixture', exact: true})).toBeVisible();
    const url = page.url();
    await teamSheet(page, setup);
    await page.getByRole('link', {name: 'Edit or cancel fixture', exact: true}).click();
    await page.locator('#schedule-venue').fill('Synthetic second field');
    await page.getByRole('button', {name: 'Save the fixture', exact: true}).click();
    await expect(page.locator('#schedule-venue')).toHaveValue('Synthetic second field');
    await page.getByRole('link', {name: 'Result', exact: true}).last().click();
    await page.locator('#result-host').fill('3');
    await page.locator('#result-guest').fill('1');
    await page.getByRole('button', {name: 'Report the score', exact: true}).click();
    await page.getByRole('link', {name: 'Overview', exact: true}).last().click();
    await expect(page.getByText('3–1', {exact: true})).toBeVisible();
    await accessible(page);
    await page.screenshot({path: test.info().outputPath(`sports-fixture-${width}.png`), fullPage: true});
    expect(errors).toEqual([]);
    await page.context().clearCookies();
    await signIn(page, accounts.adminB);
    expect((await page.goto(url))?.status()).toBe(404);
    await expect(page.getByText('Synthetic private team evidence', {exact: true})).toHaveCount(0);
  });

  test(`academic competition entrants, adjudicators and human result at ${width}px`, async ({page, baseURL}) => {
    const setup = fixture();
    await page.setViewportSize({width, height: 1000});
    await signIn(page, accounts.admin);
    const errors = collectErrors(page, baseURL!);
    await page.goto('/academic-competitions?screen=create');
    const form = page.getByRole('form', {name: 'Arrange an academic competition', exact: true});
    await form.locator('[name="kind"]').selectOption('debate');
    await form.locator('[name="external_opponent"]').fill('Synthetic debate school');
    await form.locator('[name="activity_group_id"]').selectOption(String(setup.group));
    await form.locator('[name="team_label"]').fill('Junior debating team');
    await form.locator('[name="discipline"]').fill('Debating');
    await form.locator('[name="motion"]').fill('This house values evidence in school debates.');
    await form.locator('[name="on_date"]').fill(setup.today);
    await form.locator('[name="venue"]').fill('Synthetic school hall');
    await form.getByRole('button', {name: 'Arrange the competition', exact: true}).click();
    await expect(page.getByRole('heading', {name: 'The competition', exact: true})).toBeVisible();
    const url = page.url();
    await page.getByRole('link', {name: 'Invitation and entrants', exact: true}).click();
    await teamSheet(page, setup);
    await page.goto(url);
    await page.getByRole('link', {name: 'Adjudicators', exact: true}).click();
    await page.locator('#adjudicators-outside').fill('Synthetic outside adjudicator');
    await page.getByRole('button', {name: 'Save the adjudicators', exact: true}).click();
    await expect(page.locator('#adjudicators-outside')).toHaveValue('Synthetic outside adjudicator');
    await page.getByRole('link', {name: 'Result', exact: true}).last().click();
    await page.locator('#result-host').fill('75');
    await page.locator('#result-guest').fill('68');
    await page.locator('#result-winner').selectOption('host');
    await page.locator(`#entrant-score-${setup.learner}`).fill('75');
    await page.locator(`#entrant-place-${setup.learner}`).fill('1');
    await page.locator('#result-reasons').fill('Synthetic private adjudication: evidence and rebuttal reviewed.');
    await page.locator('#result-state').selectOption('draft');
    await page.getByRole('button', {name: 'Save the result', exact: true}).click();
    await expect(page.locator('#result-state')).toHaveValue('draft');
    await expect(page.locator('#result-reasons')).toHaveValue('Synthetic private adjudication: evidence and rebuttal reviewed.');
    await page.locator('#result-state').selectOption('final');
    await page.getByRole('button', {name: 'Save the result', exact: true}).click();
    await expect(page.locator('#result-state')).toHaveValue('final');
    await expect(page.getByText('Submitted', {exact: true})).toBeVisible();
    await accessible(page);
    await page.screenshot({path: test.info().outputPath(`academic-result-${width}.png`), fullPage: true});
    expect(errors).toEqual([]);
    await page.context().clearCookies();
    await signIn(page, accounts.adminB);
    expect((await page.goto(url))?.status()).toBe(404);
    await expect(page.getByText('Synthetic private adjudication: evidence and rebuttal reviewed.', {exact: true})).toHaveCount(0);
  });
}
