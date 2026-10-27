import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-discounts-fixture.php'));"], {
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
 test(`Finance approves and posts a private discount once at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(180_000);await page.setViewportSize({width,height:1000});const data=fixture('prepare');const observed=collectErrors(page,baseURL!);await signIn(page,data.finance);
  await page.goto('/fee-discounts/new');await expect(page.getByRole('heading',{name:'Discounts and bursaries',exact:true})).toBeVisible();
  await page.getByLabel('Private Finance rule name',{exact:false}).fill('Synthetic private bursary');
  await page.getByLabel('Discount type',{exact:false}).selectOption('bursary');await page.getByLabel('Named learner',{exact:false}).selectOption(String(data.learners[0].id));
  await page.getByLabel('Charge source',{exact:false}).selectOption('annual_tuition');await page.getByLabel('Currency',{exact:false}).fill('ZAR');
  await page.getByLabel('Valid from',{exact:false}).fill(data.date.slice(0,4)+'-01-01');await page.getByLabel('Valid until',{exact:false}).fill(data.date.slice(0,4)+'-12-31');
  await page.getByLabel('Reduction calculation',{exact:false}).selectOption('percent');await page.getByLabel('Percentage or fixed money amount',{exact:false}).fill('10');
  await page.getByLabel('Other discounts on the same charges',{exact:false}).selectOption('exclusive');await page.getByLabel('Separate Finance approval',{exact:false}).selectOption('0');
  await page.getByRole('button',{name:'Save discount draft',exact:true}).click();await expect(page.getByText('Discount draft saved. Review before approval.',{exact:true})).toBeVisible();
  await page.getByLabel('Rule action',{exact:false}).selectOption('approve');await page.getByLabel('I reviewed this exact rule and approve the selected action.',{exact:false}).check();
  await page.getByRole('button',{name:'Confirm rule action',exact:true}).click();await expect(page.getByText('Discount rule updated.',{exact:true})).toBeVisible();
  await page.goto('/fee-discounts/apply?account_id='+data.account);
  await page.getByLabel('Approved discount rule',{exact:false}).selectOption({label:'Synthetic private bursary'});await page.getByLabel('Learner',{exact:false}).selectOption(String(data.learners[0].id));
  await page.getByLabel('Private Finance explanation',{exact:false}).fill('PRIVATE-SYNTHETIC-DISCOUNT-EVIDENCE');await page.getByRole('button',{name:'Review fee reduction',exact:true}).click();
  await expect(page).toHaveURL(/\/fee-discounts\/runs\/\d+$/);await expect(page.getByRole('table',{name:'Original-holder credits',exact:true})).toBeVisible();
  await page.getByLabel('I reviewed these exact original-holder credits and confirm the next step.',{exact:false}).check();await page.getByRole('button',{name:'Approve fee reduction',exact:true}).click();
  await page.getByLabel('I reviewed these exact original-holder credits and confirm the next step.',{exact:false}).check();await page.getByRole('button',{name:'Post approved reduction',exact:true}).click();
  await expect(page.getByText('Approved fee reduction posted once.',{exact:true})).toBeVisible();await expect(page.getByRole('heading',{name:'Retained credit notes',exact:true})).toBeVisible();
  await page.reload();const stats=fixture('stats',data.school);expect(stats.posted_discounts).toBe(1);expect(stats.discount_rules).toBe(1);expect(stats.credit_notes).toBe(2);
  await check(page);expect(observed).toEqual([]);
 });
}
test('Teacher cannot access private discount eligibility or approval',async({page})=>{const data=fixture('prepare');await signIn(page,data.teacher);for(const url of ['/fee-discounts','/fee-discounts/new','/fee-discounts/apply']){expect((await page.goto(url))?.status()).toBe(403);await expect(page.locator('body')).not.toContainText('PRIVATE-SYNTHETIC-DISCOUNT-EVIDENCE');}});
