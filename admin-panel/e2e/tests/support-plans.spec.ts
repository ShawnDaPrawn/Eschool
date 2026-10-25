import { execFileSync } from "node:child_process";
import path from "node:path";
import { expect, test, type BrowserContext } from "@playwright/test";
import { accounts, signIn } from "../support/accounts";
import {
    collectErrors,
    horizontalOverflow,
    seriousViolations,
} from "../support/checks";
test.describe.configure({ mode: "serial" });
test.use({ storageState: { cookies: [], origins: [] } });
type State = Awaited<ReturnType<BrowserContext["storageState"]>>;
let adminState: State,
    professionalState: State,
    teacherState: State,
    learnerName: string,
    teacherName: string;
const today = new Date().toISOString().slice(0, 10),
    expiry = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);
const professionalEmail = "support.browser@demo.navigateed.test";
test.beforeAll(async ({ browser, baseURL }) => {
    const fixture = String.raw`app(\App\Services\Platform\Tenancy\CurrentSchool::class)->run(1,function(){
 $admin=\App\Models\User::where('email','admin.a@demo.navigateed.test')->firstOrFail();
 app(\App\Services\Platform\Modules\ModuleGate::class)->change('support_plans',true,$admin);
 $year=\App\Models\SessionYear::findOrFail((int)schoolSetting('session_year'));
 if(!\App\Models\Academics\AcademicLeadership::where('user_id',$admin->id)->where('kind','principal')->where('session_year_id',$year->id)->exists()) app(\App\Services\Academics\TeachingGroups::class)->dated($admin,'leadership',['user_id'=>$admin->id,'session_year_id'=>$year->id,'kind'=>'principal','grade_level_id'=>null,'starts_on'=>$year->start_date,'ends_on'=>$year->end_date,'reason'=>'Synthetic browser principal']);
 $professional=\App\Models\User::where('email','support.browser@demo.navigateed.test')->first()??new \App\Models\User;
 $professional->forceFill(['first_name'=>'Browser','last_name'=>'Counsellor','email'=>'support.browser@demo.navigateed.test','password'=>\Illuminate\Support\Facades\Hash::make('NavigateDemo2026'),'gender'=>'Female','mobile'=>'0821234567','image'=>'demo/avatar.png','status'=>1,'email_verified_at'=>now(),'password_change_required'=>false])->save();$professional->syncRoles(['Counsellor']);
 $learner=\App\Models\Students::with('user')->orderBy('id')->firstOrFail();
 \App\Models\People\Enrolment::firstOrCreate(['learner_id'=>$learner->id,'session_year_id'=>$year->id],['class_section_id'=>$learner->class_section_id,'starts_on'=>$year->start_date,'ends_on'=>null,'legacy_dates'=>false]);
 if(!\App\Models\Pastoral\SupportAssignment::where('learner_id',$learner->id)->where('user_id',$professional->id)->where('active',true)->exists()) app(\App\Services\Pastoral\SupportPlans::class)->assign($admin,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'learner_id'=>$learner->id,'user_id'=>$professional->id,'starts_on'=>now()->toDateString(),'ends_on'=>now()->addDays(30)->toDateString(),'reason'=>'Private synthetic browser appointment']);
 foreach(\App\Models\Pastoral\SupportPlan::where('learner_id',$learner->id)->where('state','active')->get() as $plan) app(\App\Services\Pastoral\SupportPlans::class)->close($professional,$plan->id,['operation_id'=>(string)\Illuminate\Support\Str::uuid(),'revision'=>$plan->revision,'reason'=>'Reset synthetic browser support']);
 $teacher=\App\Models\User::where('email','teacher.a@demo.navigateed.test')->firstOrFail();echo json_encode(['learner'=>$learner->user->full_name,'teacher'=>$teacher->full_name]);});`;
    const names = JSON.parse(
        execFileSync("php", ["artisan", "tinker", "--execute", fixture], {
            cwd: path.resolve(process.cwd(), ".."),
            encoding: "utf8",
        }).trim(),
    ) as { learner: string; teacher: string };
    learnerName = names.learner;
    teacherName = names.teacher;
    for (const email of [accounts.admin, professionalEmail, accounts.teacher]) {
        const c = await browser.newContext({ baseURL });
        await signIn(await c.newPage(), email);
        const state = await c.storageState();
        if (email === accounts.admin) adminState = state;
        else if (email === professionalEmail) professionalState = state;
        else teacherState = state;
        await c.close();
    }
});
for (const width of [1024, 1280])
    test(`restricted support workflow at ${width}`, async ({
        browser,
        baseURL,
    }) => {
        test.setTimeout(120000);
        const pc = await browser.newContext({
                baseURL,
                storageState: professionalState,
            }),
            ac = await browser.newContext({
                baseURL,
                storageState: adminState,
            }),
            tc = await browser.newContext({
                baseURL,
                storageState: teacherState,
            });
        const page = await pc.newPage();
        await page.setViewportSize({ width, height: 1000 });
        const errors = collectErrors(page, baseURL!);
        await page.goto("/support-plans?audience=professional");
        const create = page.getByRole("form", {
            name: "Start a support plan",
            exact: true,
        });
        await create
            .getByRole("combobox", { name: "Learner", exact: true })
            .selectOption({ label: learnerName });
        await create
            .getByRole("textbox", { name: "Plan start date", exact: true })
            .fill(today);
        await create
            .getByRole("textbox", { name: "SIAS stage", exact: true })
            .fill("SNA1");
        await create
            .getByRole("textbox", { name: "Support needs", exact: true })
            .fill(`Private browser support ${width}`);
        await create
            .getByRole("textbox", { name: "Accommodations", exact: true })
            .fill(`Private full plan ${width}`);
        await create
            .getByRole("textbox", { name: "Review due", exact: true })
            .fill(expiry);
        await create
            .getByRole("button", { name: "Save support plan", exact: true })
            .click();
        await expect(
            page.getByText("Current saved plan", { exact: false }).first(),
        ).toBeVisible();
        const planUrl = page.url();
        const disclose = page.getByRole("form", {
            name: "Approve teacher instructions",
            exact: true,
        });
        await disclose
            .getByRole("combobox", { name: "Teacher", exact: true })
            .selectOption({ label: teacherName });
        await disclose
            .getByRole("textbox", { name: "Expires on", exact: true })
            .fill(expiry);
        await disclose
            .getByRole("textbox", {
                name: "Approved classroom instructions",
                exact: true,
            })
            .fill(`Approved quiet seating ${width}`);
        await disclose
            .getByRole("textbox", {
                name: "Reason for this disclosure",
                exact: true,
            })
            .fill("Limited classroom instructions");
        await disclose
            .getByRole("checkbox", { name: /I approve only/ })
            .check();
        await disclose
            .getByRole("button", { name: "Approve instructions", exact: true })
            .click();
        await expect(
            page
                .getByRole("status")
                .filter({ hasText: "Support plan change saved" }),
        ).toBeVisible();
        const teacherPage = await tc.newPage();
        await teacherPage.setViewportSize({ width, height: 1000 });
        await teacherPage.goto("/support-plans?audience=teacher");
        await expect(
            teacherPage.getByText(`Approved quiet seating ${width}`, {
                exact: true,
            }),
        ).toBeVisible();
        await expect(
            teacherPage.getByText(`Private browser support ${width}`, {
                exact: false,
            }),
        ).toHaveCount(0);
        await expect(
            teacherPage.getByText("SNA1", { exact: false }),
        ).toHaveCount(0);
        const adminPage = await ac.newPage();
        await adminPage.setViewportSize({ width, height: 1000 });
        await adminPage.goto("/support-plans?audience=principal");
        await expect(
            adminPage.getByRole("heading", {
                name: "Support assignments",
                exact: true,
            }),
        ).toBeVisible();
        await expect(
            adminPage.getByText(`Private browser support ${width}`, {
                exact: false,
            }),
        ).toHaveCount(0);
        for (const target of [page, teacherPage, adminPage]) {
            expect(await seriousViolations(target)).toEqual([]);
            expect(await horizontalOverflow(target)).toBeLessThanOrEqual(1);
            const ids = await target
                .locator("[id]")
                .evaluateAll((nodes) => nodes.map((n) => n.id));
            expect(new Set(ids).size).toBe(ids.length);
        }
        await page.goto(planUrl);
        const close = page.getByRole("form", {
            name: "Close support plan",
            exact: true,
        });
        await close
            .getByRole("textbox", { name: "Closure rationale", exact: true })
            .fill("Browser support concluded");
        await close
            .getByRole("button", { name: "Close plan", exact: true })
            .click();
        await expect(
            page.getByText("closed", { exact: false }).first(),
        ).toBeVisible();
        await teacherPage.reload();
        await expect(
            teacherPage.getByText(`Approved quiet seating ${width}`, {
                exact: true,
            }),
        ).toHaveCount(0);
        expect(errors).toEqual([]);
        await pc.close();
        await tc.close();
        await ac.close();
    });
