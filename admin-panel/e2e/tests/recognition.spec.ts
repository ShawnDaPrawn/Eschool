import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { accounts, signIn } from '../support/accounts';
import { collectErrors, horizontalOverflow, seriousViolations } from '../support/checks';

test.setTimeout(420_000);
test.use({ storageState: { cookies: [], origins: [] } });
for (const width of [390, 820, 1280]) {
  test(`house points and retained reversal at ${width}px`, async ({ page, baseURL }) => {
    const raw=execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/competition-panel-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'});
    const fixture=JSON.parse(raw.trim());
    await page.setViewportSize({width,height:1000});await signIn(page,accounts.admin);
    const errors=collectErrors(page,baseURL!);
    await page.goto(`/recognition/house_points?learner_id=${fixture.learner}`);
    const contribution=`Recognition browser ${width}-${Date.now()} <script>window.recognitionExecuted=1</script>`;
    const baseline=Number(await page.locator(`[data-house="${fixture.house}"]`).textContent());
    // The private page suppresses referrers by default. The live same-origin
    // request must still authenticate through Sanctum's stateful cookie flow.
    const poll=await page.waitForResponse(response=>response.url().includes('/api/v2/recognition/house_points/counters'),{timeout:12000});
    expect(poll.status()).toBe(200);
    expect((await poll.json()).data.session_year_id).toBe(Number(await page.locator('[data-counter-url]').getAttribute('data-year')));
    await expect(page.locator(`[data-house="${fixture.house}"]`)).toHaveText(String(baseline));
    await page.getByRole('link',{name:'Award house points',exact:true}).click();
    const issue=page.getByRole('form',{name:'Award house points',exact:true});
    await issue.locator('[name="point_category_id"]').selectOption(String(fixture.point_category));
    await issue.locator('[name="reason"]').fill(contribution);
    await issue.getByRole('button',{name:'Award points',exact:true}).click();
    await expect(page.locator(`[data-house="${fixture.house}"]`)).toHaveText(String(baseline+5));
    await expect(page.getByText(contribution)).toBeVisible();
    expect(await page.evaluate(()=>(window as unknown as Record<string,unknown>).recognitionExecuted)).toBeUndefined();
    await page.getByRole('link',{name:'Review and reverse points',exact:true}).first().click();
    const reversal=page.getByRole('form',{name:/^Reverse house points/}).first();
    await reversal.locator('[name="reason"]').fill('Recognition browser retained reversal');await reversal.getByRole('button',{name:'Append a reversal',exact:true}).click();
    await expect(page.locator(`[data-house="${fixture.house}"]`)).toHaveText(String(baseline));
    expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    expect(errors).toEqual([]);
  });
  test(`Golden Boot category, nomination, assembly and correction at ${width}px`, async ({ page, baseURL }) => {
    const raw=execFileSync('php',['artisan','tinker','--execute',"echo json_encode(require base_path('e2e/support/competition-panel-fixture.php'));"],{cwd:path.resolve(process.cwd(),'..'),encoding:'utf8'});
    const fixture=JSON.parse(raw.trim());
    await page.setViewportSize({width,height:1000});await signIn(page,accounts.admin);
    const errors=collectErrors(page,baseURL!);
    // Configure a real group-scoped award and its year window before nominating.
    const awardName=`U14 soccer boys Golden Boot ${width}-${Date.now()}`;
    const categoryName=`U14 soccer boys awards ${width}-${Date.now()}`;
    await page.goto('/recognition/awards?view=configuration');
    if (width === 390) {
      await expect.poll(() => page.locator('.panel-local-nav').evaluate(nav => {
        const active=nav.querySelector('[aria-current="page"]')!;const item=active.getBoundingClientRect(), viewport=nav.getBoundingClientRect();
        return item.left >= viewport.left - 1 && item.right <= viewport.right + 1;
      })).toBe(true);
    }
    expect(await seriousViolations(page)).toEqual([]);
    const linkColors=await page.locator('.recognition a.m3-button, .recognition .panel-local-nav a').evaluateAll(links=>links.map(link=>{
      const style=getComputedStyle(link);return {label:link.textContent?.trim(),variant:link.className,active:link.getAttribute('aria-current'),color:style.color,background:style.backgroundColor};
    }));
    await test.info().attach(`recognition-link-colors-${width}`,{body:JSON.stringify(linkColors,null,2),contentType:'application/json'});
    await page.screenshot({path:test.info().outputPath(`award-configuration-${width}.png`),fullPage:true});
    await page.getByRole('link',{name:'Create award category',exact:true}).click();
    const category=page.getByRole('form',{name:/^Award category configuration/});
    await category.locator('[name="name"]').fill(categoryName);
    await category.locator('[name="scope_kind"]').selectOption('group');
    await category.locator('[name="activity_group_id"]').selectOption(String(fixture.group));
    await category.getByRole('button',{name:'Save award category',exact:true}).click();
    const configuredCategory=page.locator('.recognition-entry').filter({has:page.getByRole('heading',{name:categoryName,exact:true})});
    await expect(configuredCategory).toBeVisible();
    await configuredCategory.getByRole('link',{name:'Configure nomination window',exact:true}).click();
    const nominationWindow=page.getByRole('form',{name:/^Nomination window/});
    await nominationWindow.locator('[name="activity_group_id"]').selectOption(String(fixture.group));
    await nominationWindow.locator('[name="opens_on"]').fill(fixture.starts_on);
    await nominationWindow.locator('[name="closes_on"]').fill(fixture.ends_on);
    await nominationWindow.locator('[name="status"]').selectOption('open');
    await nominationWindow.getByRole('button',{name:'Save year nomination window',exact:true}).click();
    await page.getByRole('link',{name:'Create award',exact:true}).click();
    const definition=page.getByRole('form',{name:/^Award definition configuration/});
    await definition.locator('[name="award_category_id"]').selectOption({label:categoryName});
    await definition.locator('[name="name"]').fill(awardName);
    await definition.locator('[name="criteria"]').fill('Recognise the leading goal scorer in the U14 boys soccer team.');
    await page.screenshot({path:test.info().outputPath(`award-definition-${width}.png`),fullPage:true});
    await definition.getByRole('button',{name:'Save reusable definition',exact:true}).click();
    await expect(page.getByRole('heading',{name:awardName,exact:true})).toBeVisible();
    await page.goto(`/recognition/awards?learner_id=${fixture.learner}`);
    await page.getByRole('link',{name:'Nominate learner',exact:true}).click();
    const nominate=page.getByRole('form',{name:'Nominate an award',exact:true});
    await nominate.locator('[name="award_definition_id"]').selectOption({label:awardName});
    await nominate.locator('[name="reason"]').fill('Private nomination evidence never projected');await nominate.getByRole('button',{name:'Submit nomination',exact:true}).click();
    await page.getByRole('link',{name:'Nominations and decisions',exact:true}).click();
    await page.locator('.recognition-entry').filter({has:page.getByRole('heading',{name:awardName,exact:true})}).getByRole('link',{name:'Open nomination and permitted actions',exact:true}).click();
    const review=page.getByRole('form',{name:/^Review nomination/}).first();await review.locator('[name="decision"]').selectOption('approved');await review.locator('[name="reason"]').fill('Private reviewer evidence never projected');await review.getByRole('button',{name:'Save review',exact:true}).click();
    await page.getByRole('link',{name:'Open assembly mode',exact:true}).click();
    await expect(page.getByText('Private nomination evidence never projected',{exact:true})).toHaveCount(0);await expect(page.getByText('Private reviewer evidence never projected',{exact:true})).toHaveCount(0);
    const present=page.locator('.recognition-entry').filter({has:page.getByRole('heading',{name:awardName,exact:true})}).getByRole('form',{name:/^Present approved nomination/});await present.locator('[name="event"]').fill('Recognition browser assembly');await present.getByRole('button',{name:'Record approved presentation',exact:true}).click();
    await expect(page).toHaveURL(/assembly=1/);
    await expect(page.getByText('Private nomination evidence never projected',{exact:true})).toHaveCount(0);
    await expect(page.getByText('Private reviewer evidence never projected',{exact:true})).toHaveCount(0);
    await page.getByRole('link',{name:'Leave assembly mode',exact:true}).click();
    await page.goto(`/recognition/awards?learner_id=${fixture.learner}`);
    await expect(page.getByRole('heading',{name:'Permanent awards',exact:true})).toBeVisible();
    await expect(page.getByRole('heading',{name:awardName,exact:true})).toBeVisible();
    await page.screenshot({path:test.info().outputPath(`award-profile-${width}.png`),fullPage:true});
    await page.locator('.recognition-entry').filter({has:page.getByRole('heading',{name:awardName,exact:true})}).getByRole('link',{name:'Correct presentation',exact:true}).click();
    const correction=page.getByRole('form',{name:/^Correct permanent award/}).first();await correction.locator('[name="reason"]').fill('Recognition browser certificate correction');await correction.getByRole('button',{name:'Retain a correction and remove from active profile',exact:true}).click();
    await page.getByRole('link',{name:'Presentation and correction history',exact:true}).click();
    await expect(page.locator('.recognition-entry').filter({has:page.getByRole('heading',{name:awardName,exact:true})})).toContainText('Recognition browser certificate correction');
    expect(await seriousViolations(page)).toEqual([]);expect(await horizontalOverflow(page)).toBeLessThanOrEqual(1);
    const ids=await page.locator('[id]').evaluateAll(nodes=>nodes.map(n=>n.id));expect(ids.filter((id,i)=>ids.indexOf(id)!==i)).toEqual([]);expect(errors).toEqual([]);
  });
}
