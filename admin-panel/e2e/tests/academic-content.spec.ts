import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
function php(code: string) {
  return execFileSync('php',['artisan','tinker','--execute',code],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'});
}
const run = Date.now().toString(36);
test.beforeAll(() => { php("require base_path('e2e/support/content-fixture.php');"); });
for (const width of [1024,1280]) {
  test(`private teacher upload, review, immutable version and withdrawal at ${width}px`,async ({ page,baseURL }) => {
    await page.setViewportSize({width,height:900});
    await signIn(page,accounts.teacher);
    const errors=collectErrors(page,baseURL!);
    await page.goto('/academic-content');
    await page.getByRole('combobox',{name:'Teaching context',exact:true}).selectOption({label:'Content rehearsal subject · Grade 5 · Content rehearsal group'});
    await page.getByRole('button',{name:'Open teaching context',exact:true}).click();
    const title=`Fractions ${width} ${run}`;
    let form=page.getByRole('form',{name:'Upload private learning material',exact:true});
    await form.getByRole('textbox',{name:'Content title',exact:true}).fill(title);
    await form.getByLabel('Private source file').setInputFiles({name:'fractions.md',mimeType:'text/markdown',buffer:Buffer.from('# Fractions\n\nOne half is two quarters.')});
    await form.getByRole('button',{name:'Upload for conversion',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Content queued'})).toBeVisible();
    await expect(page.getByRole('button',{name:'Publish reviewed content',exact:true})).toHaveCount(0);
    php(`$contentTitle='${title}'; require base_path('e2e/support/content-convert-fixture.php');`);
    await page.getByRole('link',{name:'Refresh content status',exact:true}).click();
    form=page.getByRole('form',{name:'Edit content draft',exact:true});
    await form.getByRole('textbox',{name:'Markdown draft',exact:true}).fill('# Fractions\n\n**Half** is two quarters.');
    await form.getByRole('button',{name:'Save reviewed draft',exact:true}).click();
    await page.getByRole('link',{name:'Preview publication',exact:true}).click();
    await expect(page.getByRole('heading',{name:'Review content publication',exact:true})).toBeVisible();
    await page.getByRole('checkbox',{name:'I reviewed the converted text, topic tags and published teaching groups.',exact:true}).check();
    await page.getByRole('button',{name:'Publish reviewed content',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Reviewed content published'})).toBeVisible();
    await page.getByRole('button',{name:'Save reviewed draft',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Content draft saved'})).toBeVisible();
    await page.getByText('Private version history',{exact:true}).click();
    await expect(page.getByRole('link',{name:new RegExp(`Version 1 · ${title} · published`,'i')})).toBeVisible();
    await page.getByText('Withdraw published material',{exact:true}).click();
    form=page.getByRole('form',{name:'Withdraw published material',exact:true});
    await form.getByRole('textbox',{name:'Withdrawal reason',exact:true}).fill('Synthetic publication withdrawn after review');
    await form.getByRole('button',{name:'Withdraw from learner libraries',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Content withdrawn'})).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
test('another teacher cannot open an authored private item',async ({ page }) => {
  const id=php("app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1,fn () => print(\\App\\Models\\Academics\\ContentItem::latest('id')->value('id')));").trim();
  await signIn(page,accounts.teacher2);
  const response=await page.goto(`/academic-content?item_id=${Number(id)}`);
  expect(response?.status()).toBe(404);
});
