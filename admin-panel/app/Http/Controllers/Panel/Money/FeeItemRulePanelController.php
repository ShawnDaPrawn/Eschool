<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeItemRulePanelRequest;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\ClassSchool;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemEligibilityRule;
use App\Models\Subject;
use App\Services\Money\FeeItemRules;
use App\Services\Money\FeePlanRules;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeItemRulePanelController extends Controller
{
    public function form(FeeItemRulePanelRequest $request): Response
    {
        $item = FeeItem::findOrFail((int) $request->route('item'));
        $rule = FeeItemEligibilityRule::where('fee_item_id', $item->id)->first();
        $targets = ['all:0' => __('All dated enrolments')];
        foreach (GradeLevel::orderBy('name')->get() as $row) {
            $targets['grade:'.$row->id] = __('Grade').': '.$row->name;
        }
        foreach (ClassSchool::orderBy('name')->get() as $row) {
            $targets['class:'.$row->id] = __('Class').': '.$row->name;
        }
        $sources = ['' => __('No membership source')];
        foreach (['activity_group' => ActivityGroup::class, 'teaching_group' => TeachingGroup::class, 'subject' => Subject::class] as $kind => $model) {
            foreach ($model::orderBy('name')->get(['id', 'name']) as $row) {
                $sources[$kind.':'.$row->id] = __($kind === 'activity_group' ? 'Activity or route group' : ($kind === 'teaching_group' ? 'Teaching group' : 'Subject')).': '.$row->name;
            }
        }
        $ruleFields = app(FeePlanRules::class)->fields()->orderBy('label')->get();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.item-rules.form', compact('item', 'rule', 'targets', 'sources', 'ruleFields', 'canWrite'));
    }

    public function save(FeeItemRulePanelRequest $request): Response
    {
        app(FeeItemRules::class)->save($request->user(), (int) $request->route('item'), $request->validated());

        return redirect()->route('fee-items.rules', ['item' => $request->route('item')], 303)->with('success', __('Item eligibility saved.'));
    }
}
