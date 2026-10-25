<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationDocument;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Applications
{
    public function contact(array $input): array
    {
        $data = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:128'], 'last_name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:191'], 'mobile' => ['nullable', 'string', 'max:64'],
            'relationship' => ['required', 'string', 'max:32'],
        ])->validate();
        $data['email'] = strtolower(trim($data['email']));

        return $data;
    }

    /** The same form nonce and secret make an unknown initial save retry safe. */
    public function begin(Intake $intake, int $gradeId, string $nonce, string $key, array $contact, array $answers): Application
    {
        app(IntakeAuthority::class)->open($intake);
        abort_unless(Str::isUuid($nonce) && strlen($key) === 64 && preg_match('/^[a-f0-9]{64}$/D', $key) === 1, 422);
        $parent = $this->contact($contact);
        $values = app(ApplicationForms::class)->validate($intake, $answers, false);
        $hash = hash_hmac('sha256', json_encode([$intake->id, $gradeId, $parent, $values], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($intake, $gradeId, $nonce, $key, $parent, $values, $hash): Application {
            $current = Intake::lockForUpdate()->findOrFail($intake->id);
            app(IntakeAuthority::class)->open($current);
            $grade = IntakeGrade::where('intake_id', $current->id)->findOrFail($gradeId);
            $existing = Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at', 'draft_key_hash', 'creation_hash'])->where('public_id', $nonce)->lockForUpdate()->first();
            if ($existing !== null) {
                abort_unless(hash_equals($existing->draft_key_hash, hash('sha256', $key)) && hash_equals($existing->creation_hash, $hash), 409, 'This application form was already saved with different details.');

                abort_if(app(IntakeRetention::class)->due($existing), 404);

                return Application::findOrFail($existing->id);
            }
            $row = Application::create(['public_id' => $nonce, 'intake_id' => $current->id, 'intake_grade_id' => $grade->id, 'state' => 'draft', 'answers' => $values, 'parent_contact' => $parent,
                'email_hash' => app(ApplicationLinks::class)->emailHash($parent['email']), 'draft_key_hash' => hash('sha256', $key), 'creation_hash' => $hash,
                'office_fee_cents' => $current->application_fee_cents, 'office_fee_paid' => false, 'revision' => 1]);
            app(AdmissionEvents::class)->record($row, 'draft_started');
            app(ApplicationLinks::class)->issue($row);

            return $row;
        }, 3);
    }

    public function save(int $id, string $key, int $revision, array $contact, array $answers): Application
    {
        app(IntakeAuthority::class)->owner($id, $key, true);

        return DB::transaction(function () use ($id, $key, $revision, $contact, $answers): Application {
            $row = Application::lockForUpdate()->findOrFail($id);
            app(IntakeAuthority::class)->owner($id, $key, true);
            $intake = Intake::findOrFail($row->intake_id);
            app(IntakeAuthority::class)->open($intake);
            abort_unless($row->state === 'draft' && (int) $row->revision === $revision, 409, 'Compare the saved application before changing it.');
            $parent = $this->contact($contact);
            $hash = app(ApplicationLinks::class)->emailHash($parent['email']);
            $changedEmail = ! hash_equals($row->email_hash, $hash);
            $row->update(['parent_contact' => $parent, 'answers' => app(ApplicationForms::class)->validate($intake, $answers, false), 'email_hash' => $hash,
                'email_verified_at' => $changedEmail ? null : $row->email_verified_at, 'revision' => $row->revision + 1]);
            app(AdmissionEvents::class)->record($row, 'draft_saved');
            if ($changedEmail) {
                app(ApplicationLinks::class)->issue($row);
            }

            return $row;
        }, 3);
    }

    public function submit(int $id, string $key, int $revision): Application
    {
        app(IntakeAuthority::class)->owner($id, $key, true);

        return DB::transaction(function () use ($id, $key, $revision): Application {
            $row = Application::lockForUpdate()->findOrFail($id);
            app(IntakeAuthority::class)->owner($id, $key, true);
            if ($row->state === 'submitted' && (int) $row->revision === $revision + 1) {
                return $row;
            }
            abort_unless($row->state === 'draft' && (int) $row->revision === $revision, 409, 'Compare the saved application before submitting it.');
            $intake = Intake::findOrFail($row->intake_id);
            app(IntakeAuthority::class)->open($intake);
            abort_unless($row->email_verified_at !== null, 422, 'Open the emailed resume link to confirm the application contact first.');
            app(ApplicationForms::class)->validate($intake, $row->answers, true);
            $categories = ApplicationDocument::where('application_id', $row->id)->whereNotNull('scanned_at')->pluck('category')->all();
            abort_if(array_diff($intake->required_documents, $categories) !== [], 422, 'Upload the required documents before submitting.');
            $row->update(['state' => 'submitted', 'submitted_at' => now(), 'revision' => $row->revision + 1]);
            app(AdmissionEvents::class)->record($row, 'submitted');

            return $row;
        }, 3);
    }
}
