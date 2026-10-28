import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action='prepare',school=0) {
  return JSON.parse(execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/payer-corrections-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8',env:{...process.env,BILLING_FIXTURE_ACTION:action,BILLING_FIXTURE_SCHOOL:String(school)}}).trim());
}
async function accessible(page:Page) {expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);}
test.describe.configure({mode:'serial',retries:0});
test.use({storageState:{cookies:[],origins:[]}});
for(const width of [390,820,1280]) {
 test(`Finance reviews one unpaid payer correction without moving paid money at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(120_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);const errors=collectErrors(page,baseURL!);const before=fixture('stats',data.school);
  const response=await page.goto(`/fee-payer-corrections?account_id=${data.account}&invoice_line_id=${data.correction_line}&holder_user_id=${data.parents[0]}`);expect(response!.headers()['cache-control']).toContain('no-store');
  await page.getByRole('combobox',{name:'Intended configured payer',exact:true}).selectOption(String(data.recipient));
  await page.getByRole('textbox',{name:'Original unpaid amount to correct',exact:true}).fill('1.00');
  await page.getByRole('textbox',{name:'Private Finance correction reason',exact:true}).fill('Synthetic reviewed change to the original unpaid payer.');await accessible(page);
  await page.getByRole('button',{name:'Review original unpaid correction',exact:true}).click();await expect(page.getByRole('main')).toContainText('ZAR 1.00');await accessible(page);
  await page.getByRole('checkbox',{name:'I reviewed the original unpaid debt, intended payer and exact preserved tax. Record this correction once.',exact:true}).check();
  const button=page.getByRole('button',{name:'Post original unpaid payer correction',exact:true});const form=button.locator('xpath=ancestor::form');const fields=await form.evaluate(node=>Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string,string>);const action=await form.getAttribute('action');await button.click();await expect(page.getByRole('status')).toContainText('recorded once');await accessible(page);
  expect((await page.request.post(action!,{form:fields})).status()).toBe(200);const after=fixture('stats',data.school);expect(after.corrections).toBe(1);expect(after.entries).toBe(before.entries+2);for(const key of ['payments','allocations','credit_cents','receivable_cents','income_cents','bank_cents','releases'])expect(after[key]).toEqual(before[key]);expect(errors).toEqual([]);
 });
}
test('Teacher cannot open private payer correction history',async({page})=>{const data=fixture();await signIn(page,data.teacher);expect((await page.goto('/fee-payer-corrections'))!.status()).toBe(403);await expect(page.locator('body')).not.toContainText('Synthetic receipt family');});
