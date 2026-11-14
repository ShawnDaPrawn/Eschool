import { expect as baseExpect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

// The shared local PHP runtime may finish a native POST redirect after the default 10s.
const expect = baseExpect.configure({ timeout: 60000 });

test.use({ storageState: { cookies: [], origins: [] } });
test('settings save one section without hidden required fields and profile fits mobile',async ({page})=>{
  await signIn(page,accounts.admin);
  await page.setViewportSize({width:320,height:740});
  await page.goto('/settings?view=links');
  const form=page.locator('#school-settings-links');
  const field=form.locator('[name="facebook"]');
  const original=await field.inputValue();
  try {
    await field.fill('https://example.org/synthetic-school');
    const saved=page.waitForResponse(response=>response.url().endsWith('/settings') && response.request().method()==='POST');
    await form.getByRole('button',{name:'Save this section',exact:true}).click();
    expect((await saved).ok()).toBe(true);
    await page.reload();
    await expect(field).toHaveValue('https://example.org/synthetic-school');
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(await seriousViolations(page)).toEqual([]);
    await page.goto('/settings?view=localization');
    await expect(page.locator('#school-settings-localization').getByRole('link',{name:'Prepare or activate the school year',exact:true})).toBeVisible();
    await expect(page.locator('select[name="session_year"]')).toHaveCount(0);
  } finally {
    await page.goto('/settings?view=links');
    await field.fill(original);
    const restored = page.waitForResponse(response=>response.url().endsWith('/settings') && response.request().method()==='POST');
    await form.getByRole('button',{name:'Save this section',exact:true}).click();
    expect((await restored).ok()).toBe(true);
  }
  await page.goto('/edit-profile');
  await expect(page.locator('#my-profile-form')).toBeVisible();
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await seriousViolations(page)).toEqual([]);
  await page.goto('/settings?view=maintenance');
  const maintenance = page.locator('form[action$="/clear"]');
  await expect(maintenance).toHaveAttribute('method', 'POST');
  await expect(maintenance).toHaveAttribute('data-year-safe', '');
  await expect(maintenance).toHaveAttribute('data-confirm', /shared application caches/);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  expect(await seriousViolations(page)).toEqual([]);
});

test('email template tabs support keyboard selection and leave the account logout form intact', async ({page,baseURL})=>{
  await signIn(page,accounts.admin);
  await page.setViewportSize({width:375,height:810});
  const errors=collectErrors(page,baseURL!);
  const templateWrites:string[]=[];
  page.on('request',request=>{if(request.method()==='POST' && new URL(request.url()).pathname==='/email/store') templateWrites.push(request.url());});
  await page.goto('/email-tamplate');
  const tabs=page.getByRole('tablist').getByRole('tab');
  await tabs.first().focus();
  await page.keyboard.press('End');
  await expect(tabs.last()).toBeFocused();
  await expect(tabs.last()).toHaveAttribute('aria-selected','true');
  await expect(page.locator('#rejection')).toBeVisible();
  expect(await seriousViolations(page)).toEqual([]);
  await page.keyboard.press('Home');
  await page.keyboard.press('ArrowRight');
  await expect(tabs.nth(1)).toBeFocused();
  await expect(page.locator('#staff')).toBeVisible();
  expect(await seriousViolations(page)).toEqual([]);
  expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
  await page.getByRole('button',{name:/Account menu for/}).click();
  const logout=page.waitForResponse(response=>new URL(response.url()).pathname==='/logout' && response.request().method()==='POST');
  await page.getByRole('menuitem',{name:'Signout',exact:true}).click();
  expect((await logout).status()).toBe(302);
  await expect(page.locator('body[data-panel-layout]')).toHaveCount(0);
  expect(templateWrites).toEqual([]);
  expect(errors).toEqual([]);
});
