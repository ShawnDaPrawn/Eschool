import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action='prepare',school=0) {
  return JSON.parse(execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/wallet-payments-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8',env:{...process.env,BILLING_FIXTURE_ACTION:action,BILLING_FIXTURE_SCHOOL:String(school)}}).trim());
}
async function accessible(page:Page) {
 expect(await seriousViolations(page)).toEqual([]);
 expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
}
async function vendorSignIn(page:Page,data:any) {
 await page.goto('/vendor-counter/sign-in');
 await page.getByRole('textbox',{name:'School code',exact:true}).fill(data.school_slug);
 await page.getByRole('textbox',{name:'Email or handle',exact:true}).fill(data.operator_email);
 await page.locator('#vendor-login-password').fill(data.operator_password);
 await page.getByRole('button',{name:'Sign in to vendor counter',exact:true}).click();
 await expect(page.getByRole('heading',{name:'Vendor order queue',exact:true})).toBeVisible();
}
test.describe.configure({mode:'serial',retries:0});
test.use({storageState:{cookies:[],origins:[]}});
for(const width of [390,820,1280]) {
 test(`Vendor confirms original wallet QR and current SUN photo once at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(180_000);const data=fixture();await page.setViewportSize({width,height:1100});await vendorSignIn(page,data);const errors=collectErrors(page,baseURL!);
  const index=`/vendor-counter/counters/${data.vendor}/payments`;await page.goto(index);await accessible(page);
  await page.getByRole('textbox',{name:'Current wallet payment QR',exact:true}).fill(data.payment_qr);
  await page.getByRole('button',{name:'Review wallet QR payment',exact:true}).click();
  await expect(page.getByRole('heading',{name:'Review this counter payment',exact:true})).toBeVisible();
  await expect(page.getByRole('main')).toContainText('Exact wallet total: ZAR 12.50');await accessible(page);
  const confirm=page.locator('[data-wallet-payment-confirm]');await confirm.getByRole('checkbox').check();
  const fields=await confirm.evaluate(node=>Object.fromEntries(new FormData(node as HTMLFormElement).entries()) as Record<string,string>);const action=(await confirm.getAttribute('action'))!;
  await confirm.getByRole('button',{name:'Confirm wallet payment and handover',exact:true}).click();
  await expect(page.getByRole('main')).toContainText('Payment recorded. Hand over the listed goods once.');
  const replay=await page.request.post(action,{form:fields});expect(replay.status()).toBe(200);expect(await replay.text()).toContain('do not hand over goods again');
  expect(fixture('stats',data.school)).toEqual({payments:1,wallet_cents:3750,on_hand:5,healthy:true});
  await page.goto(index);await page.locator(`#tap-product-${data.product}`).fill('1');
  await page.locator('#payment-tap-reader').fill(data.payment_reader);await page.locator('#payment-tap-uri').fill(data.tap_uri);
  await page.getByRole('button',{name:'Review card photo and exact payment',exact:true}).click();
  await expect(page.getByRole('img',{name:'Current cardholder photo',exact:true})).toBeVisible();
  await expect(page.getByRole('main')).toContainText('Exact wallet total: ZAR 12.50');await accessible(page);
  expect(fixture('stats',data.school).payments).toBe(1);
  await page.locator('[data-wallet-payment-confirm]').getByRole('checkbox').check();
  await page.getByRole('button',{name:'Confirm wallet payment and handover',exact:true}).click();
  await expect(page.getByRole('main')).toContainText('Payment recorded. Hand over the listed goods once.');
  await expect(page.getByRole('img',{name:'Current cardholder photo',exact:true})).toHaveCount(0);
  expect(fixture('stats',data.school)).toEqual({payments:2,wallet_cents:2500,on_hand:4,healthy:true});
  fixture('pause',data.school);await page.goto(index);await expect(page.getByRole('main')).toContainText('Wallet payments are paused');await expect(page.getByRole('button',{name:'Review wallet QR payment',exact:true})).toHaveCount(0);await accessible(page);expect(errors).toEqual([]);
 });
}
test('Finance pairs and revokes a purpose-bound reader without retaining its credential',async({page})=>{
 test.setTimeout(120_000);const data=fixture();await signIn(page,data.finance);await page.goto('/vendor-payment-readers');
 await page.getByRole('combobox',{name:'Assigned Vendor counter',exact:true}).selectOption(String(data.vendor));
 await page.getByRole('textbox',{name:'Reader name',exact:true}).fill('Reviewed synthetic counter reader');
 await page.getByRole('spinbutton',{name:'Reader validity in days',exact:true}).fill('1');
 await page.getByRole('button',{name:'Register and pair payment reader',exact:true}).click();
 await expect(page.locator('#payment-reader-credential')).toHaveValue(/^NED1\./);await accessible(page);
 await page.locator('li').filter({hasText:'Reviewed synthetic counter reader'}).getByRole('button',{name:'Revoke this payment reader',exact:true}).click();
 await expect(page.locator('#payment-reader-credential')).toHaveCount(0);await expect(page.locator('li').filter({hasText:'Reviewed synthetic counter reader'})).toContainText('revoked');await accessible(page);
});
test('Teacher cannot open private reader controls or the dedicated payment counter',async({page})=>{
 test.setTimeout(120_000);const data=fixture();await signIn(page,data.teacher);
 for(const url of ['/vendor-payment-readers',`/vendor-counter/counters/${data.vendor}/payments`])expect((await page.goto(url))!.status()).toBe(403);
});
