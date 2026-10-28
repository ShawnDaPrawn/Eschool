import { readFileSync, existsSync } from 'node:fs';
import path from 'node:path';
import { expect, test } from '@playwright/test';

test('the real seeded page inventory and authentication states are ready', () => {
  const root = path.resolve(__dirname, '..');
  const pages: string[] = JSON.parse(readFileSync(path.join(root, '.cache/panel-pages.json'), 'utf8'));
  expect(pages.length).toBeGreaterThan(50);
  for (const page of ['/home', '/search', '/new-year']) expect(pages).toContain(page);
  for (const name of ['admin-pages', 'admin-flows', 'teacher']) {
    expect(existsSync(path.join(root, '.auth', name + '.json'))).toBe(true);
  }
});
