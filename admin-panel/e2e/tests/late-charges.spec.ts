import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action='prepare',school=0) {
  return JSON.parse(execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/late-charges-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8',env:{...process.env,BILLING_FIXTURE_ACTION:action,BILLING_FIXTURE_SCHOOL:String(school)}}).trim());
}
async function accessible(page:Page) {expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);}
test.describe.configure({mode:'serial',retries:0});
test.use({storageState:{cookies:[],origins:[]}});
for(const width of [390,820,1280]) {
 test(`Finance configures and posts one explicit capped late charge at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(120_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);const errors=collectErrors(page,baseURL!);
  await page.goto(`/fee-late-charges/policy?year_id=${data.year}&currency=ZAR`);
  await page.getByRole('checkbox',{name:'Enable this explicitly configured late-charge rule',exact:true}).check();
  await page.getByRole('combobox',{name:'Original charges covered',exact:true}).selectOption('annual_only');await page.getByRole('combobox',{name:'Gross charge basis',exact:true}).selectOption('flat');
  await page.getByRole('textbox',{name:'Flat gross amount, if selected',exact:true}).fill('0.50');await page.getByRole('textbox',{name:'Maximum gross charge including VAT',exact:true}).fill('0.25');
  await page.getByRole('spinbutton',{name:'Grace days after original due date',exact:true}).fill('1');await page.getByRole('spinbutton',{name:'Days until the new late invoice is due',exact:true}).fill('10');
  await page.getByRole('combobox',{name:'Explicit VAT treatment',exact:true}).selectOption('no_vat');await page.getByRole('textbox',{name:'VAT rate percentage; zero for untaxed or zero-rated charges',exact:true}).fill('0.00');
  await page.getByRole('textbox',{name:'Private reason for this school rule revision',exact:true}).fill('Synthetic explicit original one-off school rule.');await accessible(page);await page.getByRole('button',{name:'Retain this late-charge rule revision',exact:true}).click();await expect(page.getByRole('status')).toContainText('revision retained');await accessible(page);
  await page.goto(`/fee-late-charges?account_id=${data.account}&original_invoice_line_id=${data.late_line}&holder_user_id=${data.parents[0]}`);await page.getByRole('textbox',{name:'Private Finance late-charge reason',exact:true}).fill('Synthetic reviewed original overdue share and separate credit.');await page.getByRole('button',{name:'Review configured one-off late charge',exact:true}).click();await expect(page.getByRole('main')).toContainText('ZAR 0.25');await accessible(page);
  await page.getByRole('checkbox',{name:'I reviewed the original overdue debt, separate credit and exact school rule. Record this one-off invoice once.',exact:true}).check();const button=page.getByRole('button',{name:'Post reviewed one-off late charge',exact:true});const form=button.locator('xpath=ancestor::form');const fields=await form.evaluate(node=>Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string,string>);const action=await form.getAttribute('action');await button.click();await expect(page.getByRole('status')).toContainText('recorded once');expect((await page.request.post(action!,{form:fields})).status()).toBe(200);expect(fixture('stats',data.school)).toEqual({late_charges:1,policy_revisions:1,late_gross_cents:25,healthy:true});await accessible(page);
  fixture('pause',data.school);await page.reload();await expect(page.getByRole('main')).toContainText('Late charges are read-only');expect((await page.request.post(action!,{form:fields})).status()).toBe(403);expect(errors).toEqual([]);
 });
}
test('Teacher cannot open late policy or private original debt',async({page})=>{const data=fixture();await signIn(page,data.teacher);for(const url of ['/fee-late-charges','/fee-late-charges/policy'])expect((await page.goto(url))!.status()).toBe(403);});
