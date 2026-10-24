import { readFileSync } from 'node:fs';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.use({ storageState: { cookies: [], origins: [] } });
for (const code of ['af', 'zu']) {
  for (const width of [1024, 1280]) {
    test(`${code} academic workbenches at ${width}px`, async ({ page, baseURL }) => {
      const labels = JSON.parse(readFileSync(path.resolve(process.cwd(), '../resources/lang', `${code}.json`), 'utf8')) as Record<string, string>;
      await page.setViewportSize({ width, height: 900 });
      await signIn(page, accounts.admin);
      await page.goto('/academic-sasams');
      await page.goto(`/set-language/${code}`);
      const errors = collectErrors(page, baseURL!);
      for (const [url, title] of [
        ['/academic-registers', 'Academic registers'],
        ['/academic-timetable', 'Academic timetable'],
        ['/communication-centre', 'School messages'],
      ]) {
        await page.goto(url);
        await expect(page.getByRole('heading', { level: 1, name: labels[title], exact: true })).toBeVisible();
        await expect(page.locator('html')).toHaveAttribute('lang', code);
        expect(await seriousViolations(page)).toEqual([]);
        expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      }
      expect(errors).toEqual([]);
    });
  }
}
