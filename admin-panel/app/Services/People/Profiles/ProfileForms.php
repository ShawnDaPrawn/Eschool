<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Metadata, authorization and validation shared by every form consumer. */
class ProfileForms
{
    private const USER_SOURCES = ['first_name', 'last_name', 'dob', 'gender', 'email', 'mobile'];

    public function __construct(private readonly ProfilePolicy $policy) {}

    private function definitions(string $profile)
    {
        return ProfileFieldDefinition::query()->where('profile_type', $profile)->whereHas('category')->with('category')
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public function schema(User $actor, User $person, string $profile, bool $forExport = false): array
    {
        $context = $this->policy->resolve($actor, $person, $profile);
        $fields = $this->definitions($profile)->filter(fn ($field) => $this->policy->field($context, $field)
            && (! $forExport || ! $field->sensitive || $this->policy->permission($context['actor'], 'profile-sensitive-export')));
        $values = ProfileFieldValue::query()->where('user_id', $person->id)->whereIn('definition_id', $fields->pluck('id'))->get()->keyBy('definition_id');
        $categories = [];
        foreach ($fields->groupBy('category_id')->sortBy(fn ($group) => $group->first()->category->sort_order) as $group) {
            $category = $group->first()->category;
            $rows = [];
            foreach ($group as $field) {
                $rows[] = ['id' => (int) $field->id, 'key' => $field->key, 'label' => __($field->label), 'type' => $field->type,
                    'validation' => (object) $this->validationFor($field), 'options' => $field->options, 'required' => $field->required, 'locked' => $field->platform_locked,
                    'editable' => $this->policy->field($context, $field, write: true), 'sensitive' => $field->sensitive,
                    'value' => $this->wireValue($field, $this->read($context['person'], $field, $values->get($field->id)), $context['person'])];
            }
            $categories[] = ['id' => (int) $category->id, 'key' => $category->key, 'label' => __($category->label), 'fields' => $rows];
        }

        return ['person_id' => (int) $person->id, 'profile_type' => $profile, 'available_profiles' => $this->policy->types($context['person']), 'categories' => $categories];
    }

    private function wireValue(ProfileFieldDefinition $field, mixed $value, User $person): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($field->type === 'file') {
            return ['file' => app(ProfileFiles::class)->metadata($person, $field, (string) $value)];
        }

        return match ($field->type) {
            'boolean' => ['flag' => (bool) $value],
            'checkbox' => ['choices' => $value],
            default => ['text' => (string) $value],
        };
    }

    private function read(User $person, ProfileFieldDefinition $field, ?ProfileFieldValue $stored): mixed
    {
        if (str_starts_with($field->source, 'user.')) {
            $key = substr($field->source, 5);
            abort_unless(in_array($key, self::USER_SOURCES, true), 422);
            $value = $person->getAttribute($key);

            return $key === 'gender' && is_string($value) ? ucfirst(strtolower($value)) : $value;
        }
        if ($field->source === 'guardian.relationship') {
            return GuardianLink::query()->where('guardian_user_id', $person->id)->where('status', 'active')->pluck('relationship')->unique()->implode(', ');
        }
        if ($stored?->value === null) {
            return null;
        }
        try {
            if (! $stored->encrypted) {
                throw new RuntimeException('Unexpected profile value storage.');
            }

            return json_decode(Crypt::decryptString($stored->value), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            // Never include a private value, ciphertext or key in an error.
            throw new RuntimeException('A stored profile value is unavailable.');
        }
    }

    /** Partial updates validate the supplied editable fields; other fields do not freeze progress. */
    public function save(User $actor, User $person, string $profile, array $input): void
    {
        $context = $this->policy->resolve($actor, $person, $profile);
        abort_unless($context['write'], 403);
        abort_if($input === [] || count($input) > 100, 422);
        $definitions = $this->definitions($profile)->keyBy('key');
        $values = [];
        foreach ($input as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $key)) {
                throw ValidationException::withMessages(['values' => 'Choose a valid editable field.']);
            }
            $field = $definitions->get($key);
            if ($field === null || ! $this->policy->field($context, $field, write: true)) {
                throw ValidationException::withMessages(['values' => 'A field is not editable or available.']);
            }
            $values[$key] = $this->validateValue($context['person'], $field, $value);
        }
        DB::transaction(function () use ($context, $profile, $values) {
            $person = User::query()->lockForUpdate()->findOrFail($context['person']->id);
            $current = $this->policy->resolve($context['actor'], $person, $profile);
            abort_unless($current['write'], 403);
            $definitions = ProfileFieldDefinition::query()->where('profile_type', $profile)->whereIn('key', array_keys($values))->lockForUpdate()->get()->keyBy('key');
            foreach ($values as $key => $value) {
                $field = $definitions->get($key);
                if (! $field || ! $this->policy->field($current, $field, write: true)) {
                    throw ValidationException::withMessages(['values.'.$key => 'This field is no longer editable.']);
                }
                $value = $this->validateValue($person, $field, $value);
                if (str_starts_with($field->source, 'user.')) {
                    $column = substr($field->source, 5);
                    abort_unless(in_array($column, self::USER_SOURCES, true), 422);
                    $person->setAttribute($column, $value);
                } else {
                    ProfileFieldValue::query()->updateOrCreate(['user_id' => $person->id, 'definition_id' => $field->id],
                        ['value' => $value === null ? null : Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR)), 'encrypted' => true]);
                }
            }
            $person->save();
            Audit::record('profile.updated', $person, [], ['profile_type' => $definitions->first()?->profile_type, 'field_keys' => array_keys($values)],
                schoolId: app(CurrentSchool::class)->id());
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'profile.values'));
        });

    }

    public function validationFor(ProfileFieldDefinition $field): array
    {
        $validation = $field->validation ?? [];
        $limit = match ($field->source) {
            'user.first_name', 'user.last_name' => 128,
            'user.email', 'user.mobile' => 191,
            'user.gender' => 16,
            default => null,
        };
        if ($limit !== null) {
            $validation['max'] = min($limit, $validation['max'] ?? $limit);
        }
        if ($field->source === 'user.email') {
            $validation['email'] = true;
        }
        if ($field->source === 'user.dob') {
            $validation['past'] = true;
        }

        return $validation;
    }

    /** Validation only; callers still own authorization and transactional writes. */
    public function validateValue(User $person, ProfileFieldDefinition $field, mixed $value): mixed
    {
        $validation = $this->validationFor($field);
        $rules = [$field->required ? 'required' : 'nullable'];
        if ($field->type === 'file') {
            if ($value !== null) {
                throw ValidationException::withMessages(['values.'.$field->key => 'Upload this file through the authorised file action.']);
            }
        } elseif ($field->type === 'checkbox') {
            $rules = array_merge($rules, ['array', 'max:100']);
            $extra = ['value.*' => ['string', 'distinct', Rule::in($field->options)]];
        } elseif ($field->type === 'boolean') {
            $rules[] = 'boolean';
        } elseif ($field->type === 'date') {
            $rules[] = 'date_format:Y-m-d';
            if (($validation['past'] ?? false) || $field->source === 'user.dob') {
                $rules[] = 'before_or_equal:today';
            }
        } elseif ($field->type === 'number') {
            $rules = array_merge($rules, ['string', 'numeric', 'regex:/^-?\d{1,20}(?:\.\d{1,6})?$/D']);
            foreach (['min', 'max'] as $bound) {
                if (isset($validation[$bound])) {
                    $rules[] = $bound.':'.$validation[$bound];
                }
            }
        } elseif (in_array($field->type, ['dropdown', 'radio'], true)) {
            $rules = array_merge($rules, ['string', Rule::in($field->options)]);
            if (isset($validation['max'])) {
                $rules[] = 'max:'.$validation['max'];
            }
        } elseif (in_array($field->type, ['text', 'textarea'], true)) {
            $rules = array_merge($rules, ['string', 'max:'.($validation['max'] ?? ($field->type === 'textarea' ? 10000 : 512))]);
            if (isset($validation['min'])) {
                $rules[] = 'min:'.$validation['min'];
            }
            if ($validation['email'] ?? false) {
                $rules[] = 'email';
            }
        } else {
            throw ValidationException::withMessages(['values.'.$field->key => 'This field type is not available.']);
        }
        if ($field->source === 'user.email') {
            $rules[] = Rule::unique('users', 'email')->ignore($person->id);
        }
        $validator = Validator::make(['value' => $value], ['value' => $rules] + ($extra ?? []), attributes: ['value' => __($field->label)]);
        if ($validator->fails()) {
            $member = match ($field->type) {
                'checkbox' => 'choices', 'boolean' => 'flag', 'file' => 'clear', default => 'text',
            };
            throw ValidationException::withMessages(['values.'.$field->key.'.'.$member => $validator->errors()->all()]);
        }

        return $field->type === 'boolean' && $value !== null ? (bool) $value : $value;
    }
}
