import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-lifecycle-fixture.php'));"], {
    cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8',
    env: { ...process.env, BILLING_FIXTURE_ACTION: action, BILLING_FIXTURE_SCHOOL: String(school) },
  }).trim());
}
async function check(page: Page) {
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await page.locator('[id]').evaluateAll(nodes => nodes.map(n => n.id).filter((id, index, all) => all.indexOf(id) !== index))).toEqual([]);
}
async function submitOnceAndReplay(page: Page, label: string) {
  const button = page.getByRole('button', { name: label, exact: true });
  const form = button.locator('xpath=ancestor::form');
  const fields = await form.evaluate(node => Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string, string>);
  const action = await form.getAttribute('action');
  await button.click();
  await expect(page.getByRole('status')).toBeVisible();
  expect((await page.request.post(action!, { form: fields })).status()).toBe(200);
}
test.describe.configure({ mode: 'serial', retries: 0 });
test.use({ storageState: { cookies: [], origins: [] } });


for (const width of [390,820,1280]) {
 test(`Finance reviews an exact mid-year charge and original invoice at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(180_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);const errors=collectErrors(page,baseURL!);
  await page.goto('/fee-lifecycle');await page.getByRole('combobox',{name:'Mid-year join charging',exact:true}).selectOption('daily');await page.getByRole('combobox',{name:'Known leaver charging',exact:true}).selectOption('daily');await page.getByRole('combobox',{name:'Installments for a newly posted bill',exact:true}).selectOption('remaining_dates');
  await check(page);await submitOnceAndReplay(page,'Save enrolment fee policy');
  await page.goto('/fee-bill-runs/new');await page.getByRole('main').getByRole('combobox',{name:'School year',exact:true}).selectOption(String(data.year));await page.getByRole('textbox',{name:'Enrolment date for this bill run',exact:true}).fill(data.date);await page.getByRole('textbox',{name:'Invoice due date',exact:true}).fill(data.due);await page.getByRole('checkbox',{name:'I confirm no VAT is charged on these annual tuition fees.',exact:true}).check();await page.getByRole('button',{name:'Prepare bill preview',exact:true}).click();
  await expect(page.getByRole('button',{name:'Approve bill preview',exact:true})).toBeVisible();await page.getByText('Enrolment charge calculation',{exact:true}).click();await expect(page.getByText('Original annual plan rate',{exact:true})).toBeVisible();await expect(page.getByText('Charged period',{exact:true})).toBeVisible();await check(page);
  await page.getByRole('button',{name:'Approve bill preview',exact:true}).click();await page.getByRole('checkbox',{name:'I confirm these approved charges should be invoiced.',exact:true}).check();await submitOnceAndReplay(page,'Post approved invoices');
  const stats=fixture('stats',data.school);expect(stats.receivable_cents).toBe(data.expected_cents);expect(stats.entries).toBe(1);expect(stats.invoices).toBe(1);
  await page.getByRole('link',{name:/^BILL-/}).first().click();await expect(page.getByRole('table',{name:'Invoice lines',exact:true})).toContainText(data.date);await check(page);expect(errors).toEqual([]);
 });
}
test('Teacher cannot change private school enrolment fee rules',async({page})=>{const data=fixture();await signIn(page,data.teacher);expect((await page.goto('/fee-lifecycle'))?.status()).toBe(403);});
