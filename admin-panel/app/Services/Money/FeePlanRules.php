<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** A bounded data rule, never executable caller expressions or raw SQL. */
final class FeePlanRules
{
    public function fields()
    {
        return ProfileFieldDefinition::where('profile_type', 'learner')->whereIn('source', ['custom', 'user.first_name', 'user.last_name', 'user.dob', 'user.gender', 'user.email', 'user.mobile'])->whereHas('category')->where('hidden', false)->where('sensitive', false)
            ->whereIn('type', ['text', 'number', 'date', 'dropdown', 'radio', 'checkbox', 'boolean'])->whereJsonContains('visible_to', 'school_admin');
    }

    public function normalize(array $input): array
    {
        $rules = Validator::make(['rules' => $input], [
            'rules' => ['present', 'array', 'max:20'], 'rules.*' => ['required', 'array:field,definition_id,values'],
            'rules.*.field' => ['required', 'in:phase,profile'], 'rules.*.definition_id' => ['nullable', 'integer', 'min:1'],
            'rules.*.values' => ['required', 'array', 'min:1', 'max:30'], 'rules.*.values.*' => ['required'],
        ])->validate()['rules'];
        foreach ($rules as &$rule) {
            if ($rule['field'] === 'phase') {
                abort_if(isset($rule['definition_id']), 422, 'Phase rules do not use a profile definition.');
                foreach ($rule['values'] as $value) {
                    abort_unless(is_string($value) && in_array($value, ['Foundation', 'Intermediate', 'Senior', 'FET'], true), 422, 'Choose a registered grade phase.');
                }
                $rule['definition_id'] = null;
            } else {
                $field = $this->fields()->lockForUpdate()->findOrFail($rule['definition_id'] ?? 0);
                $rule['definition_id'] = (int) $field->id;
                foreach ($rule['values'] as $value) {
                    abort_unless(($field->type === 'boolean' && is_bool($value)) || ($field->type !== 'boolean' && is_string($value) && strlen($value) <= 512), 422, 'Use the profile field value type.');
                    if (in_array($field->type, ['dropdown', 'radio', 'checkbox'], true)) {
                        abort_unless(in_array($value, $field->options, true), 422, 'Choose a configured profile option.');
                    }
                }
            }
            $rule['values'] = array_values(array_unique($rule['values'], SORT_REGULAR));
        }unset($rule);

        return array_values($rules);
    }

    public function context(array $rules, array $userIds, bool $locked): array
    {
        $ids = array_values(array_unique(array_filter(array_column($rules, 'definition_id'))));
        $fields = $this->fields()->whereIn('id', $ids)->when($locked, fn ($q) => $q->lockForUpdate())->get()->keyBy('id');
        $values = [];
        foreach (ProfileFieldValue::whereIn('definition_id', $fields->keys())->whereIn('user_id', $userIds)->when($locked, fn ($q) => $q->lockForUpdate())->get() as $row) {
            $values[(int) $row->user_id][(int) $row->definition_id] = $row;
        }
        $people = User::whereIn('id', $userIds)->when($locked, fn ($q) => $q->lockForUpdate())->get(['id', 'first_name', 'last_name', 'dob', 'gender', 'email', 'mobile'])->keyBy('id');

        return ['fields' => $fields, 'values' => $values, 'people' => $people, 'school_id' => app(CurrentSchool::class)->id()];
    }

    public function matches(array $rules, ?string $phase, int $user, array $context): bool
    {
        abort_unless($context['school_id'] === app(CurrentSchool::class)->id(), 403);
        foreach ($rules as $rule) {
            if ($rule['field'] === 'phase') {
                if (! in_array($phase, $rule['values'], true)) {
                    return false;
                }

                continue;
            }
            $field = $context['fields']->get($rule['definition_id']);
            if ($field === null) {
                throw ValidationException::withMessages(['applies_to' => 'A billing profile rule is no longer available.']);
            }
            if (str_starts_with($field->source, 'user.')) {
                $person = $context['people']->get($user);
                $value = $person?->getAttribute(substr($field->source, 5));
                if ($field->source === 'user.gender' && is_string($value)) {
                    $value = ucfirst(strtolower($value));
                }
                if (! in_array($value, $rule['values'], true)) {
                    return false;
                }

                continue;
            }
            $stored = $context['values'][$user][$rule['definition_id']] ?? null;
            if ($stored === null) {
                return false;
            }
            try {
                if (! $stored->encrypted) {
                    throw new \RuntimeException;
                }
                $value = json_decode(Crypt::decryptString($stored->getAttribute('value')), true, flags: JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                throw ValidationException::withMessages(['applies_to' => 'A billing profile rule value needs review.']);
            }
            $actual = $field->type === 'checkbox' && is_array($value) ? $value : [$value];
            if (array_filter($actual, fn ($v) => in_array($v, $rule['values'], true)) === []) {
                return false;
            }
        }

        return true;
    }
}
