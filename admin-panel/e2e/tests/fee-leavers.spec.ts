import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-leavers-fixture.php'));"], {
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



for(const width of [390,820,1280]){
 test(`Finance reviews original payer leaver credits and posts once at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(180_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);const errors=collectErrors(page,baseURL!);
  const response=await page.goto('/fee-leavers');expect(response?.headers()['cache-control']).toContain('no-store');
  await page.getByRole('combobox',{name:'Departure rule for invoices without an original saved policy',exact:true}).selectOption('daily');await page.getByRole('combobox',{name:'Existing fee reductions on departure',exact:true}).selectOption('retain_full');await page.getByRole('combobox',{name:'Separate Finance approval',exact:true}).selectOption('0');await check(page);await submitOnceAndReplay(page,'Save leaver credit policy');
  await page.goto('/fee-leavers/new?invoice_id='+data.invoice);await page.getByRole('combobox',{name:'Invoiced learner',exact:true}).selectOption(String(data.learners[0].id));await page.getByRole('textbox',{name:'Private Finance explanation',exact:true}).fill('PRIVATE-SYNTHETIC-LEAVER-EVIDENCE');await page.getByRole('button',{name:'Review leaver credit',exact:true}).click();
  await expect(page.getByRole('table',{name:'Original-holder leaver credits',exact:true})).toBeVisible();await expect(page.getByRole('main')).toContainText(data.departure);await check(page);
  await page.getByRole('checkbox',{name:'I reviewed the departure, original policy and each payer credit and confirm the next step.',exact:true}).check();await page.getByRole('button',{name:'Approve leaver credit',exact:true}).click();
  await page.getByRole('checkbox',{name:'I reviewed the departure, original policy and each payer credit and confirm the next step.',exact:true}).check();await submitOnceAndReplay(page,'Post approved leaver credit');
  const stats=fixture('stats',data.school);expect(stats.leaver_runs).toBe(1);expect(stats.credit_notes).toBe(2);expect(stats.credited_cents).toBe(data.expected_credit_cents);expect(stats.original_invoice_cents).toBe(2002);expect(stats.receivable_cents).toBe(2002-data.expected_credit_cents);expect(stats.inactive_learners).toBe(2);await check(page);expect(errors).toEqual([]);
 });
}
test('Teacher cannot access leaver rules or private departure evidence',async({page})=>{const data=fixture();await signIn(page,data.teacher);for(const url of ['/fee-leavers','/fee-leavers/new','/fee-leavers/99999']){expect((await page.goto(url))?.status()).toBe(403);await expect(page.getByRole('main')).not.toContainText('PRIVATE-SYNTHETIC-LEAVER-EVIDENCE');}});
