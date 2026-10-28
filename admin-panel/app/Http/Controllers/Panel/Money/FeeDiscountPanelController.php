<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeDiscountPanelRequest;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeeDiscount;
use App\Models\Money\FeeDiscountRun;
use App\Models\Money\FeeItem;
use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\FeeDiscountRuns;
use App\Services\Money\FeeDiscounts;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class FeeDiscountPanelController extends Controller
{
    public function index(FeeDiscountPanelRequest $request): Response
    {
        $rules = FeeDiscount::orderByDesc('id')->paginate(30);
        $runs = FeeDiscountRun::orderByDesc('id')->limit(60)->get();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.discounts.index', compact('rules', 'runs', 'canWrite'));
    }

    public function form(FeeDiscountPanelRequest $request): Response
    {
        $rule = $request->route('rule') === null ? null : FeeDiscount::findOrFail((int) $request->route('rule'));
        $year = SessionYear::where('default', 1)->first();
        $plans = $year === null ? collect() : FeePlan::where('session_year_id', $year->id)->orderBy('name')->get();
        $items = FeeItem::orderBy('name')->get();
        $students = $year === null ? collect() : Students::whereIn('id', Enrolment::where('session_year_id', $year->id)->select('learner_id'))->orderBy('id')->get(['id', 'user_id']);
        $people = User::whereIn('id', $students->pluck('user_id'))->get()->keyBy('id');
        $learners = [];
        foreach ($students as $student) {
            $person = $people->get($student->user_id);
            if ($person !== null) {
                $learners[$student->id] = trim($person->first_name.' '.$person->last_name);
            }
        }
        $canWrite = app(ModuleGate::class)->writable('fees') && ($rule === null || ($year !== null && (int) $rule->session_year_id === (int) $year->id));

        $staffRoles = Role::where('school_id', app(CurrentSchool::class)->id())->whereNotIn('name', ['Student', 'Parent', 'Vendor'])->orderBy('name')->pluck('name', 'name');

        return response()->view('money.discounts.form', compact('rule', 'year', 'plans', 'items', 'learners', 'canWrite', 'staffRoles'));
    }

    public function save(FeeDiscountPanelRequest $request): Response
    {
        $rule = app(FeeDiscounts::class)->save($request->user(), $request->validated(), $request->route('rule') === null ? null : (int) $request->route('rule'));

        return redirect()->route('fee-discounts.edit', ['rule' => $rule->id], 303)->with('success', __('Discount draft saved. Review before approval.'));
    }

    public function transition(FeeDiscountPanelRequest $request): Response
    {
        $rule = app(FeeDiscounts::class)->transition($request->user(), (int) $request->route('rule'), $request->validated());

        return redirect()->route('fee-discounts.edit', ['rule' => $rule->id], 303)->with('success', __('Discount rule updated.'));
    }

    public function application(FeeDiscountPanelRequest $request): Response
    {
        $currentYear = SessionYear::where('default', 1)->value('id');
        $rules = FeeDiscount::where('state', 'active')->where('session_year_id', $currentYear)->orderBy('name')->get();
        $accounts = FeeAccount::where('active', true)->orderBy('name')->get();
        $account = $request->filled('account_id') ? FeeAccount::findOrFail($request->integer('account_id')) : null;
        $year = SessionYear::where('default', 1)->first();
        $learners = [];
        if ($account !== null && $year !== null) {
            $students = Students::whereIn('id', FeeAccountLearner::where('fee_account_id', $account->id)->where('session_year_id', $year->id)->select('learner_id'))->get(['id', 'user_id']);
            $people = User::whereIn('id', $students->pluck('user_id'))->get()->keyBy('id');
            foreach ($students as $student) {
                $person = $people->get($student->user_id);
                if ($person !== null) {
                    $learners[$student->id] = trim($person->first_name.' '.$person->last_name);
                }
            }
        }
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.discounts.application', compact('rules', 'accounts', 'account', 'learners', 'canWrite'));
    }

    public function prepare(FeeDiscountPanelRequest $request): Response
    {
        $run = app(FeeDiscountRuns::class)->prepare($request->user(), $request->validated());

        return redirect()->route('fee-discounts.run', ['run' => $run->id], 303);
    }

    public function run(FeeDiscountPanelRequest $request): Response
    {
        $run = FeeDiscountRun::findOrFail((int) $request->route('run'));
        $canWrite = app(ModuleGate::class)->writable('fees') && (int) SessionYear::where('default', 1)->value('id') === (int) $run->session_year_id;

        return response()->view('money.discounts.run', compact('run', 'canWrite'));
    }

    public function approve(FeeDiscountPanelRequest $request): Response
    {
        $run = app(FeeDiscountRuns::class)->approve($request->user(), (int) $request->route('run'), $request->validated());

        return redirect()->route('fee-discounts.run', ['run' => $run->id], 303);
    }

    public function post(FeeDiscountPanelRequest $request): Response
    {
        $run = app(FeeDiscountRuns::class)->post($request->user(), (int) $request->route('run'), $request->validated());

        return redirect()->route('fee-discounts.run', ['run' => $run->id], 303)->with('success', __('Approved fee reduction posted once.'));
    }
}
