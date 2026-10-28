import { chromium, request, type FullConfig } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { accounts, signIn } from './support/accounts';

/**
 * Before the tests: sign in the demo accounts once (saved under .auth/) and
 * list the panel's pages. A panel page is a GET route without parameters that
 * answers the Super Admin with HTML built on layouts/master (data-panel-layout);
 * list endpoints that answer JSON and the public website are not pages.
 */
const SKIP = ['up', 'storage-link', 'migrate', 'seeder_install', 'delete-chat-message/cron-job', 'logout'];
const SKIP_PREFIXES = ['api/', 'install', '_debugbar', 'sanctum/', 'storage/'];

export default async function globalSetup(config: FullConfig): Promise<void> {
  const baseURL = config.projects[0].use.baseURL ?? 'http://127.0.0.1:8000';
  const root = __dirname;
  mkdirSync(path.join(root, '.auth'), { recursive: true });
  mkdirSync(path.join(root, '.cache'), { recursive: true });

  const browser = await chromium.launch(config.projects[0].use.launchOptions);
  for (const [file, email] of [
    ['admin-pages', accounts.admin],
    ['admin-flows', accounts.admin],
    ['teacher', accounts.teacher],
  ] as const) {
    const context = await browser.newContext({ baseURL });
    const page = await context.newPage();
    await signIn(page, email);
    await context.storageState({ path: path.join(root, '.auth', `${file}.json`) });
    await context.close();
  }
  await browser.close();

  // Every GET route without parameters, from the app itself.
  const routes = JSON.parse(
    execFileSync('php', ['artisan', 'route:list', '--json', '--method=GET'], { cwd: path.join(root, '..'), encoding: 'utf8' }),
  ) as { uri: string }[];
  const candidates = [...new Set(routes.map((r) => r.uri))]
    .filter((uri) => !uri.includes('{') && !SKIP.includes(uri) && !SKIP_PREFIXES.some((p) => uri.startsWith(p)))
    .sort();

  const api = await request.newContext({ baseURL, storageState: path.join(root, '.auth', 'admin-pages.json') });
  const pages: string[] = [];
  for (const uri of candidates) {
    const response = await api.get('/' + uri.replace(/^\//, ''), { maxRedirects: 0, failOnStatusCode: false });
    const type = response.headers()['content-type'] ?? '';
    if (response.status() === 200 && type.includes('text/html') && (await response.text()).includes('data-panel-layout')) {
      pages.push('/' + uri.replace(/^\//, ''));
    }
  }
  await api.dispose();

  writeFileSync(path.join(root, '.cache', 'panel-pages.json'), JSON.stringify(pages, null, 2) + '\n');
}
