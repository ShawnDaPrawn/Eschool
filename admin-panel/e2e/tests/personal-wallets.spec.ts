import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action = 'prepare', school = 0) {
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/personal-wallets-fixture.php'));"], {
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
  await button.click();await expect(page.getByRole('heading',{name:'Wallets',exact:true})).toBeVisible();expect((await page.request.post(action!,{form:fields})).status()).toBe(200);
}
test.describe.configure({mode:'serial',retries:0});test.use({storageState:{cookies:[],origins:[]}});


for(const width of [390,820,1280]){
 test(`Finance configures independent wallet controls and reads zero wallets at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(150_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);const errors=collectErrors(page,baseURL!);
  const response=await page.goto('/wallet-settings');expect(response?.headers()['cache-control']).toContain('no-store');await page.getByRole('textbox',{name:'Wallet currency code',exact:true}).fill('ZAR');await page.getByRole('textbox',{name:'Daily wallet spending cap',exact:true}).fill('50.00');await page.getByRole('combobox',{name:'Require guardian controls before learner spending',exact:true}).selectOption('1');await check(page);await submitOnceAndReplay(page,'Save wallet spending rules');
  fixture('issue',data.school);await page.reload();await expect(page.getByRole('table',{name:'Personal wallet balances',exact:true})).toBeVisible();await expect(page.getByRole('main')).toContainText('ZAR 10.00');await page.getByRole('link',{name:data.learners[0].name,exact:true}).click();await expect(page.getByRole('table',{name:'Wallet statement',exact:true})).toBeVisible();await expect(page.getByRole('main')).toContainText('ZAR 0.00');await check(page);
  fixture('disable',data.school);await page.goto('/wallet-settings');await expect(page.getByRole('main')).toContainText('Wallet history is read-only while wallets are disabled.');await expect(page.getByRole('button',{name:'Save wallet spending rules',exact:true})).toBeDisabled();const stats=fixture('stats',data.school);expect(stats.wallets).toBe(2);expect(stats.limits).toBe(1);expect(stats.wallet_entries).toBe(0);expect(stats.approved).toBe(true);expect(stats.phase6_cents).toBe(0);await check(page);expect(errors).toEqual([]);
 });
}
test('Teacher cannot configure school wallets or read Finance wallet lists',async({page})=>{const data=fixture();await signIn(page,data.teacher);for(const url of ['/wallet-settings','/wallet-settings/9999/statement'])expect((await page.goto(url))?.status()).toBe(403);});


test('Scoped CSP runs trusted workbench scripts and blocks injected inline code', async ({ page }) => {
  const data = fixture();
  await page.addInitScript(() => {
    (window as any).__cspViolations = [];
    document.addEventListener('securitypolicyviolation', event => {
      (window as any).__cspViolations.push(event.effectiveDirective);
    });
  });
  await signIn(page, data.finance);
  const response = await page.goto('/wallet-settings');
  expect(response!.status()).toBe(200);
  const scriptPolicy = response!.headers()['content-security-policy'].split(';').find(value => value.trim().startsWith('script-src'))!;
  expect(scriptPolicy).toContain("'nonce-");
  expect(scriptPolicy).not.toContain("'unsafe-inline'");
  expect(await page.evaluate(() => Boolean((window as any).panelShell))).toBe(true);
  await page.evaluate(() => {
    (window as any).__injectedScriptRan = false;
    const script = document.createElement('script');
    script.textContent = 'window.__injectedScriptRan = true';
    document.body.append(script);
  });
  await expect.poll(() => page.evaluate(() => (window as any).__cspViolations)).toContain('script-src-elem');
  expect(await page.evaluate(() => (window as any).__injectedScriptRan)).toBe(false);
});
