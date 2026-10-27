<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\ClassSchool;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemEligibilityRule;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeItemRules
{
    public function save(User $actor, int $itemId, array $input): FeeItemEligibilityRule
    {
        app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION);
        $data = Validator::make($input, self::rules())->validate();
        $data['item_id'] = $itemId;
        $receipt = app(BillingOperations::class)->once($actor, FeeItems::PERMISSION, 'fee_item_eligibility', $data, function () use ($actor, $itemId, $data): array {
            FeeItem::lockForUpdate()->findOrFail($itemId);
            $rule = FeeItemEligibilityRule::where('fee_item_id', $itemId)->lockForUpdate()->first() ?? new FeeItemEligibilityRule(['fee_item_id' => $itemId]);
            abort_unless((int) ($rule->revision ?? 0) === (int) $data['revision'], 409, 'Item eligibility changed. Review its current rules.');
            if ($data['target_scope'] !== 'all') {
                ($data['target_scope'] === 'grade' ? GradeLevel::query() : ClassSchool::query())->lockForUpdate()->findOrFail($data['target_id']);
            }
            $sources = [];
            foreach (['activity_group_id' => ActivityGroup::class, 'teaching_group_id' => TeachingGroup::class, 'subject_id' => Subject::class] as $column => $model) {
                $id = $data[$column] ?? null;
                if ($id !== null) {
                    $model::lockForUpdate()->findOrFail($id);
                }$sources[$column] = $id === null ? null : (int) $id;
            }
            $count = count(array_filter($sources, fn ($id) => $id !== null));
            abort_unless($data['mode'] === 'membership' ? $count === 1 : $count === 0, 422, 'Choose exactly one membership source for a membership item.');
            abort_unless($data['mode'] !== 'manual' || ($data['target_scope'] === 'all' && ($data['applies_to'] ?? []) === []), 422, 'Manual items do not have automatic matching rules.');
            $rule->fill($sources + ['mode' => $data['mode'], 'target_scope' => $data['target_scope'], 'target_id' => $data['target_scope'] === 'all' ? null : (int) $data['target_id'], 'applies_to' => app(FeePlanRules::class)->normalize($data['applies_to'] ?? []), 'revision' => (int) ($rule->revision ?? 0) + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_item_eligibility_saved', $rule, [], ['mode' => $rule->mode, 'revision' => $rule->revision]);

            return ['id' => (int) $rule->id];
        });

        return FeeItemEligibilityRule::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'mode' => ['required', 'in:manual,compulsory,membership'], 'target_scope' => ['required', 'in:all,grade,class'], 'target_id' => ['nullable', 'integer', 'min:1', 'required_unless:target_scope,all', 'prohibited_if:target_scope,all'], 'applies_to' => ['sometimes', 'array', 'max:20'], 'applies_to.*' => ['required', 'array:field,definition_id,values'], 'applies_to.*.field' => ['required', 'in:phase,profile'], 'applies_to.*.definition_id' => ['nullable', 'integer', 'min:1'], 'applies_to.*.values' => ['required', 'array', 'min:1', 'max:30'], 'applies_to.*.values.*' => ['required'], 'activity_group_id' => ['nullable', 'integer', 'min:1'], 'teaching_group_id' => ['nullable', 'integer', 'min:1'], 'subject_id' => ['nullable', 'integer', 'min:1']];
    }
}
