<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use App\Models\FormField;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Throwable;

/** Additive transition only: never erase legacy data or replace a live edit. */
class LegacyProfileBackfill
{
    private const PROFILES = [
        'learner' => ['students', 1, 'Student'],
        'parent' => ['parents', 2, 'Parent'],
        'teacher' => ['teachers', 3, 'Teacher'],
    ];

    public function run(int $schoolId, string $profile, int $after = 0, int $limit = 500, bool $apply = false, bool $files = false): array
    {
        if (! isset(self::PROFILES[$profile]) || $schoolId < 1 || $after < 0 || $limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Invalid legacy profile batch.');
        }
        School::query()->findOrFail($schoolId);

        return app(CurrentSchool::class)->run($schoolId, function () use ($schoolId, $profile, $after, $limit, $apply, $files) {
            $report = ['school_id' => $schoolId, 'profile' => $profile, 'mode' => $apply ? 'apply' : 'dry_run',
                'scanned' => 0, 'definitions_added' => 0, 'values_added' => 0, 'files_added' => 0, 'preserved' => 0,
                'next_after' => $after, 'has_more' => false, 'issue_count' => 0, 'issues' => []];
            $fields = DB::transaction(function () use ($schoolId, $profile, $apply, &$report) {
                // Serialize competing mapping creation without updating existing metadata.
                if ($apply) {
                    School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();
                }

                return $this->definitions($profile, $apply, $report);
            });
            [$table] = self::PROFILES[$profile];
            $ids = DB::table($table)->where('school_id', $schoolId)->whereNull('deleted_at')->where('id', '>', $after)
                ->orderBy('id')->limit($limit + 1)->pluck('id');
            $report['has_more'] = $ids->count() > $limit;
            foreach ($ids->take($limit) as $id) {
                $createdPaths = [];
                try {
                    DB::transaction(function () use ($table, $id, $schoolId, $profile, $fields, $apply, $files, &$report, &$createdPaths) {
                        $this->person($table, (int) $id, $schoolId, $profile, $fields, $apply, $files, $report, $createdPaths);
                    });
                } catch (Throwable $error) {
                    foreach ($createdPaths as $path) {
                        Storage::disk(ProfileFiles::DISK)->delete($path);
                    }
                    throw $error;
                }
                $report['scanned']++;
                $report['next_after'] = (int) $id;
            }

            return $report;
        });
    }

    private function definitions(string $profile, bool $apply, array &$report): array
    {
        $legacy = FormField::query()->where('for', self::PROFILES[$profile][1])->orderBy('id')->get();
        $names = $legacy->groupBy(fn ($field) => str_replace(' ', '_', $field->name));
        $fields = [];
        foreach ($legacy as $old) {
            $name = str_replace(' ', '_', $old->name);
            if ($names->get($name)->count() !== 1) {
                $this->issue($report, 'ambiguous_field_name', fieldId: (int) $old->id);

                continue;
            }
            $existing = ProfileFieldDefinition::query()->withTrashed()->where('legacy_form_field_id', $old->id)->first();
            if ($existing !== null) {
                if ($existing->trashed() || $existing->profile_type !== $profile || $existing->source !== 'custom'
                    || ! $existing->encrypted || ! $existing->category()->exists()) {
                    $this->issue($report, 'mapping_unavailable', fieldId: (int) $old->id);

                    continue;
                }
                $fields[$name] = $existing;

                continue;
            }
            $options = [];
            try {
                if (in_array($old->type, ['dropdown', 'radio', 'checkbox'], true)) {
                    $options = json_decode($old->default_values ?? '', true, 32, JSON_THROW_ON_ERROR);
                    if (! is_array($options) || ! array_is_list($options)) {
                        throw new JsonException;
                    }
                }
            } catch (JsonException) {
                $this->issue($report, 'invalid_options', fieldId: (int) $old->id);

                continue;
            }
            $data = ['category_id' => 1, 'key' => 'legacy_'.$old->id, 'label' => str_replace('_', ' ', $old->name),
                'type' => $old->type, 'options' => $options, 'validation' => [], 'required' => (bool) $old->is_required,
                'visible_to' => $profile === 'learner' ? ['owner', 'guardian', 'school_admin'] : ['owner', 'school_admin'],
                'editable_by' => $profile === 'learner' ? ['guardian', 'school_admin'] : ['owner', 'school_admin'],
                'hidden' => true, 'sensitive' => true, 'encrypted' => true, 'sort_order' => max(0, min(100000, (int) $old->rank))];
            $validator = Validator::make($data, ProfileDefinitions::fieldRules());
            if ($validator->fails() || (in_array($old->type, ['dropdown', 'radio', 'checkbox'], true) && $options === [])) {
                $this->issue($report, 'invalid_definition', fieldId: (int) $old->id);

                continue;
            }
            $category = ProfileFieldCategory::query()->withTrashed()->where('profile_type', $profile)->where('key', 'legacy_import')->first();
            $keyTaken = ProfileFieldDefinition::query()->withTrashed()->where('profile_type', $profile)->where('key', $data['key'])->exists();
            if ($category?->trashed() || $keyTaken) {
                $this->issue($report, 'mapping_conflict', fieldId: (int) $old->id);

                continue;
            }
            if ($apply && $category === null) {
                $category = ProfileFieldCategory::query()->create(['profile_type' => $profile, 'key' => 'legacy_import',
                    'label' => 'Imported fields', 'sort_order' => 1000]);
            }
            $field = new ProfileFieldDefinition($data + ['profile_type' => $profile, 'source' => 'custom', 'legacy_form_field_id' => $old->id]);
            if ($apply) {
                $field->category_id = $category->id;
                $field->save();
            }
            $fields[$name] = $field;
            $report['definitions_added']++;
        }

        return $fields;
    }

    private function person(string $table, int $id, int $schoolId, string $profile, array $fields, bool $apply, bool $files, array &$report, array &$createdPaths): void
    {
        $source = DB::table($table)->where('school_id', $schoolId)->whereNull('deleted_at')->where('id', $id)->first();
        if ($source === null) {
            return;
        }
        // Live writes lock the user before field definitions. Do not invert that order.
        $person = User::query()->whereKey($source->user_id)->when($apply, fn ($query) => $query->lockForUpdate())->first();
        if ($person === null || ! DB::table('school_memberships')->where('school_id', $schoolId)->where('user_id', $source->user_id)
            ->where('role', self::PROFILES[$profile][2])->exists()) {
            $this->issue($report, 'person_school_mismatch', $id);

            return;
        }
        if (DB::table($table)->where('school_id', $schoolId)->where('user_id', $person->id)->whereNull('deleted_at')->count() !== 1) {
            $this->issue($report, 'ambiguous_person_records', $id);

            return;
        }
        $source = DB::table($table)->where('school_id', $schoolId)->whereNull('deleted_at')->where('id', $id)
            ->when($apply, fn ($query) => $query->lockForUpdate())->first();
        if ($source === null || (int) $source->user_id !== (int) $person->id) {
            $this->issue($report, 'source_changed', $id);

            return;
        }
        try {
            $values = $this->decode($source->dynamic_fields);
        } catch (JsonException) {
            $this->issue($report, 'invalid_dynamic_fields', $id);

            return;
        }
        $added = 0;
        foreach ($values as $name => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (! isset($fields[$name])) {
                $this->issue($report, 'unmapped_value', $id);

                continue;
            }
            $added += $this->value($person, $fields[$name], $value, $id, $apply, $files, $report, $createdPaths);
        }
        if ($profile === 'learner' && $source->religion !== null && $source->religion !== '') {
            $religion = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'religion')
                ->where('source', 'custom')->where('encrypted', true)->first();
            if ($religion === null) {
                $this->issue($report, 'religion_definition_unavailable', $id);
            } else {
                $added += $this->value($person, $religion, $source->religion, $id, $apply, $files, $report, $createdPaths);
            }
        }
        if ($apply && $added > 0) {
            Audit::record('profile.legacy_backfilled', $person, [], ['profile_type' => $profile, 'values_added' => $added], schoolId: $schoolId);
            Outbox::record(new ContextChanged($schoolId, 'profile.values'));
        }
    }

    private function value(User $person, ProfileFieldDefinition $mapped, mixed $value, int $recordId, bool $apply, bool $files, array &$report, array &$createdPaths): int
    {
        $field = $mapped;
        if ($mapped->exists && $apply) {
            $field = ProfileFieldDefinition::query()->whereKey($mapped->id)->lockForUpdate()->first();
            if ($field === null || ! $field->category()->exists() || ! $field->encrypted || $field->source !== 'custom') {
                $this->issue($report, 'mapping_changed', $recordId, (int) $mapped->legacy_form_field_id);

                return 0;
            }
        }
        $existing = $field->exists ? DB::table('profile_field_values')->where('school_id', app(CurrentSchool::class)->id())
            ->where('user_id', $person->id)->where('definition_id', $field->id)->first(['id', 'encrypted']) : null;
        if ($existing !== null) {
            $report['preserved']++;
            if (! $existing->encrypted) {
                $this->issue($report, 'existing_storage_invalid', $recordId, (int) $field->legacy_form_field_id);
            }

            return 0;
        }
        if ($field->type === 'file') {
            if (! $files) {
                $this->issue($report, 'private_file_migration_pending', $recordId, (int) $field->legacy_form_field_id);

                return 0;
            }
            $prepared = app(LegacyProfileFiles::class)->prepare($value, $field->profile_type);
            if (is_string($prepared)) {
                $this->issue($report, $prepared, $recordId, (int) $field->legacy_form_field_id);

                return 0;
            }
            if ($apply) {
                $file = app(LegacyProfileFiles::class)->copy($person, $field, $prepared);
                $createdPaths[] = $file->path;
                $value = $file->id;
            }
            $report['files_added']++;
        }
        if (in_array($field->type, ['text', 'textarea', 'number', 'dropdown', 'radio', 'date'], true) && is_int($value)) {
            $value = (string) $value;
        }
        try {
            if ($field->type !== 'file') {
                $value = app(ProfileForms::class)->validateValue($person, $field, $value);
            }
        } catch (ValidationException) {
            $this->issue($report, 'invalid_value', $recordId, (int) $field->legacy_form_field_id);

            return 0;
        }
        if ($apply) {
            DB::table('profile_field_values')->insert(['school_id' => app(CurrentSchool::class)->id(), 'user_id' => $person->id,
                'definition_id' => $field->id, 'value' => Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR)),
                'encrypted' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $report['values_added']++;

        return 1;
    }

    /** Both legacy list-of-maps and flat maps occur in installed data. */
    private function decode(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (! is_array($data)) {
            throw new JsonException;
        }
        $maps = array_is_list($data) ? $data : [$data];
        $flat = [];
        foreach ($maps as $map) {
            if (! is_array($map)) {
                throw new JsonException;
            }
            foreach ($map as $key => $value) {
                if (! is_string($key) || (array_key_exists($key, $flat) && $flat[$key] !== $value)) {
                    throw new JsonException;
                }
                $flat[$key] = $value;
            }
        }

        return $flat;
    }

    private function issue(array &$report, string $code, ?int $recordId = null, ?int $fieldId = null): void
    {
        $report['issue_count']++;
        if (count($report['issues']) < 100) {
            $report['issues'][] = ['code' => $code, 'record_id' => $recordId, 'field_id' => $fieldId];
        }
    }
}
