<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** One metadata boundary for API, panel and legacy field adapters. */
class ProfileDefinitions
{
    public function authorize(User $actor, string $operation): User
    {
        return app(ProfilePolicy::class)->manageDefinitions($actor, $operation);
    }

    public function category(User $actor, array $input, ?int $id = null): ProfileFieldCategory
    {
        $this->authorize($actor, $id === null ? 'create' : 'edit');
        $data = Validator::make($input, self::categoryRules())->validate();

        return DB::transaction(function () use ($data, $id) {
            $category = $id === null ? new ProfileFieldCategory : ProfileFieldCategory::query()->lockForUpdate()->findOrFail($id);
            if ($category->exists && ($category->key !== $data['key'] || $category->profile_type !== $data['profile_type'])) {
                throw ValidationException::withMessages(['key' => 'Existing category keys and profile types cannot be changed.']);
            }
            $duplicate = ProfileFieldCategory::query()->withTrashed()->where('profile_type', $data['profile_type'])->where('key', $data['key'])
                ->when($id !== null, fn ($query) => $query->whereKeyNot($id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['key' => 'This category key is already in use.']);
            }
            $category->fill($data)->save();
            $this->changed();

            return $category;
        });
    }

    public function field(User $actor, array $input, ?int $id = null): ProfileFieldDefinition
    {
        $this->authorize($actor, $id === null ? 'create' : 'edit');
        $data = Validator::make($input, self::fieldRules())->validate();
        if (isset($data['validation']['min'], $data['validation']['max']) && $data['validation']['min'] > $data['validation']['max']) {
            throw ValidationException::withMessages(['validation.max' => 'The maximum must be at least the minimum.']);
        }

        return DB::transaction(function () use ($data, $id) {
            $category = ProfileFieldCategory::query()->findOrFail($data['category_id']);
            $field = $id === null ? new ProfileFieldDefinition : ProfileFieldDefinition::query()->lockForUpdate()->findOrFail($id);
            if ($field->exists && ($field->key !== $data['key'] || $field->profile_type !== $category->profile_type)) {
                throw ValidationException::withMessages(['key' => 'Existing field keys and profile types cannot be changed.']);
            }
            if ($field->platform_locked && (($data['hidden'] ?? false) || ($field->required && isset($data['required']) && ! $data['required']))) {
                throw ValidationException::withMessages(['required' => 'This platform field remains available and keeps its required status.']);
            }
            $fixedSource = $field->exists && $field->source !== 'custom';
            if (($fixedSource && (($data['sensitive'] ?? false) || ($data['encrypted'] ?? false)))
                || (! $fixedSource && isset($data['encrypted']) && ! $data['encrypted'])) {
                throw ValidationException::withMessages(['encrypted' => 'Encryption follows the field storage source and cannot be disabled for stored profile values.']);
            }
            if ($field->platform_locked && ($field->type !== $data['type'] || ($field->sensitive && (! ($data['sensitive'] ?? true) || ! ($data['encrypted'] ?? true))))) {
                throw ValidationException::withMessages(['type' => 'This platform field keeps its type and privacy protection.']);
            }
            if (array_diff($data['visible_to'], $this->visibilityLimit($field, $category->profile_type, $data['key'])) !== []
                || array_diff($data['editable_by'], $data['visible_to']) !== []) {
                throw ValidationException::withMessages(['visible_to' => 'Choose roles within the field privacy limits; editors must also be viewers.']);
            }
            if (ProfileFieldDefinition::query()->withTrashed()->where('profile_type', $category->profile_type)->where('key', $data['key'])
                ->when($id !== null, fn ($query) => $query->whereKeyNot($id))->exists()) {
                throw ValidationException::withMessages(['key' => 'This field key is already in use.']);
            }
            if ($field->exists && $field->type !== $data['type'] && ProfileFieldValue::query()->where('definition_id', $field->id)->exists()) {
                throw ValidationException::withMessages(['type' => 'Move existing values before changing this field type.']);
            }
            if (in_array($data['type'], ['dropdown', 'radio', 'checkbox'], true) && ($data['options'] ?? []) === []) {
                throw ValidationException::withMessages(['options' => 'Add at least one option.']);
            }
            $field->fill($data + ['source' => 'custom', 'profile_type' => $category->profile_type]);
            // Do not let an edit change a fixed platform source into a custom field.
            if ($field->exists) {
                $field->source = $field->getOriginal('source');
            }
            $field->encrypted = ! $fixedSource;
            $field->validation ??= [];
            $field->options ??= [];
            $field->save();
            $this->changed();

            return $field;
        });
    }

    public function removeField(User $actor, int $id): void
    {
        $this->authorize($actor, 'delete');
        DB::transaction(function () use ($id) {
            $field = ProfileFieldDefinition::query()->lockForUpdate()->findOrFail($id);
            abort_if($field->platform_locked, 422, 'This platform field cannot be removed.');
            $field->delete();
            $this->changed();
        });
    }

    public function removeCategory(User $actor, int $id): void
    {
        $this->authorize($actor, 'delete');
        DB::transaction(function () use ($id) {
            $category = ProfileFieldCategory::query()->lockForUpdate()->findOrFail($id);
            abort_if(ProfileFieldDefinition::query()->where('category_id', $category->id)->exists(), 422, 'Move the fields to another category first.');
            $category->delete();
            $this->changed();
        });
    }

    public static function categoryRules(): array
    {
        return [
            'profile_type' => ['required', Rule::in(config('profile_fields.types'))],
            'key' => ['required', 'regex:/^[a-z][a-z0-9_]{0,63}$/D'],
            'label' => ['required', 'string', 'max:120'], 'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public static function fieldRules(): array
    {
        return [
            'category_id' => ['required', 'integer'], 'key' => ['required', 'regex:/^[a-z][a-z0-9_]{0,63}$/D'],
            'label' => ['required', 'string', 'max:120'], 'type' => ['required', Rule::in(config('profile_fields.field_types'))],
            'options' => ['sometimes', 'array', 'max:100'], 'options.*' => ['string', 'distinct', 'max:120'],
            'validation' => ['sometimes', 'array:min,max,email,past'],
            'validation.min' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'validation.max' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'validation.email' => ['sometimes', 'boolean'], 'validation.past' => ['sometimes', 'boolean'],
            'visible_to' => ['present', 'array'], 'visible_to.*' => [Rule::in(config('profile_fields.roles')), 'distinct'],
            'editable_by' => ['present', 'array'], 'editable_by.*' => [Rule::in(config('profile_fields.roles')), 'distinct'],
            'required' => ['sometimes', 'boolean'], 'sensitive' => ['sometimes', 'boolean'],
            'encrypted' => ['sometimes', 'boolean'], 'hidden' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ];
    }

    private function visibilityLimit(ProfileFieldDefinition $field, string $profile, string $key): array
    {
        if ($field->platform_locked && $key === 'identity_number') {
            return $profile === 'learner' ? ['owner', 'guardian', 'school_admin'] : ['owner', 'school_admin'];
        }

        return config('profile_fields.roles');
    }

    private function changed(): void
    {
        Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'profile.fields'));
    }
}
