import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/fee-refunds-fixture.php'));"], {
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
  const button=page.getByRole('button',{name:label,exact:true});const form=button.locator('xpath=ancestor::form');
  const fields=await form.evaluate(node=>Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string,string>);const action=await form.getAttribute('action');
  await button.click();await expect(page.getByRole('heading',{name:'Fee refunds',exact:true})).toBeVisible();expect((await page.request.post(action!,{form:fields})).status()).toBe(200);
}
test.describe.configure({mode:'serial',retries:0});test.use({storageState:{cookies:[],origins:[]}});

for(const width of [390,820,1280]){
 test(`Finance reserves verifies and cancels original receipt refunds at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(180_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);const errors=collectErrors(page,baseURL!);
  const response=await page.goto('/fee-refunds');expect(response?.headers()['cache-control']).toContain('no-store');
  await page.getByRole('combobox',{name:'Cash refunds permitted',exact:true}).selectOption('1');await page.getByRole('combobox',{name:'EFT refunds permitted',exact:true}).selectOption('1');await page.getByRole('combobox',{name:'Separate Finance approval',exact:true}).selectOption('0');await check(page);await submitOnceAndReplay(page,'Save manual refund policy');
  async function prepare(amount:string){await page.goto('/fee-refunds/new?payment_id='+data.payment);await page.getByRole('textbox',{name:'Refund amount',exact:true}).fill(amount);await page.getByRole('combobox',{name:'Refund method',exact:true}).selectOption('cash');await page.getByRole('textbox',{name:'Private Finance explanation',exact:true}).fill('PRIVATE-SYNTHETIC-REFUND');await page.getByRole('button',{name:'Review receipt refund',exact:true}).click();await page.getByRole('checkbox',{name:'I reviewed the original holder, receipt credit, method and refund policy.',exact:true}).check();await submitOnceAndReplay(page,'Approve and reserve refund');}
  await prepare('4.00');await check(page);await page.getByRole('textbox',{name:'Unique payout reference',exact:true}).fill('SYNTHETIC-REFUND-'+data.school);await page.getByRole('textbox',{name:'Private payout verification',exact:true}).fill('PRIVATE-SYNTHETIC-PAYOUT');await page.getByRole('checkbox',{name:'I verified payment to the original receipt holder.',exact:true}).check();await page.getByRole('checkbox',{name:'I confirm this outside payout occurred and has not already been recorded.',exact:true}).check();await submitOnceAndReplay(page,'Record verified refund payout');await expect(page.getByRole('main')).toContainText('paid');await check(page);
  await prepare('1.00');await page.getByRole('textbox',{name:'Private cancellation reason',exact:true}).fill('Original holder cancelled unpaid reservation');await page.getByRole('checkbox',{name:'I confirm no outside payout occurred for this reservation.',exact:true}).check();await submitOnceAndReplay(page,'Cancel refund reservation');await expect(page.getByRole('main')).toContainText('cancelled');await check(page);
  const stats=fixture('stats',data.school);expect(stats.refunds).toBe(2);expect(stats.paid_cents).toBe(400);expect(stats.cancelled).toBe(1);expect(stats.available_cents).toBe(500);expect(stats.healthy).toBe(true);expect(errors).toEqual([]);
 });
}
test('Teacher cannot access private refund evidence',async({page})=>{const data=fixture();await signIn(page,data.teacher);for(const url of ['/fee-refunds','/fee-refunds/new','/fee-refunds/99999']){expect((await page.goto(url))?.status()).toBe(403);await expect(page.getByRole('main')).not.toContainText('PRIVATE-SYNTHETIC-REFUND');}});
