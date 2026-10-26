import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

function execute(code: string): string {
  return execFileSync('php', ['artisan', 'tinker', '--execute', code], {cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8'}).trim();
}
test.describe.configure({mode: 'serial'});
test.use({storageState: {cookies: [], origins: []}});
for (const width of [390, 820, 1280]) {
  test(`allocated conversations preserve private replies and retire withdrawn scope at ${width}px`, async ({page, browser, baseURL}) => {
    const setup = JSON.parse(execute("echo json_encode(require base_path('e2e/support/group-chat-fixture.php'));"));
    await page.setViewportSize({width, height: 1000});
    const errors = collectErrors(page, baseURL!);
    const expectedErrors = [`HTTP 422: /group-conversations/groups/${setup.group}/create`];
    await signIn(page, accounts.teacher);
    await page.goto('/group-conversations');
    await page.getByRole('link', {name: setup.name, exact: true}).click();
    await page.getByText('Start a conversation', {exact: true}).click();
    // Exercise the real browser's 422 form, then change only the invalid field.
    await page.getByLabel('Title', {exact: true}).fill(`Synthetic private draft ${width}`);
    await page.getByLabel('Conversation type', {exact: true}).selectOption('question');
    await page.getByLabel('Audience', {exact: true}).selectOption('staff');
    await page.getByLabel('Message', {exact: true}).fill('Synthetic confidential draft');
    await page.getByLabel('Message language', {exact: true}).evaluate((select: HTMLSelectElement) => {
      select.add(new Option('Invalid synthetic language', 'invalid!', true, true));
    });
    await page.getByRole('button', {name: 'Create conversation', exact: true}).click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page.getByLabel('Conversation type', {exact: true})).toHaveValue('question');
    await expect(page.getByLabel('Audience', {exact: true})).toHaveValue('staff');
    await expect(page.getByLabel('Message', {exact: true})).toHaveValue('Synthetic confidential draft');
    await page.getByLabel('Message language', {exact: true}).selectOption('en');
    await page.getByRole('button', {name: 'Create conversation', exact: true}).click();
    await expect(page).toHaveURL(/\/threads\/[a-f0-9-]{36}$/);
    await expect(page.getByRole('region', {name: 'Selected conversation'})).toContainText('Staff · Private question');
    await page.getByRole('link', {name: 'Refresh group conversations', exact: true}).click();
    await page.getByText('Start a conversation', {exact: true}).click();
    await page.getByLabel('Title', {exact: true}).fill(`Synthetic announcement ${width}`);
    await page.getByLabel('Audience', {exact: true}).selectOption('guardians');
    await page.getByLabel('Message', {exact: true}).fill('Synthetic announcement <script>neverExecute()</script>');
    await page.getByRole('button', {name: 'Create conversation', exact: true}).click();
    await expect(page).toHaveURL(/\/threads\/[a-f0-9-]{36}$/);
    const url = page.url(), thread = url.match(/threads\/([a-f0-9-]{36})/)![1];
    await expect(page.getByText('Synthetic announcement <script>neverExecute()</script>', {exact: true})).toBeVisible();
    const family = await browser.newContext({baseURL});
    const headers = {Authorization: `Bearer ${setup.token}`, 'X-School-Id': '1', 'X-Account-Id': String(setup.parent), Accept: 'application/json'};
    const endpoint = `/api/v2/activities/groups/${setup.group}/chat/threads/${thread}`;
    const sent = await family.request.post(`${endpoint}/messages`, {headers, data: {operation_id: crypto.randomUUID(), persona: 'parent', visibility: 'staff', body: 'Synthetic private parent question', language: 'af'}});
    expect(sent.status()).toBe(200);
    const parentMessage = (await sent.json()).data.message_id;
    await page.reload();
    const selected = page.getByRole('region', {name: 'Selected conversation', exact: true});
    await expect(selected.getByText('Synthetic private parent question', {exact: true})).toBeVisible();
    await selected.getByLabel('Reply destination').selectOption(String(parentMessage));
    await selected.getByLabel('Message', {exact: true}).fill('Synthetic private staff response');
    await selected.getByLabel('Message language', {exact: true}).evaluate((select: HTMLSelectElement) => {
      select.add(new Option('Invalid synthetic language', 'invalid!', true, true));
    });
    await selected.getByRole('button', {name: 'Send reply', exact: true}).click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(selected.getByLabel('Reply destination')).toHaveValue(String(parentMessage));
    await expect(page.locator('#chat-create-body')).toHaveValue('');
    await selected.getByLabel('Message language', {exact: true}).selectOption('en');
    await selected.getByRole('button', {name: 'Send reply', exact: true}).click();
    await expect(selected.getByText('Synthetic private staff response', {exact: true})).toBeVisible();
    const read = await family.request.get(`${endpoint}?persona=parent`, {headers});
    expect(read.status()).toBe(200);
    expect((await read.json()).data.items.find((item: {body: string}) => item.body === 'Synthetic private staff response').private).toBe(true);
    const article = selected.getByRole('article').filter({hasText: 'Synthetic private parent question'});
    await article.getByText('Remove this message', {exact: true}).click();
    await article.getByLabel('Reason for removal').fill('Synthetic reasoned moderation');
    await article.getByRole('button', {name: 'Remove message', exact: true}).click();
    await expect(selected.getByText('Message removed', {exact: true})).toBeVisible();
    await expect(selected.getByText('Synthetic private parent question', {exact: true})).toHaveCount(0);
    await selected.getByRole('button', {name: 'Close conversation', exact: true}).click();
    await expect(selected.getByRole('button', {name: 'Send reply', exact: true})).toHaveCount(0);
    await selected.getByRole('button', {name: 'Reopen conversation', exact: true}).click();
    await expect(selected.getByRole('button', {name: 'Send reply', exact: true})).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.length).toBe(new Set(ids).size);
    expectedErrors.push(`HTTP 422: /group-conversations/groups/${setup.group}/threads/${thread}/send`);
    expect(errors).toEqual(expectedErrors);
    const lease = new URL((await page.locator('[data-lease-url]').getAttribute('data-lease-url'))!);
    // A heartbeat rechecks the server allocation; stale private DOM is erased.
    execute(`app(\\App\\Services\\Platform\\Tenancy\\CurrentSchool::class)->run(1,fn()=>\\App\\Models\\Activities\\ActivityStaff::where('activity_group_id',${setup.group})->where('user_id',${setup.teacher})->update(['active'=>false]));`);
    await page.evaluate(() => window.dispatchEvent(new Event('focus')));
    await expect(page.getByText('This private view has expired. Reload to check your current access.', {exact: true})).toBeVisible();
    await expect(page.getByText('Synthetic private staff response', {exact: true})).toHaveCount(0);
    expect(errors).toEqual([...expectedErrors, `HTTP 404: ${lease.pathname}${lease.search}`]);
    await family.close();
  });
}
