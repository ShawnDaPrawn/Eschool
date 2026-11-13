import { expect, test } from '@playwright/test';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
import { prepareUiReworkSchool } from '../support/ui-rework';

test.use({ storageState: '.auth/admin-pages.json' });
test.beforeAll(prepareUiReworkSchool);

const workspaces = [
  ['Home', '/home'], ['People', '/workspace/people'],
  ['Attendance', '/workspace/attendance'], ['Leave', '/workspace/leave'],
  ['Academics', '/workspace/academics'], ['Extracurriculars', '/workspace/extracurriculars'],
  ['Awards', '/workspace/awards'], ['Discipline', '/workspace/discipline'],
  ['Community', '/workspace/community'], ['Vendors', '/workspace/vendors'],
  ['Finance', '/workspace/finance'], ['Settings', '/workspace/settings?view=configuration'],
] as const;

for (const width of [320, 375, 768, 1280]) {
  test.describe(`${width}px school administration`, () => {
    test.use({ viewport: { width, height: 900 } });

    for (const [name, path] of workspaces) {
      test(`${name} workspace loads without overflow or inaccessible controls`, async ({ page, baseURL }) => {
        const errors = collectErrors(page, baseURL!);
        expect((await page.goto(path))?.status()).toBe(200);
        await expect(page.locator('main h1')).toHaveCount(1);
        await expect(page.locator('main h1')).toBeVisible();
        await page.waitForLoadState('networkidle');
        expect(await horizontalOverflow(page), `${name} overflows at ${width}px`).toBeLessThanOrEqual(1);
        if (width >= 992) {
          await expect.poll(() => page.evaluate(() => {
            const nav = document.querySelector('#sidebar')!.getBoundingClientRect();
            const heading = document.querySelector('main h1')!.getBoundingClientRect();
            return getComputedStyle(document.documentElement).direction === 'rtl'
              ? heading.right - nav.left : nav.right - heading.left;
          }), { message: 'The settled page heading must not sit beneath the desktop sidebar.' }).toBeLessThanOrEqual(1);
        }
        const activeTabs = page.locator('main :is(.workspace-tabs, .panel-section-nav, .panel-local-nav) a[aria-current]');
        for (const tab of await activeTabs.all()) {
          const contrast = await tab.evaluate(node => {
            const style = getComputedStyle(node);
            const rgb = (value: string) => (value.match(/[\d.]+/g) || []).slice(0, 3).map(Number);
            const luminance = (value: string) => rgb(value).map(channel => {
              const c = channel / 255;
              return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
            }).reduce((sum, c, i) => sum + c * [0.2126, 0.7152, 0.0722][i], 0);
            const foreground = luminance(style.color), background = luminance(style.backgroundColor);
            return (Math.max(foreground, background) + 0.05) / (Math.min(foreground, background) + 0.05);
          });
          expect(contrast, `${name} selected tab text contrast`).toBeGreaterThanOrEqual(4.5);
        }
        expect(await seriousViolations(page)).toEqual([]);
        expect(errors).toEqual([]);
      });
    }

    test('learner creation keeps its labelled controls within the screen', async ({ page }) => {
      expect((await page.goto('/students/create'))?.status()).toBe(200);
      await expect(page.locator('main h1')).toHaveCount(1);
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      const controls = page.locator('main input:not([type="hidden"]), main select, main textarea');
      expect(await controls.count()).toBeGreaterThan(10);
      const outside = await controls.evaluateAll((nodes, viewport) => nodes.filter(node => {
        const element = node as HTMLElement;
        if (!element.getClientRects().length || element.classList.contains('m3-visually-hidden')) return false;
        const rect = element.getBoundingClientRect();
        return rect.left < -1 || rect.right > Number(viewport) + 1;
      }).map(node => node.getAttribute('name')), width);
      expect(outside).toEqual([]);
      for (const upload of await page.locator('main .file-upload-browse:visible').all()) {
        const box = await upload.boundingBox();
        expect(box).not.toBeNull();
        expect(box!.x).toBeGreaterThanOrEqual(0);
        expect(box!.x + box!.width).toBeLessThanOrEqual(width + 1);
      }
      const portrait = page.locator('main .m3-file-field').filter({ has: page.locator('input[name="image"]') });
      const chooser = page.waitForEvent('filechooser');
      await portrait.getByRole('button').click();
      await (await chooser).setFiles({
        name: 'learner-portrait.png', mimeType: 'image/png',
        buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/M2kAAAAASUVORK5CYII=', 'base64'),
      });
      await expect(portrait.locator('.file-upload-info')).toHaveValue('learner-portrait.png');
      expect(await seriousViolations(page)).toEqual([]);
    });

    test('the global year control is reachable without entering a module', async ({ page }) => {
      await page.goto('/home');
      if (width < 992) await page.locator('[data-panel-nav-open]').click();
      const selector = page.locator(width < 992 ? '#filter_session_year_global_mobile' : '#filter_session_year_global');
      await expect(selector).toBeVisible();
      expect(await selector.locator('option').count()).toBeGreaterThanOrEqual(2);
      const selected = await selector.inputValue();
      const other = await selector.locator('option').evaluateAll((options, value) => options.find(option => (option as HTMLOptionElement).value !== value)?.getAttribute('value'), selected);
      expect(other).toBeTruthy();
      // Inspecting the global context must not activate a different school year.
      await expect(selector).toHaveAttribute('data-selected', selected);
    });

    test('confirmation dialogs fit the viewport and restore keyboard focus', async ({ page }) => {
      await page.goto('/home');
      const opener = page.locator('main h1');
      await opener.evaluate(node => node.setAttribute('tabindex', '-1'));
      await opener.focus();
      await page.evaluate(() => {
        (window as unknown as { pageConfirm: (options: object) => Promise<boolean> }).pageConfirm({ title: 'Confirm school action', message: 'Review the selected school and academic year before continuing.', confirmLabel: 'Continue' });
      });
      const dialog = page.getByRole('alertdialog');
      await expect(dialog).toBeVisible();
      const box = await dialog.boundingBox();
      expect(box).not.toBeNull();
      expect(box!.x).toBeGreaterThanOrEqual(0);
      expect(box!.x + box!.width).toBeLessThanOrEqual(width + 1);
      await expect(dialog.getByRole('button', { name: 'Cancel', exact: true })).toBeFocused();
      expect(await seriousViolations(page)).toEqual([]);
      await page.keyboard.press('Escape');
      await expect(dialog).not.toBeVisible();
      await expect(opener).toBeFocused();
    });

    for (const path of ['/my-school/inbox', '/my-school/preferences/notifications', '/my-school/preferences/quiet-hours', '/my-school/preferences/texts']) {
      test(`${path} fits the screen and exposes accessible controls`, async ({ page }) => {
        expect((await page.goto(path))?.status()).toBe(200);
        await expect(page.locator('main h1')).toHaveCount(1);
        expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
        expect(await seriousViolations(page)).toEqual([]);
      });
    }

    if (width < 992) {
      test('the mobile drawer supports Escape and returns keyboard focus', async ({ page }) => {
        await page.goto('/home');
        const opener = page.locator('[data-panel-nav-open]');
        await expect(opener).toBeVisible();
        await opener.click();
        await expect(opener).toHaveAttribute('aria-expanded', 'true');
        await expect(page.locator('#sidebar')).toHaveClass(/active/);
        await page.locator('#sidebar-close').focus();
        await page.keyboard.press('Escape');
        await expect(opener).toHaveAttribute('aria-expanded', 'false');
        await expect(opener).toBeFocused();
        expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
      });
    }
  });
}
