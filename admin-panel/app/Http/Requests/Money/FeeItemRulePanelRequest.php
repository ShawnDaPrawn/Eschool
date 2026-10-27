<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeItemRules;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePlanRules;

class FeeItemRulePanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeItems::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-items.index';
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethodSafe()) {
            return;
        }
        // Resolve only the current-school options after checking the private form binding.
        $this->authorize();
        if ($this->exists('target_choice')) {
            $choice = $this->input('target_choice');
            abort_unless(is_string($choice) && preg_match('/\A(all:0|(?:grade|class):[1-9][0-9]{0,17})\z/D', $choice) === 1, 422);
            [$scope,$id] = explode(':', $choice);
            $this->merge(['target_scope' => $scope, 'target_id' => $scope === 'all' ? null : (int) $id]);
        }
        if ($this->exists('membership_choice')) {
            $choice = $this->input('membership_choice');
            $ids = ['activity_group_id' => null, 'teaching_group_id' => null, 'subject_id' => null];
            if ($choice !== null && $choice !== '') {
                abort_unless(is_string($choice) && preg_match('/\A(activity_group|teaching_group|subject):([1-9][0-9]{0,17})\z/D', $choice, $m) === 1, 422);
                $ids[$m[1].'_id'] = (int) $m[2];
            }$this->merge($ids);
        }
        if ($this->exists('rule_rows')) {
            $rows = $this->input('rule_rows');
            abort_unless(is_array($rows) && count($rows) <= 20, 422);
            $rules = [];
            foreach ($rows as $row) {
                abort_unless(is_array($row), 422);
                $field = $row['field'] ?? '';
                $text = $row['values'] ?? '';
                abort_unless(is_string($field) && is_string($text) && strlen($text) <= 16000, 422);
                if ($field === '') {
                    abort_unless(trim($text) === '', 422, 'Choose a field for each rule.');

                    continue;
                }
                $values = preg_split('/\R/u', trim($text));
                abort_unless(is_array($values) && count($values) <= 30, 422);
                if ($field === 'phase') {
                    $rules[] = ['field' => 'phase', 'values' => $values];

                    continue;
                }
                abort_unless(preg_match('/\Aprofile:([1-9][0-9]{0,17})\z/D', $field, $m) === 1, 422);
                $definition = app(FeePlanRules::class)->fields()->findOrFail((int) $m[1]);
                if ($definition->type === 'boolean') {
                    $values = array_map(function (string $value): bool {
                        $value = mb_strtolower($value);
                        if (in_array($value, ['yes', 'true', mb_strtolower(__('Yes'))], true)) {
                            return true;
                        }
                        if (in_array($value, ['no', 'false', mb_strtolower(__('No'))], true)) {
                            return false;
                        }
                        abort(422, 'Use Yes or No for a boolean profile rule.');
                    }, $values);
                }
                $rules[] = ['field' => 'profile', 'definition_id' => (int) $definition->id, 'values' => $values];
            }$this->merge(['applies_to' => $rules]);
        }
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + FeeItemRules::rules();
    }
}
