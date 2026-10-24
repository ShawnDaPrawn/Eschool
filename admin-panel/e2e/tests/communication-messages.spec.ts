import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
const author = 'message.author@demo.navigateed.test';
const run = Date.now().toString(36);
function php(code: string) { return execFileSync('php',['artisan','tinker','--execute',code],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'}); }
test.beforeAll(() => php("require base_path('e2e/support/message-fixture.php');"));
for (const width of [1024,1280]) {
  test(`draft preview approval publication and withdrawal at ${width}px`, async ({ page,baseURL }) => {
    await page.setViewportSize({width,height:900});
    await page.context().clearCookies();
    await signIn(page,author);
    const errors=collectErrors(page,baseURL!);
    await page.goto('/communication-centre');
    let form=page.getByRole('form',{name:'Edit private message',exact:true});
    const title=`Private message ${width} ${run}`;
    await form.getByRole('textbox',{name:'Message subject',exact:true}).fill(title);
    await form.getByRole('combobox',{name:'Audience scope',exact:true}).selectOption({label:'Group · Message rehearsal group'});
    await form.getByRole('combobox',{name:'Audience people',exact:true}).selectOption('1');
    await form.getByRole('textbox',{name:'Message Markdown',exact:true}).fill('# Family information\n\nPlease read this **private** message.');
    await form.getByRole('checkbox',{name:'In app',exact:true}).uncheck();
    await form.getByRole('button',{name:'Save private message',exact:true}).click();
    form=page.getByRole('form',{name:'Edit private message',exact:true});
    await expect(form.getByRole('textbox',{name:'Message subject',exact:true})).toHaveValue(title);
    await expect(form.getByRole('textbox',{name:'Message Markdown',exact:true})).toHaveValue('# Family information\n\nPlease read this **private** message.');
    await expect(form.getByRole('checkbox',{name:'In app',exact:true})).not.toBeChecked();
    await expect(page.getByRole('status').filter({hasText:'Save your changes'})).toBeVisible();
    await form.getByRole('checkbox',{name:'In app',exact:true}).check();
    await form.getByRole('button',{name:'Save private message',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Message draft saved'})).toBeVisible();
    const url=page.url(), id=Number(new URL(url).searchParams.get('message_id'));
    await page.getByRole('link',{name:'Preview saved message',exact:true}).click();
    await expect(page.getByRole('heading',{name:'Audience and delivery preview',exact:true})).toBeVisible();
    // Unsaved edits cannot submit the old server draft by accident.
    form=page.getByRole('form',{name:'Edit private message',exact:true});
    await form.getByRole('textbox',{name:'Message subject',exact:true}).fill(`${title} edited`);
    await expect(page.getByRole('button',{name:'Submit previewed message',exact:true})).toBeDisabled();
    await expect(page.getByRole('status').filter({hasText:'Save your changes'})).toBeVisible();
    await form.getByRole('button',{name:'Save private message',exact:true}).click();
    await page.getByRole('link',{name:'Preview saved message',exact:true}).click();
    await page.getByRole('button',{name:'Submit previewed message',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Message submitted'})).toBeVisible();
    await page.context().clearCookies();
    await signIn(page,accounts.admin);
    await page.goto(`/communication-centre?message_id=${id}&preview_message=1`);
    await page.getByText('Message author rules',{exact:true}).click();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    form=page.getByRole('form',{name:'Decide message approval',exact:true});
    await form.getByRole('textbox',{name:'Review reason',exact:true}).fill('Reviewed the synthetic audience and private message.');
    await form.getByRole('button',{name:'Approve message',exact:true}).click();
    php(`app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1,fn () => app(\\App\\Services\\Communication\\MessageDelivery::class)->process(${id}));`);
    await page.context().clearCookies();
    await signIn(page,author);
    await page.goto(`/communication-centre?message_id=${id}`);
    await expect(page.getByText('Recipients at publication',{exact:false})).toBeVisible();
    await expect(page.getByRole('form',{name:'Edit private message',exact:true})).toHaveCount(0);
    await page.getByText('Withdraw message',{exact:true}).first().click();
    form=page.getByRole('form',{name:'Withdraw message',exact:true});
    await form.getByRole('textbox',{name:'Withdrawal reason',exact:true}).fill('Synthetic message withdrawn after browser review');
    await form.getByRole('button',{name:'Withdraw message',exact:true}).click();
    await expect(page.getByRole('status').filter({hasText:'Message withdrawn'})).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
test('another teacher and another school cannot open a private message',async ({page})=>{
  const id=Number(php("app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1,fn()=>print(\\App\\Models\\Communication\\Message::latest('id')->value('id')));"));
  await signIn(page,accounts.teacher2);
  expect((await page.goto(`/communication-centre?message_id=${id}`))?.status()).toBe(404);
  await page.context().clearCookies();
  await signIn(page,accounts.adminB);
  expect((await page.goto(`/communication-centre?message_id=${id}`))?.status()).toBe(404);
});

test('an audience owner can archive an empty list after its contact leaves scope',async ({page})=>{
  await signIn(page,author);
  await page.goto('/communication-centre');
  await page.getByText('Saved custom audiences',{exact:true}).click();
  let form=page.getByRole('form',{name:'Save custom audience',exact:true});
  await form.getByRole('textbox',{name:'Audience name',exact:true}).fill(`Audience ${run}`);
  await form.getByRole('checkbox').first().check();
  await form.getByRole('button',{name:'Save custom audience',exact:true}).click();
  const url=page.url();
  php("app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1,function () { $learner=\\App\\Models\\Students::whereHas('user',fn($q)=>$q->where('email','message.learner@demo.navigateed.test'))->firstOrFail(); \\App\\Models\\Platform\\GuardianLink::where('learner_id',$learner->id)->update(['status'=>'revoked']); });");
  await page.goto(url);
  await expect(page.getByRole('status').filter({hasText:'Contacts no longer available in your scope'})).toBeVisible();
  form=page.getByRole('form',{name:'Save custom audience',exact:true});
  await expect(form.locator('input[name="user_ids[]"]')).toHaveCount(0);
  await form.getByRole('combobox',{name:'Audience availability',exact:true}).selectOption('0');
  await form.getByRole('button',{name:'Save custom audience',exact:true}).click();
  await expect(page.getByRole('status').filter({hasText:'Audience saved'})).toBeVisible();
});
