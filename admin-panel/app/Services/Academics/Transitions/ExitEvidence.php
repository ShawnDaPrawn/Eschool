<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicTransition;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\ReportCard;
use App\Models\Attendance;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\ProfileFile;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\TransitionPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterLegacy;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExitEvidence
{
    public static function rules(): array
    {
        return ['transition_id' => ['required', 'integer', 'min:1'], 'report_ids' => ['present', 'array', 'max:'.config('academic_transitions.maximum_export_reports')], 'report_ids.*' => ['integer', 'min:1', 'distinct'],
            'document_ids' => ['present', 'array', 'max:'.config('academic_transitions.maximum_export_documents')], 'document_ids.*' => ['required', 'uuid', 'distinct']];
    }

    public function selection(User $actor, array $input): array
    {
        $data = Validator::make($input, self::rules())->validate();
        $leave = AcademicTransition::where('kind', 'leave')->findOrFail((int) $data['transition_id']);
        app(TransitionPolicy::class)->export($actor, (int) $leave->learner_id);
        $reports = array_map('intval', $data['report_ids']);
        sort($reports);
        $documents = $data['document_ids'];
        sort($documents);

        return ['transition_id' => (int) $leave->id, 'report_ids' => $reports, 'document_ids' => $documents];
    }

    /** Current permitted documents only: old/replaced attachments are never exported. */
    public function documents(User $actor, Students $learner): array
    {
        $policy = app(ProfilePolicy::class);
        try {
            $context = $policy->resolve($actor, $learner->user, 'learner');
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof ModelNotFoundException || in_array($error->getStatusCode(), [403, 404], true)) {
                return [];
            } throw $error;
        }
        $choices = [];
        foreach (ProfileFieldDefinition::where('profile_type', 'learner')->where('type', 'file')->whereHas('category')->get() as $definition) {
            if (! $policy->field($context, $definition)) {
                continue;
            }
            $value = ProfileFieldValue::where('user_id', $learner->user_id)->where('definition_id', $definition->id)->first();
            if ($value === null || ! $value->encrypted || $value->value === null) {
                continue;
            }
            try {
                $id = json_decode(Crypt::decryptString($value->value), true, flags: JSON_THROW_ON_ERROR);
            } catch (DecryptException|\JsonException) {
                continue;
            }
            if (! is_string($id)) {
                continue;
            }
            $file = ProfileFile::where('user_id', $learner->user_id)->where('definition_id', $definition->id)->find($id);
            if ($file === null) {
                continue;
            }
            $choices[] = ['id' => $file->id, 'definition_id' => (int) $definition->id, 'name' => $file->name, 'mime' => $file->mime, 'extension' => $file->extension, 'bytes' => (int) $file->size];
        }

        return $choices;
    }

    public function collect(User $actor, array $input): array
    {
        $selection = $this->selection($actor, $input);
        $leave = AcademicTransition::findOrFail($selection['transition_id']);
        $learner = app(TransitionPolicy::class)->export($actor, (int) $leave->learner_id);
        abort_if($learner->user === null, 409, __('Restore the learner identity before exporting.'));
        $today = app(AcademicAuthority::class)->today();
        $through = min($today, $leave->effective_on->toDateString());
        $reports = ReportCard::with('batch')->where('learner_id', $learner->id)->whereIn('id', $selection['report_ids'])->whereHas('batch', fn ($q) => $q->where('state', 'released')->whereColumn('report_batches.fingerprint', 'report_cards.generated_fingerprint'))->orderBy('id')->get();
        abort_unless($reports->count() === count($selection['report_ids']), 404);
        $reportData = [];
        $issues = [];
        $total = 0;
        foreach ($reports as $report) {
            if (! app(ReportArtifacts::class)->available($report, $report->batch->fingerprint)) {
                $issues[] = ['type' => 'report_unavailable', 'id' => (string) $report->id];
            }
            $size = $report->pdf_path !== null && Storage::disk(ReportArtifacts::DISK)->exists($report->pdf_path) ? Storage::disk(ReportArtifacts::DISK)->size($report->pdf_path) : 0;
            $total += $size;
            $reportData[] = ['id' => (int) $report->id, 'sha256' => $report->pdf_sha256, 'fingerprint' => $report->generated_fingerprint, 'year_name' => $report->payload['year_name'], 'term_name' => $report->payload['term_name'], 'released_at' => $report->batch->released_at->toISOString()];
        }
        $documentData = [];
        $choices = $selection['document_ids'] === [] ? collect() : collect($this->documents($actor, $learner))->keyBy('id');
        foreach ($selection['document_ids'] as $id) {
            $file = $choices->get($id);
            abort_if($file === null, 404);
            $total += $file['bytes'];
            $row = ProfileFile::findOrFail($id);
            $disk = Storage::disk(ProfileFiles::DISK);
            $valid = $row->path === app(CurrentSchool::class)->storagePath('profile-files').'/'.$row->id.'.enc' && $disk->exists($row->path)
                && $disk->size($row->path) <= ProfileFiles::MAX_KB * 4096;
            $file['source_sha256'] = null;
            if ($valid) {
                try {
                    $cipher = $disk->get($row->path);
                    $plain = Crypt::decryptString($cipher);
                    $valid = strlen($plain) === $file['bytes'];
                    $file['source_sha256'] = $valid ? hash('sha256', $cipher) : null;
                } catch (DecryptException) {
                    $valid = false;
                }
            }
            $documentData[] = $file;
            if (! $valid) {
                $issues[] = ['type' => 'document_unavailable', 'id' => $id];
            }
        }
        $entries = MarkReleaseEntry::with('release', 'snapshot')->where('learner_id', $learner->id)->orderBy('id')->limit(10001)->get();
        abort_if($entries->count() > 10000, 422, __('This history needs a scoped export. Contact the school data administrator.'));
        $subjects = Subject::withTrashed()->whereIn('id', $entries->map(fn ($e) => $e->release->subject_id))->get()->keyBy('id');
        $marks = $entries->map(function ($entry) use ($subjects) {
            $release = $entry->release;
            $snapshot = $entry->snapshot;
            $running = $release->mode === 'running';

            return ['release_id' => (int) $release->id, 'release_version' => $release->version, 'subject_id' => (int) $release->subject_id, 'subject_name' => $subjects->get($release->subject_id)?->name ?? __('Archived subject'),
                'grade_level_id' => (int) $release->grade_level_id, 'session_year_id' => (int) $release->session_year_id, 'term_id' => $release->term_id === null ? null : (int) $release->term_id, 'mode' => $release->mode,
                'percentage_bp' => $running ? $snapshot->running_bp : $snapshot->percentage_bp, 'rating_level' => $running ? ($snapshot->result['running_rating_level'] ?? null) : $snapshot->rating_level,
                'coverage_bp' => $snapshot->coverage_bp, 'snapshot_id' => (int) $snapshot->id, 'snapshot_fingerprint' => $snapshot->fingerprint, 'released_at' => $release->created_at->toISOString()];
        })->all();
        $history = array_values(array_filter(app(TransitionContext::class)->state((int) $learner->id)['enrolments'], fn ($e) => $e['starts_on'] <= $leave->effective_on->toDateString() || ($e['cancelled_at'] !== null && $e['cancelled_at'] <= $leave->created_at->toISOString())));
        $blueprint = ['selection' => $selection, 'school_id' => app(CurrentSchool::class)->id(), 'school_name' => app(CurrentSchool::class)->school()->name, 'learner_id' => (int) $learner->id,
            'learner_name' => trim($learner->user->first_name.' '.$learner->user->last_name), 'student_number' => $learner->student_number, 'last_attended_on' => $leave->effective_on->toDateString(),
            'attendance_through' => $through, 'reports' => $reportData, 'documents' => $documentData, 'released_marks' => $marks, 'attendance' => $this->attendance((int) $learner->id, $through), 'enrolments' => $history];
        $estimate = $total + strlen(json_encode($blueprint, JSON_THROW_ON_ERROR)) * 2;
        if ($estimate > (int) config('academic_transitions.maximum_export_bytes')) {
            $issues[] = ['type' => 'export_too_large', 'id' => (string) $leave->id];
        }
        $fingerprint = hash('sha256', json_encode([$blueprint, $issues], JSON_THROW_ON_ERROR));

        return ['blueprint' => $blueprint, 'fingerprint' => $fingerprint, 'can_prepare' => $issues === [], 'issues' => $issues, 'estimated_bytes' => $estimate];
    }

    private function attendance(int $learner, string $through): array
    {
        $rows = [];
        $legacy = Attendance::where('student_id', $learner)->whereDate('date', '<=', $through)->orderBy('id')->limit(20001)->get();
        abort_if($legacy->count() > 20000, 422, __('This attendance history needs a scoped export. Contact the school data administrator.'));
        foreach ($legacy as $entry) {
            $date = substr((string) $entry->date, 0, 10);
            $rows[$date.':homeroom'] = ['on_date' => $date, 'kind' => 'homeroom', 'lesson_id' => null, 'state' => app(RegisterLegacy::class)->state((int) $entry->type), 'revision' => 0];
        }
        $canonical = AttendanceEntry::with('register')->where('learner_id', $learner)->whereHas('register', fn ($q) => $q->whereDate('on_date', '<=', $through))->orderBy('id')->limit(20001)->get();
        abort_if($canonical->count() > 20000, 422, __('This attendance history needs a scoped export. Contact the school data administrator.'));
        foreach ($canonical as $entry) {
            $date = $entry->register->on_date->toDateString();
            $lesson = $entry->register->timetable_lesson_id;
            $rows[$date.':'.($lesson === null ? 'homeroom' : 'lesson:'.$lesson)] = ['on_date' => $date, 'kind' => $lesson === null ? 'homeroom' : 'period', 'lesson_id' => $lesson === null ? null : (int) $lesson, 'state' => $entry->state, 'revision' => $entry->revision];
        }
        ksort($rows);

        return array_values($rows);
    }
}
