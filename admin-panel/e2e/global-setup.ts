import { chromium, request, type FullConfig } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { accounts, signIn } from './support/accounts';
import { panelTaskDestinations, prepareUiReworkSchool } from './support/ui-rework';

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

  prepareUiReworkSchool();

  const browser = await chromium.launch(config.projects[0].use.launchOptions);
  for (const [file, email] of [
    ['admin-pages', accounts.admin],
    ['admin-flows', accounts.admin],
    ['teacher', accounts.teacher],
    ['admin-discovery', accounts.admin],
  ] as const) {
    if (process.env.E2E_REUSE_AUTH === '1' && file !== 'admin-discovery' && existsSync(path.join(root, '.auth', `${file}.json`))) continue;
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
  const routeCandidates = [...new Set(routes.map((r) => r.uri))]
    .filter((uri) => !uri.includes('{') && !SKIP.includes(uri) && !SKIP_PREFIXES.some((p) => uri.startsWith(p)))
    .map(uri => '/' + uri.replace(/^\//, ''));
  const candidates = [...new Set([...routeCandidates, ...panelTaskDestinations()])].sort();

  const api = await request.newContext({ baseURL, storageState: path.join(root, '.auth', 'admin-discovery.json') });
  const pages: string[] = [];
  const failures: { uri: string; status: number; error?: string }[] = [];
  const responses: { uri: string; status: number; panel: boolean }[] = [];
  // Independent read-only requests; local verification can reserve server
  // workers for workflows while CI discovery runs before the test projects.
  const batchSize = Math.max(1, Math.min(8, Number(process.env.E2E_DISCOVERY_CONCURRENCY || 4)));
  for (let offset = 0; offset < candidates.length; offset += batchSize) {
    const batch = await Promise.all(candidates.slice(offset, offset + batchSize).map(async uri => {
      const url = '/' + uri.replace(/^\//, '');
      try {
        const response = await api.get(url, { maxRedirects: 0, failOnStatusCode: false });
        const status = response.status();
        const type = response.headers()['content-type'] ?? '';
        const panel = status === 200 && type.includes('text/html') && (await response.text()).includes('data-panel-layout');
        return { uri: url, status, panel };
      } catch {
        // Finish discovery and fail its explicit acceptance check. Do not lose
        // every other route, or print request cookies in a network traceback.
        return { uri: url, status: 0, panel: false, error: 'Request failed or timed out during page discovery.' };
      }
    }));
    for (const result of batch) {
      responses.push({ uri: result.uri, status: result.status, panel: result.panel });
      if (result.status === 0 || result.status >= 500) failures.push({ uri: result.uri, status: result.status, ...('error' in result ? { error: result.error } : {}) });
      if (result.panel) pages.push(result.uri);
    }
    writeFileSync(path.join(root, '.cache', 'panel-discovery-progress.json'), JSON.stringify({ checked: Math.min(offset + batchSize, candidates.length), total: candidates.length, pageCount: pages.length, failures }, null, 2) + '\n');
  }
  await api.dispose();

  writeFileSync(path.join(root, '.cache', 'panel-pages.json'), JSON.stringify(pages, null, 2) + '\n');
  writeFileSync(path.join(root, '.cache', 'panel-discovery-failures.json'), JSON.stringify(failures, null, 2) + '\n');
  // Keep authorized refusals/redirects visible in the audit without treating
  // an intentional JSON, role-limited or non-panel endpoint as a missing page.
  writeFileSync(path.join(root, '.cache', 'panel-discovery-responses.json'), JSON.stringify(responses, null, 2) + '\n');
}
