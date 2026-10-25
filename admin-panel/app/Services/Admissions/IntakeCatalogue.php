<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Academics\GradeLevel;
use App\Models\Admissions\Application;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IntakeCatalogue
{
    public function save(User $actor, array $input, ?int $id = null): Intake
    {
        app(IntakeAuthority::class)->staff($actor, true);
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'],
            'state' => ['required', 'in:draft,open,closed'], 'opens_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'closes_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:opens_at'],
            'application_fee_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'keep_for_days' => ['required', 'integer', 'min:1', 'max:'.(int) config('admissions.maximum_keep_days')],
            'field_ids' => ['required', 'array', 'min:3', 'max:80'], 'field_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'required_documents' => ['present', 'array', 'max:12'], 'required_documents.*' => ['required', 'string', 'distinct', 'regex:/^[a-z][a-z0-9_]{0,63}$/D'],
            'grades' => ['required', 'array', 'min:1', 'max:30'], 'grades.*.grade_level_id' => ['required', 'integer', 'distinct', 'min:1'], 'grades.*.places' => ['required', 'integer', 'min:0', 'max:10000'],
        ])->validate();
        $data['target_id'] = $id;
        $receipt = app(AdmissionOperations::class)->once($actor, 'intake.save', $data, function () use ($data, $id, $actor): array {
            $year = SessionYear::findOrFail($data['session_year_id']);
            abort_if($year->start_date === null || $year->end_date === null, 422, 'Configure the intake school year first.');
            $ids = array_map('intval', $data['field_ids']);
            $fields = app(ApplicationForms::class)->selectable()->whereIn('id', $ids);
            abort_unless($fields->count() === count($ids), 422, 'Choose available applicant profile fields.');
            if ($fields->contains(fn ($field) => $field->sensitive)) {
                abort_unless(app(ProfilePolicy::class)->permission($actor, 'profile-sensitive-view'), 403);
            }
            foreach (['user.first_name', 'user.last_name', 'user.dob'] as $source) {
                abort_unless($fields->where('profile_type', 'learner')->where('source', $source)->count() === 1, 422, 'Include the learner name and date of birth fields.');
            }
            $row = $id === null ? new Intake : Intake::lockForUpdate()->findOrFail($id);
            abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'], 409, 'Compare the current intake before saving.');
            if ($row->exists && Application::where('intake_id', $row->id)->exists()) {
                abort_unless((int) $row->session_year_id === (int) $data['session_year_id'] && $row->field_ids === $ids
                    && (int) $row->application_fee_cents === (int) $data['application_fee_cents'] && $row->required_documents === array_values($data['required_documents']) && hash_equals($row->form_fingerprint, app(ApplicationForms::class)->fingerprint($fields)), 409, 'Keep the submitted form, fee and school year stable; create a new intake for a different form.');
            }
            $row->fill(array_diff_key($data, array_flip(['operation_id', 'target_id', 'grades', 'revision'])) + ['revision' => (int) ($row->revision ?? 0) + 1]);
            foreach (['opens_at', 'closes_at'] as $key) {
                $row->setAttribute($key, CarbonImmutable::parse($data[$key], app(CurrentSchool::class)->school()->timezone)->utc());
            }
            $restricted = $fields->where('sensitive', true)->pluck('id')->map(fn ($value) => (int) $value)->all();
            $row->restricted_field_ids = array_values(array_unique(array_merge($row->exists ? ($row->restricted_field_ids ?? []) : [], $restricted)));
            $row->field_ids = $ids;
            $row->form_fingerprint = app(ApplicationForms::class)->fingerprint($fields);
            if (! $row->exists) {
                $row->public_id = (string) Str::uuid();
            }
            $row->save();
            $kept = [];
            foreach ($data['grades'] as $grade) {
                GradeLevel::findOrFail($grade['grade_level_id']);
                $slot = IntakeGrade::firstOrNew(['intake_id' => $row->id, 'grade_level_id' => (int) $grade['grade_level_id']]);
                if ($slot->exists) {
                    $reserved = app(AdmissionOffers::class)->reserved((int) $slot->id);
                    abort_if((int) $grade['places'] < $reserved, 409, 'Capacity cannot be reduced below existing reserved places.');
                }
                $slot->fill(['places' => (int) $grade['places'], 'revision' => (int) ($slot->revision ?? 0) + 1])->save();
                $kept[] = (int) $slot->id;
            }
            foreach (IntakeGrade::where('intake_id', $row->id)->whereNotIn('id', $kept)->get() as $removed) {
                abort_if(Application::where('intake_grade_id', $removed->id)->exists(), 409, 'Retain grades with existing applications.');
                $removed->delete();
            }
            Audit::record('admissions.intake_saved', $row, [], ['revision' => $row->revision]);

            return ['id' => (int) $row->id];
        });

        return Intake::findOrFail($receipt['id']);
    }
}
