import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.describe.configure({ mode: 'serial' });
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`house reversal and private award assembly workflow at ${width}px`, async ({ page, baseURL }) => {
    const raw=execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/recognition-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'});
    const fixture=JSON.parse(raw.trim());
    await page.setViewportSize({width,height:1000});await signIn(page,accounts.admin);
    const errors=collectErrors(page,baseURL!);
    await page.goto(`/recognition/house_points?learner_id=${fixture.learner}`);
    const contribution=`Recognition browser ${width}-${Date.now()} <script>window.recognitionExecuted=1</script>`;
    const baseline=Number(await page.locator(`[data-house="${fixture.house}"]`).textContent());
    const issue=page.getByRole('form',{name:'Award house points',exact:true});
    await issue.locator('[name="point_category_id"]').selectOption(String(fixture.point_category));
    await issue.locator('[name="reason"]').fill(contribution);
    await issue.getByRole('button',{name:'Award points',exact:true}).click();
    await expect(page.locator(`[data-house="${fixture.house}"]`)).toHaveText(String(baseline+5));
    await expect(page.getByText(contribution)).toBeVisible();
    expect(await page.evaluate(()=>(window as unknown as Record<string,unknown>).recognitionExecuted)).toBeUndefined();
    const reversal=page.getByRole('form',{name:/^Reverse house points/}).first();
    await reversal.locator('[name="reason"]').fill('Recognition browser retained reversal');await reversal.getByRole('button',{name:'Append a reversal',exact:true}).click();
    await expect(page.locator(`[data-house="${fixture.house}"]`)).toHaveText(String(baseline));
    expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    await page.goto(`/recognition/awards?learner_id=${fixture.learner}`);
    await page.getByText('Nominate this learner',{exact:true}).click();
    const nominate=page.getByRole('form',{name:'Nominate an award',exact:true});
    await nominate.locator('[name="award_definition_id"]').selectOption(String(fixture.definition));
    await nominate.locator('[name="reason"]').fill('Private nomination evidence never projected');await nominate.getByRole('button',{name:'Submit nomination',exact:true}).click();
    const review=page.getByRole('form',{name:/^Review nomination/}).first();await review.locator('[name="decision"]').selectOption('approved');await review.locator('[name="reason"]').fill('Private reviewer evidence never projected');await review.getByRole('button',{name:'Save review',exact:true}).click();
    await page.getByRole('link',{name:'Open assembly mode',exact:true}).click();
    await expect(page.getByText('Private nomination evidence never projected',{exact:true})).toHaveCount(0);await expect(page.getByText('Private reviewer evidence never projected',{exact:true})).toHaveCount(0);
    const present=page.getByRole('form',{name:/^Present approved nomination/}).first();await present.locator('[name="event"]').fill('Recognition browser assembly');await present.getByRole('button',{name:'Record approved presentation',exact:true}).click();
    await expect(page.getByRole('heading',{name:'Permanent awards',exact:true})).toBeVisible();
    const correction=page.getByRole('form',{name:/^Correct permanent award/}).first();await correction.locator('[name="reason"]').fill('Recognition browser certificate correction');await correction.getByRole('button',{name:'Retain a correction and remove from active profile',exact:true}).click();
    expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids=await page.locator('[id]').evaluateAll(nodes=>nodes.map(n=>n.id));expect(ids.filter((id,i)=>ids.indexOf(id)!==i)).toEqual([]);expect(errors).toEqual([]);
  });
}
