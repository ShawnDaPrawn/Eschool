import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
type Fixture = {children: Record<number,{id:number,name:string}>,guardian:number,token:string,destination:{year:number,room:number,category:number},today:string,start:string};
let fixture: Fixture;
test.beforeAll(() => {
  fixture = JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/transfer-fixture.php');"], {cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'}).trim());
});
for(const width of [390,1280]) {
  test(`staff and named guardian complete signed two-school handoff at ${width}`,async({browser,baseURL})=>{
    test.setTimeout(120000);
    const ac=await browser.newContext({baseURL,viewport:{width,height:1000}}),bc=await browser.newContext({baseURL,viewport:{width,height:1000}}),gc=await browser.newContext({baseURL});
    try {
      const page=await ac.newPage(),receiving=await bc.newPage();
      await signIn(page,accounts.admin);await signIn(receiving,accounts.adminB);
      const errors=collectErrors(page,baseURL!),otherErrors=collectErrors(receiving,baseURL!);
      const child=fixture.children[width];
      await page.goto(`/school-transfers?learner_id=${child.id}&guardian_id=${fixture.guardian}`);
      const prepare=page.getByRole('form',{name:'Prepare a transfer for guardian review',exact:true});
      await prepare.getByRole('combobox',{name:'NavigateEd receiving school',exact:true}).selectOption('2');
      await prepare.locator('input[name="last_attended_on"]').fill(fixture.today);
      await prepare.locator('input[name="starts_on"]').fill(fixture.start);
      await prepare.getByRole('button',{name:'Prepare for guardian review',exact:true}).click();
      await expect(page.getByRole('heading',{name:`${child.name} · Transfer package`,exact:true})).toBeVisible();
      const id=Number(new URL(page.url()).searchParams.get('record'));
      expect(id).toBeGreaterThan(0);
      const headers={Authorization:`Bearer ${fixture.token}`,'X-School-Id':'1','X-Account-Id':String(fixture.guardian),Accept:'application/json'};
      const detail=await gc.request.get(`/api/v2/transfers/consents/${id}`,{headers});
      expect(detail.status()).toBe(200);const packet=(await detail.json()).data;
      expect(packet.can_consent).toBe(true);expect(packet.learner_name).toBe(child.name);
      const consent=await gc.request.post(`/api/v2/transfers/packages/${id}/consent`,{headers,data:{operation_id:crypto.randomUUID(),revision:packet.revision,fingerprint:packet.fingerprint,version:packet.consent_version,confirmation:true}});
      expect(consent.status()).toBe(200);
      await page.reload();await page.getByRole('button',{name:'Release consented transfer',exact:true}).click();
      await expect(page.getByText('Released to receiving school',{exact:false}).first()).toBeVisible();
      await receiving.goto(`/school-transfers?lane=incoming&record=${id}`);
      const accept=receiving.getByRole('form',{name:'Accept this transfer',exact:true});
      await accept.getByRole('combobox',{name:'Receiving school year',exact:true}).selectOption(String(fixture.destination.year));
      await accept.getByRole('combobox',{name:'Receiving homeroom',exact:true}).selectOption(String(fixture.destination.room));
      await accept.getByRole('combobox',{name:'Learner category',exact:true}).selectOption(String(fixture.destination.category));
      await accept.getByRole('checkbox').check();
      await accept.getByRole('button',{name:'Accept and place learner',exact:true}).click();
      await expect(receiving.getByText('Accepted by receiving school',{exact:false}).first()).toBeVisible();
      const archivePath=await receiving.getByRole('link',{name:'Download signed transfer archive',exact:true}).getAttribute('href');expect(archivePath).toBeTruthy();const archive=await bc.request.get(archivePath!);
      expect(archive.status()).toBe(200);expect(archive.headers()['cache-control']).toContain('no-store');expect(archive.headers()['content-type']).toContain('application/zip');expect((await archive.body()).subarray(0,2).toString()).toBe('PK');
      for(const target of [page,receiving]) {
        expect(await seriousViolations(target)).toEqual([]);expect(await horizontalOverflow(target)).toBeLessThanOrEqual(1);
        const ids=await target.locator('[id]').evaluateAll(nodes=>nodes.map(n=>n.id));expect(new Set(ids).size).toBe(ids.length);
      }
      expect(errors).toEqual([]);expect(otherErrors).toEqual([]);
    } finally { await ac.close();await bc.close();await gc.close(); }
  });
}
test('external school remains selectable when another NavigateEd school is active',async({page})=>{
  await signIn(page,accounts.admin);const child=fixture.children[999];
  await page.goto(`/school-transfers?learner_id=${child.id}&guardian_id=${fixture.guardian}`);
  const form=page.getByRole('form',{name:'Prepare a transfer for guardian review',exact:true});
  await expect(form.getByRole('combobox',{name:'NavigateEd receiving school',exact:true})).toHaveValue('');
  await form.getByRole('textbox',{name:'External receiving school',exact:true}).fill('Synthetic external high');
  await form.locator('input[name="last_attended_on"]').fill(fixture.today);
  await form.locator('input[name="starts_on"]').fill(fixture.start);
  await form.getByRole('button',{name:'Prepare for guardian review',exact:true}).click();
  const id=Number(new URL(page.url()).searchParams.get('record'));expect(id).toBeGreaterThan(0);
  await expect(page.getByRole('region',{name:`${child.name} · Transfer package`,exact:true}).getByText('Synthetic external high',{exact:true})).toBeVisible();
});
test('ordinary teacher cannot open transfer packages',async({page})=>{
  await signIn(page,accounts.teacher);const response=await page.goto('/school-transfers');expect(response?.status()).toBe(403);await expect(page.getByText('Package fingerprint',{exact:true})).toHaveCount(0);
});
