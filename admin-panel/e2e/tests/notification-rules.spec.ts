import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';

test.use({ storageState: { cookies: [], origins: [] } });

test('school admin changes an optional parent channel while required notices stay locked', async ({ page }) => {
  await signIn(page, accounts.adminB);
  await page.goto('/notification-rules?portal=parent');
  const email = page.getByRole('combobox', { name: 'School notice email', exact: true });
  const previous = await email.inputValue();
  try {
    await email.selectOption('default_on');
    await page.getByRole('button', { name: 'Save School notice email', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Notification rule updated' })).toContainText('Notification rule updated');
    await expect(email).toHaveValue('default_on');
    const absence = page.getByRole('row').filter({ hasText: 'Absence alert' });
    await expect(absence).toContainText('Required by NavigateEd');
    await expect(absence.getByRole('combobox')).toHaveCount(0);
    await page.getByLabel('Portal', { exact: true }).selectOption('teacher');
    await page.getByRole('button', { name: 'View rules', exact: true }).click();
    await expect(page.getByRole('combobox', { name: 'School notice email', exact: true })).toHaveValue('default_off');
  } finally {
    await page.goto('/notification-rules?portal=parent');
    await email.selectOption(previous);
    await page.getByRole('button', { name: 'Save School notice email', exact: true }).click();
  }
});
