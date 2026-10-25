<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Intake;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\User;
use App\Services\People\Profiles\ProfileForms;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Applicant forms reuse actual school profile definitions and their validators. */
class ApplicationForms
{
    public function selectable(): Collection
    {
        return ProfileFieldDefinition::with('category')->whereIn('profile_type', ['learner', 'parent'])->where('hidden', false)->whereHas('category')->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn ($field) => $field->type !== 'file' && $field->source !== 'user.email' && in_array('school_admin', $field->visible_to, true) && in_array('school_admin', $field->editable_by, true) && array_intersect($field->visible_to, ['owner', 'guardian']) !== [] && array_intersect($field->editable_by, ['owner', 'guardian']) !== []);
    }

    public function fingerprint(Collection $fields): string
    {
        $shape = $fields->sortBy('id')->map(fn ($field) => ['id' => (int) $field->id, 'source' => $field->source, 'profile_type' => $field->profile_type, 'type' => $field->type, 'required' => $field->required, 'options' => $field->options, 'validation' => $field->validation])->values()->all();

        return hash('sha256', json_encode($shape, JSON_THROW_ON_ERROR));
    }

    public function definitions(Intake $intake): Collection
    {
        $fields = $this->selectable()->whereIn('id', $intake->field_ids)->keyBy('id');
        abort_unless($fields->count() === count($intake->field_ids), 409, 'The intake form fields changed. The school needs to review this form.');

        abort_unless(hash_equals($intake->form_fingerprint, $this->fingerprint($fields)), 409, 'The selected profile field types or validation changed. Restore the intake definitions or configure a new intake before continuing.');

        return $fields;
    }

    public function validate(Intake $intake, array $input, bool $submit): array
    {
        abort_if(count($input) > 100, 422);
        $fields = $this->definitions($intake);
        $values = [];
        foreach ($input as $key => $value) {
            if (! ctype_digit((string) $key) || ! $fields->has((int) $key)) {
                throw ValidationException::withMessages(['answers' => 'A supplied field is not on this intake form.']);
            }
            $field = clone $fields->get((int) $key);
            if (! $submit) {
                $field->required = false;
            }
            $values[(string) $field->id] = app(ProfileForms::class)->validateValue(new User, $field, $value);
        }
        if ($submit) {
            foreach ($fields as $field) {
                if ($field->required && ! array_key_exists((string) $field->id, $values)) {
                    throw ValidationException::withMessages(['answers.'.$field->id => __('Complete the required field: ').$field->label]);
                }
            }
        }

        return $values;
    }

    public function schema(Intake $intake): array
    {
        return $this->definitions($intake)->map(fn ($field) => ['id' => (int) $field->id, 'profile_type' => $field->profile_type, 'category' => $field->category->label, 'label' => $field->label,
            'type' => $field->type, 'options' => $field->options, 'required' => $field->required, 'validation' => app(ProfileForms::class)->validationFor($field)])->values()->all();
    }
}
