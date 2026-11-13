import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test, type Page } from '@playwright/test';
import { signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
function fixture(action='prepare',school=0){return JSON.parse(execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/tickets-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8',env:{...process.env,BILLING_FIXTURE_ACTION:action,BILLING_FIXTURE_SCHOOL:String(school)}}).trim());}
async function check(page:Page){expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);expect(await page.locator('[id]').evaluateAll(nodes=>nodes.map(n=>n.id).filter((id,i,all)=>all.indexOf(id)!==i))).toEqual([]);}
test.describe.configure({mode:'serial',retries:0});test.use({storageState:{cookies:[],origins:[]}});
for(const width of [390,820,1280]){
 test(`Vendor admits original general and named tickets once at ${width}px`,async({page,baseURL})=>{
  test.setTimeout(300_000);const data=fixture();await page.setViewportSize({width,height:1000});
  expect((await page.goto('/vendor-counter/sign-in'))?.status()).toBe(200);await page.getByRole('textbox',{name:'School code',exact:true}).fill(data.school_slug);await page.getByRole('textbox',{name:'Email or handle',exact:true}).fill(data.operator_email);await page.locator('#vendor-login-password').fill(data.operator_password);await page.getByRole('button',{name:'Sign in to vendor counter',exact:true}).click();await expect(page).toHaveURL(/\/vendor-counter$/);
  const errors=collectErrors(page,baseURL!);const url=`/vendor-counter/counters/${data.vendor}/tickets`;expect((await page.goto(url))?.status()).toBe(200);await check(page);const before=fixture('stats',data.school);
  for(const [index,ticket] of data.tickets.entries()){
   if(index>0)await page.goto(url);const form=page.getByRole('form',{name:'Review admission before entry',exact:true});await form.getByRole('textbox',{name:index===0?'Current ticket number':'Scanned admission QR',exact:true}).fill(index===0?ticket.ticket_number:ticket.qr);await Promise.all([page.waitForEvent('domcontentloaded'),form.getByRole('button',{name:'Review admission before entry',exact:true}).click()]);await expect(page.getByRole('heading',{name:'Synthetic original school event',exact:true})).toBeVisible();await check(page);
   const confirm=page.getByRole('form',{name:'Confirm one actual admission',exact:true});await confirm.getByRole('checkbox').check();const replay=await confirm.evaluate(f=>({url:(f as HTMLFormElement).action,form:Object.fromEntries(new FormData(f as HTMLFormElement).entries())}));if(index===0){
    // Server commits but the browser loses its response: recover the original UUID.
    await page.route('**/tickets/redeem',async route=>{await route.fetch();await route.abort('failed');},{times:1});
    // Wait for Chromium's failed-document navigation before returning to the recovery GET.
    await Promise.all([page.waitForEvent('domcontentloaded'),confirm.getByRole('button',{name:'Confirm one actual admission',exact:true}).click()]);
    await page.goto(url);await expect(page.getByRole('heading',{name:'Admission outcome unconfirmed',exact:true})).toBeVisible();
    await expect(page.getByRole('button',{name:'Review admission before entry',exact:true})).toBeDisabled();
    await Promise.all([page.waitForEvent('domcontentloaded'),page.getByRole('button',{name:'Check original admission outcome',exact:true}).click()]);
    await expect(page.getByText('Admission was already recorded. Check the original record; do not admit this ticket again.',{exact:true})).toBeVisible();
   }else{
    await Promise.all([page.waitForEvent('domcontentloaded'),confirm.getByRole('button',{name:'Confirm one actual admission',exact:true}).click()]);await expect(page.getByText('One admission recorded. Admit this ticket once.',{exact:true})).toBeVisible();
   }
   await expect(page.getByRole('button',{name:'Confirm one actual admission',exact:true})).toHaveCount(0);const reply=await page.request.post(replay.url,{form:replay.form as Record<string,string>});expect(reply.status()).toBe(200);expect(await reply.text()).toContain('do not admit this ticket again');
  }
  expect(fixture('stats',data.school)).toMatchObject({tickets:2,redeemed:2,transferred:0,types:2,entries:before.entries,healthy:true});await check(page);await page.screenshot({path:test.info().outputPath('original-admission-recorded.png'),fullPage:true});expect(errors).toEqual([]);fixture('revoke',data.school);expect((await page.goto(url))?.status()).toBe(403);expect(await page.locator('body').textContent()).not.toContain(data.tickets[0].ticket_number);
 });
}
for(const width of [390,1280]){
 test(`Finance revises issued admission capacity and confirms event cancellation at ${width}px`,async({page})=>{
  test.setTimeout(300_000);const data=fixture();await page.setViewportSize({width,height:1000});await signIn(page,data.finance);
  const url='/ticket-events?event_id='+data.event;
  expect((await page.goto(url))?.status()).toBe(200);await expect(page.getByRole('heading',{name:'Admission event details',exact:true})).toBeVisible();await check(page);
  await page.getByRole('link',{name:'Edit sale and access windows',exact:true}).click();await expect(page.getByRole('form',{name:'Retain event sale and admission windows',exact:true})).toBeVisible();await check(page);
  await page.getByRole('link',{name:'Cancel',exact:true}).click();
  await page.getByRole('link',{name:/^Admission type [0-9]+$/}).first().click();
  await page.getByRole('link',{name:'Edit admission type',exact:true}).click();
  const type=page.getByRole('form',{name:'Retain admission capacity and transfer rule',exact:true});
  await expect(type.getByRole('combobox',{name:'Original admission product',exact:true})).toHaveCount(0);
  await type.getByRole('spinbutton',{name:'Explicit admission capacity',exact:true}).fill('2');
  await type.getByRole('textbox',{name:'Private reason for this admission revision',exact:true}).fill('Reviewed original issued capacity for this synthetic event.');
  await Promise.all([page.waitForEvent('domcontentloaded'),type.getByRole('button',{name:'Retain admission capacity and transfer rule',exact:true}).click()]);
  await expect(page.getByRole('heading',{name:'Retained admission type',exact:true})).toBeVisible();await check(page);
  await page.getByRole('link',{name:'Cancel admission event',exact:true}).click();
  const cancel=page.getByRole('form',{name:'Cancel admission event',exact:true});await cancel.getByRole('textbox',{name:'Private reason for this event revision',exact:true}).fill('School confirmed cancellation with separate retained finance follow-up.');await cancel.getByRole('checkbox').check();
  await Promise.all([page.waitForEvent('domcontentloaded'),cancel.getByRole('button',{name:'Retain cancellation',exact:true}).click()]);
  await expect(page.getByText('This admission event is cancelled and cannot reopen.',{exact:true})).toBeVisible();await check(page);
  await page.screenshot({path:test.info().outputPath('retained-admission-cancellation.png'),fullPage:true});
  expect(fixture('stats',data.school)).toMatchObject({tickets:2,redeemed:0,healthy:true});
 });
}
test('Finance reviews retained read-only event policy',async({page})=>{const data=fixture();await signIn(page,data.finance);expect((await page.goto('/ticket-events?event_id='+data.event+'&section=event'))?.status()).toBe(200);await expect(page.getByRole('form',{name:'Retain event sale and admission windows',exact:true})).toBeVisible();fixture('pause',data.school);await page.reload();await expect(page.getByText('Ticketing is read-only. Keep original event, ticket and redemption history.',{exact:true})).toBeVisible();await expect(page.getByRole('button',{name:'Retain event sale and admission windows',exact:true})).toBeDisabled();});
