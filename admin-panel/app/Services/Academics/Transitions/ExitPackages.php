<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Jobs\Academics\GenerateExitPackage;
use App\Models\Academics\AcademicExitPackage;
use App\Models\Academics\AcademicTransition;
use App\Models\Platform\RetentionRule;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\TransitionPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExitPackages
{
    public function preview(User $actor, array $input): array
    {
        return DB::transaction(function () use ($actor, $input) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $source = app(ExitEvidence::class)->collect($actor, $input);

            return $this->previewData($source);
        });
    }

    private function previewData(array $source): array
    {
        $data = $source['blueprint'];

        return ['selection' => $data['selection'], 'learner_id' => $data['learner_id'], 'learner_name' => $data['learner_name'], 'last_attended_on' => $data['last_attended_on'], 'attendance_through' => $data['attendance_through'],
            'reports' => $data['reports'], 'documents' => $data['documents'], 'released_mark_count' => count($data['released_marks']), 'attendance_count' => count($data['attendance']), 'enrolment_count' => count($data['enrolments']),
            'fingerprint' => $this->reviewedFingerprint($source['fingerprint'], app(TransitionPolicies::class)->retention()), 'can_prepare' => $source['can_prepare'], 'issues' => $source['issues'], 'estimated_bytes' => $source['estimated_bytes'], 'retention_days' => app(TransitionPolicies::class)->retention()['days']];
    }

    private function reviewedFingerprint(string $source, array $retention): string
    {
        return hash('sha256', json_encode([$source, $retention], JSON_THROW_ON_ERROR));
    }

    public function prepare(User $actor, array $input): array
    {
        $selection = app(ExitEvidence::class)->selection($actor, $input);
        $write = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']])->validate();
        $hash = hash('sha256', json_encode([$selection, $write['fingerprint']], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $selection, $write, $hash) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $leave = AcademicTransition::where('kind', 'leave')->findOrFail($selection['transition_id']);
            app(TransitionPolicy::class)->export($actor, (int) $leave->learner_id);
            $previous = AcademicExitPackage::where('requested_by', $actor->id)->where('operation_id', $write['operation_id'])->first();
            if ($previous !== null) {
                abort_unless(hash_equals($previous->request_hash, $hash), 409, __('This export operation already has a different selection. Review before retrying.'));

                return $this->data($actor, $previous);
            }
            $source = app(ExitEvidence::class)->collect($actor, $selection);
            $retention = app(TransitionPolicies::class)->retention();
            abort_unless($source['can_prepare'] && hash_equals($this->reviewedFingerprint($source['fingerprint'], $retention), $write['fingerprint']), 409, __('Exit evidence or copy retention changed, or a selected file is unavailable. Review the current export.'));
            app(TransitionPolicies::class)->retainHistory();
            RetentionRule::firstOrCreate(['record_type' => 'academic_exit_packages'], ['action' => 'expire_artifact', 'days' => $retention['days'], 'revision' => 1]);
            $row = AcademicExitPackage::create(['academic_transition_id' => $leave->id, 'learner_id' => $leave->learner_id, 'requested_by' => $actor->id, 'operation_id' => $write['operation_id'],
                'generation_id' => (string) Str::uuid(), 'request_hash' => $hash, 'source_fingerprint' => $source['fingerprint'], 'state' => 'queued', 'blueprint' => $source['blueprint'], 'expires_at' => now()->addDays($retention['days'])]);
            Audit::record('academics.exit_package_requested', $row, [], ['learner_id' => (int) $leave->learner_id, 'transition_id' => (int) $leave->id, 'expires_at' => $row->expires_at->toISOString()]);
            GenerateExitPackage::dispatch(app(CurrentSchool::class)->id(), (int) $row->id, $row->generation_id)->afterCommit();

            return $this->data($actor, $row);
        }, 3);
    }

    public function retry(User $actor, int $id): array
    {
        app(TransitionPolicy::class)->package($actor, $id);

        return DB::transaction(function () use ($actor, $id) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $row = app(TransitionPolicy::class)->package($actor, $id);
            abort_if($row->expires_at->lte(now()) || $row->state === 'expired', 409, __('This export copy has expired. Prepare a new export.'));
            if (in_array($row->state, ['queued', 'ready'], true)) {
                return $this->data($actor, $row);
            }
            $source = app(ExitEvidence::class)->collect($actor, $row->blueprint['selection']);
            abort_unless($source['can_prepare'] && hash_equals($row->source_fingerprint, $source['fingerprint']), 409, __('The source changed. Preview and prepare a new export.'));
            $row->forceFill(['state' => 'queued', 'failure_code' => null, 'generation_id' => (string) Str::uuid()])->save();
            GenerateExitPackage::dispatch(app(CurrentSchool::class)->id(), $id, $row->generation_id)->afterCommit();
            Audit::record('academics.exit_package_retried', $row);

            return $this->data($actor, $row);
        });
    }

    public function data(User $actor, AcademicExitPackage $row): array
    {
        app(TransitionPolicy::class)->package($actor, (int) $row->id);
        $ready = $row->state === 'ready' && $row->expires_at->isFuture();

        return ['id' => (int) $row->id, 'transition_id' => (int) $row->academic_transition_id, 'learner_id' => (int) $row->learner_id, 'state' => $row->expires_at->lte(now()) ? 'expired' : $row->state,
            'failure_code' => $row->failure_code, 'expires_at' => $row->expires_at->toISOString(), 'generated_at' => $row->generated_at?->toISOString(), 'artifact_sha256' => $row->artifact_sha256, 'artifact_bytes' => $row->artifact_bytes,
            'signing_public_key' => $row->signing_public_key, 'signing_key_id' => $row->signing_key_id, 'download_url' => $ready ? URL::temporarySignedRoute('api.v2.academics.transitions.export-download', now()->addMinutes(10)->min($row->expires_at),
                ['package' => $row->id, 'reader' => $actor->id, 'school' => app(CurrentSchool::class)->id()]) : null];
    }
}
