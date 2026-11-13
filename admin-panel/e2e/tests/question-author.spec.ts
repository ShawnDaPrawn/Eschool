import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });
test.setTimeout(180_000);
let group: string;
test.beforeAll(() => {
  group = execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/question-author-fixture.php');"], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' }).trim();
  expect(group).toMatch(/^\d+$/);
});

test('structured options and accepted answers create a real question at 390px', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await signIn(page, accounts.teacher);
  await page.goto(`/question-banks/write?group=${group}`);
  const form = page.locator('[data-question-author]');
  await form.getByRole('combobox', { name: 'Answer type', exact: true }).selectOption('choice');
  await form.getByRole('textbox', { name: 'Question', exact: true }).fill(`One half as a decimal? ${Date.now()}`);
  await form.getByRole('textbox', { name: 'Worked explanation', exact: true }).fill('Divide one by two to get 0.5.');
  await form.getByRole('button', { name: 'Add option', exact: true }).click();
  await form.getByRole('button', { name: 'Add option', exact: true }).click();
  await form.getByRole('textbox', { name: 'Option or label text', exact: true }).nth(0).fill('0.5');
  await form.getByRole('textbox', { name: 'Option or label text', exact: true }).nth(1).fill('2');
  await form.getByRole('button', { name: 'Add accepted answer', exact: true }).click();
  await form.getByRole('combobox', { name: 'Correct option', exact: true }).selectOption('o1');
  const write = page.waitForRequest(request => request.method() === 'POST' && request.url().includes('/question-banks/'));
  await form.getByRole('button', { name: 'Approve this question', exact: true }).click();
  const sent = await write;
  expect(sent.postData()).toContain('o1: 0.5');
  await expect(page.getByRole('status').filter({ hasText: 'Question approved.' })).toBeVisible();
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await seriousViolations(page)).toEqual([]);
});

test('worked-step and diagram rows retain their exact source payload without losing hidden answer drafts', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await signIn(page, accounts.teacher);
  await page.goto(`/question-banks/write?group=${group}`);
  const form = page.locator('[data-question-author]');
  await form.getByRole('combobox', { name: 'Answer type', exact: true }).selectOption('steps');
  await form.getByRole('button', { name: 'Add step', exact: true }).click();
  await form.getByRole('button', { name: 'Add step', exact: true }).click();
  await form.getByRole('textbox', { name: 'Step question', exact: true }).nth(0).fill('First add two and two');
  await form.getByRole('textbox', { name: 'Accepted step answers, one per line', exact: true }).nth(0).fill('4');
  await form.getByRole('textbox', { name: 'Step question', exact: true }).nth(1).fill('Then double the answer');
  await form.getByRole('textbox', { name: 'Accepted step answers, one per line', exact: true }).nth(1).fill('8');
  await expect(form.locator('textarea[name=steps]')).toHaveValue('First add two and two | 4\nThen double the answer | 8');
  await form.getByRole('combobox', { name: 'Answer type', exact: true }).selectOption('diagram_label');
  await form.getByRole('button', { name: 'Add label', exact: true }).click();
  await form.getByRole('textbox', { name: 'Option or label text', exact: true }).fill('Leaf');
  await form.getByRole('button', { name: 'Add region', exact: true }).click();
  await form.getByRole('spinbutton', { name: 'Across from left (%)', exact: true }).fill('50');
  await form.getByRole('spinbutton', { name: 'Down from top (%)', exact: true }).fill('10');
  await form.getByRole('combobox', { name: 'Correct diagram label', exact: true }).selectOption('l1');
  await expect(form.locator('textarea[name=regions]')).toHaveValue('r1: 50, 10');
  await expect(form.locator('textarea[name=mapping]')).toHaveValue('r1: l1');
  await form.getByRole('combobox', { name: 'Answer type', exact: true }).selectOption('steps');
  await expect(form.getByRole('textbox', { name: 'Step question', exact: true }).nth(0)).toHaveValue('First add two and two');
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
});
