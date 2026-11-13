import { expect as baseExpect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { horizontalOverflow, seriousViolations } from '../support/checks';

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
