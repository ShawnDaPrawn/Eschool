import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';
test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 1280]) {
  test(`private follow-up owner workflow and metadata-only leadership at ${width}px`, async ({ page, baseURL }) => {
    execFileSync('php', ['artisan','tinker','--execute',"require base_path('e2e/support/risk-fixture.php');"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'});
    await page.setViewportSize({width,height:1000});
    await signIn(page,accounts.teacher);
    const errors=collectErrors(page,baseURL!);
    await page.goto('/risk-interventions');
    await page.getByRole('link',{name:'Open your intervention',exact:true}).first().click();
    await expect(page.locator('#risk-action')).toHaveValue('Synthetic private professional follow-up');
    await page.locator('#risk-action').fill('Synthetic reviewed action <script>window.riskTextExecuted=1</script>');
    await page.locator('#risk-outcome').fill('Synthetic review outcome');
    await page.getByRole('button',{name:'Save review',exact:true}).click();
    await expect(page.locator('#risk-action')).toHaveValue('Synthetic reviewed action <script>window.riskTextExecuted=1</script>');
    expect(await page.evaluate(()=>(window as unknown as Record<string,unknown>).riskTextExecuted)).toBeUndefined();
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids=await page.locator('[id]').evaluateAll(nodes=>nodes.map(n=>n.id));
    expect(ids.filter((id,i)=>ids.indexOf(id)!==i)).toEqual([]);
    await page.context().clearCookies();await signIn(page,accounts.admin);
    await page.goto('/risk-interventions');
    await expect(page.getByText('Synthetic review outcome',{exact:true})).toHaveCount(0);
    await expect(page.locator('#risk-action')).toHaveCount(0);
    await page.getByRole('link',{name:'Change the assigned owner',exact:true}).first().click();
    await expect(page.getByRole('button',{name:'Confirm owner handoff',exact:true})).toBeVisible();
    await expect(page.getByText('Synthetic review outcome',{exact:true})).toHaveCount(0);
    expect(await seriousViolations(page)).toEqual([]);
    expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
}
