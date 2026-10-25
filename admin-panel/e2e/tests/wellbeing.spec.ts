import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
let fixture: {case: number; report: number; category: number; assigned: string; unassigned: string};
test.beforeAll(() => {
  fixture = JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/wellbeing-panel-fixture.php'));"], { cwd: path.resolve(process.cwd(), '..'), encoding: 'utf8' }));
});
for (const width of [1024, 1280]) {
  test(`separate metadata, clinical work and unassigned denial at ${width}px`, async ({page}) => {
    await page.setViewportSize({width, height: 900});
    await signIn(page, accounts.admin);
    await page.goto(`/wellbeing?mode=configuration&category=${fixture.category}&managed_case=${fixture.case}`);
    await expect(page.getByRole('heading', {name: 'Case assignment metadata', exact: true})).toBeVisible();
    await expect(page.getByText('Synthetic confidential clinical baseline', {exact: true})).toHaveCount(0);
    await expect(page.getByText('Synthetic confidential reported concern', {exact: true})).toHaveCount(0);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.context().clearCookies();
    await signIn(page, fixture.assigned);
    await page.goto(`/wellbeing?case=${fixture.case}`);
    await expect(page.getByText('Synthetic confidential clinical baseline', {exact: true})).toBeVisible();
    let form = page.locator('form').filter({has: page.getByRole('button', {name:'Save private case entry', exact:true})});
    const note = `Synthetic private browser escalation ${width} ${Date.now()}`;
    await form.getByRole('combobox', {name:'Entry type', exact:true}).selectOption('escalation');
    await form.getByRole('textbox', {name:'Private case note', exact:true}).fill(note);
    await form.getByRole('button', {name:'Save private case entry', exact:true}).click();
    await expect(page.getByText(note, {exact:true})).toBeVisible();
    form = page.locator('form').filter({has: page.getByRole('button', {name:'Book private appointment', exact:true})});
    await form.getByRole('combobox', {name:'Appointment professional', exact:true}).selectOption({label:'Clinic Assigned'});
    const location = `Private clinical room ${width} ${Date.now()}`;
    await form.getByRole('textbox', {name:'Appointment location', exact:true}).fill(location);
    await form.getByRole('textbox', {name:'Appointment notes', exact:true}).fill('Synthetic private appointment purpose');
    // Adjacent time windows keep the two width rehearsals independent.
    const date = (await form.getByRole('textbox', {name:'Appointment starts', exact:true}).inputValue()).slice(0,10);
    const hour = width === 1024 ? '09' : '10';
    await form.getByRole('textbox', {name:'Appointment starts', exact:true}).fill(`${date}T${hour}:00`);
    await form.getByRole('textbox', {name:'Appointment ends', exact:true}).fill(`${date}T${hour}:30`);
    await form.getByRole('button', {name:'Book private appointment', exact:true}).click();
    await expect(page.getByText(location, {exact:true})).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids = await page.locator('[id]').evaluateAll(nodes => nodes.map(node => node.id));
    expect(ids.length).toBe(new Set(ids).size);
    await page.goto(`/wellbeing?report=${fixture.report}`);
    await expect(page.getByText('Synthetic confidential reported concern', {exact:true})).toBeVisible();
    form = page.locator('form').filter({has:page.getByRole('button',{name:'Save triage response',exact:true})});
    await expect(form.getByLabel('Keep this note internal to the safeguarding team')).toBeChecked();
    const triage = `Synthetic confidential internal triage ${width} ${Date.now()}`;
    await form.getByRole('textbox', {name:'Triage note or reply', exact:true}).fill(triage);
    await form.getByRole('combobox', {name:'Report status', exact:true}).selectOption('triaged');
    await form.getByRole('button', {name:'Save triage response',exact:true}).click();
    await expect(page.getByText(triage,{exact:true})).toBeVisible();
    expect(await seriousViolations(page)).toEqual([]);
    await page.context().clearCookies();
    await signIn(page, fixture.unassigned);
    expect((await page.goto(`/wellbeing?case=${fixture.case}`))?.status()).toBe(404);
    await expect(page.getByText(note,{exact:true})).toHaveCount(0);
    expect((await page.goto(`/wellbeing?report=${fixture.report}`))?.status()).toBe(404);
    await expect(page.getByText(triage,{exact:true})).toHaveCount(0);
  });
}
