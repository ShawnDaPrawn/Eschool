<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\ReportCard;
use App\Models\Attendance;
use App\Models\People\Enrolment;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFile;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterLegacy;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\Academics\Transitions\ExitEvidence;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Academics\Transitions\TransitionContext;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;

class TransferEvidence
{
    public static function rules(): array
    {
        return ['learner_id' => ['required', 'integer', 'min:1'], 'guardian_id' => ['required', 'integer', 'min:1'],
            'to_school_id' => ['present', 'nullable', 'integer', 'min:1'], 'external_school' => ['nullable', 'string', 'max:160'],
            'last_attended_on' => ['required', 'date_format:Y-m-d'], 'starts_on' => ['required', 'date_format:Y-m-d', 'after:last_attended_on'],
            'report_ids' => ['present', 'array', 'max:'.config('school_transfers.maximum_reports')], 'report_ids.*' => ['integer', 'min:1', 'distinct'],
            'document_ids' => ['present', 'array', 'max:'.config('school_transfers.maximum_documents')], 'document_ids.*' => ['uuid', 'distinct']];
    }

    public function selection(User $actor, array $input): array
    {
        app(TransferAuthority::class)->staff($actor, true);
        $d = Validator::make($input, self::rules())->validate();
        foreach (['learner_id', 'guardian_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $d['to_school_id'] = $d['to_school_id'] === null ? null : (int) $d['to_school_id'];
        abort_if($d['to_school_id'] === app(CurrentSchool::class)->id(), 422, 'Choose a different receiving school.');
        if ($d['to_school_id'] !== null) {
            School::active()->findOrFail($d['to_school_id']);
            $d['external_school'] = null;
        } else {
            abort_unless(is_string($d['external_school'] ?? null) && trim($d['external_school']) !== '', 422, 'Name the receiving school.');
            $d['external_school'] = trim($d['external_school']);
        }
        $d['report_ids'] = array_map('intval', $d['report_ids']);
        sort($d['report_ids']);
        sort($d['document_ids']);

        return $d;
    }

    /** Frozen academic history and approved document metadata; never clinical domains. */
    public function collect(User $actor, array $selection): array
    {
        $d = $this->selection($actor, $selection);
        $learner = Students::with('user')->findOrFail($d['learner_id']);
        abort_if($learner->user === null, 409, 'Restore the learner identity before preparing a transfer.');
        $guardian = User::findOrFail($d['guardian_id']);
        app(TransferAuthority::class)->guardian($guardian, (int) $learner->id);
        $cover = Enrolment::with('homeroom.class')->where('learner_id', $learner->id)->onDate($d['last_attended_on'])->first();
        abort_if($cover === null, 409, 'No source enrolment covers the last attended date.');
        $departed = app(LearnerAcademicAccess::class)->departed($learner, $d['starts_on']);
        $today = app(AcademicAuthority::class)->today();
        $newerEpisode = Enrolment::where('learner_id', $learner->id)->whereDate('starts_on', '>', $d['last_attended_on'])->whereDate('starts_on', '<=', $today)->exists();
        abort_if($d['to_school_id'] !== null && ($departed || $newerEpisode), 409, 'Choose the current sending school and enrolment episode for a new transfer.');
        $exportOnly = $d['to_school_id'] === null && ($departed || $newerEpisode);
        $state = app(TransitionContext::class)->state((int) $learner->id);
        $reports = ReportCard::with('batch')->where('learner_id', $learner->id)->whereIn('id', $d['report_ids'])
            ->whereHas('batch', fn ($q) => $q->where('state', 'released')->whereColumn('report_batches.fingerprint', 'report_cards.generated_fingerprint'))->orderBy('id')->get();
        abort_unless($reports->count() === count($d['report_ids']), 404);
        $reportData = [];
        foreach ($reports as $report) {
            abort_unless(app(ReportArtifacts::class)->available($report, $report->batch->fingerprint), 409, 'A selected report needs restoration.');
            $reportData[] = ['id' => (int) $report->id, 'sha256' => $report->pdf_sha256, 'year_name' => $report->payload['year_name'], 'term_name' => $report->payload['term_name'], 'released_at' => $report->batch->released_at->toISOString()];
        }
        $documents = $this->documents($actor, $guardian, $learner, $d['document_ids']);
        $entries = MarkReleaseEntry::with('release', 'snapshot')->where('learner_id', $learner->id)->orderBy('id')->limit(10001)->get();
        abort_if($entries->count() > 10000, 422, 'This history needs a scoped export.');
        $subjects = Subject::withTrashed()->whereIn('id', $entries->map(fn ($r) => $r->release->subject_id))->get()->keyBy('id');
        $marks = $entries->map(function ($entry) use ($subjects) {
            $r = $entry->release;
            $s = $entry->snapshot;
            $running = $r->mode === 'running';

            return ['release_id' => (int) $r->id, 'release_version' => $r->version, 'subject_name' => $subjects->get($r->subject_id)?->name ?? __('Archived subject'), 'grade_level_id' => (int) $r->grade_level_id,
                'session_year_id' => (int) $r->session_year_id, 'term_id' => $r->term_id === null ? null : (int) $r->term_id, 'mode' => $r->mode,
                'percentage_bp' => $running ? $s->running_bp : $s->percentage_bp, 'rating_level' => $running ? ($s->result['running_rating_level'] ?? null) : $s->rating_level,
                'coverage_bp' => $s->coverage_bp, 'fingerprint' => $s->fingerprint, 'released_at' => $r->created_at->toISOString()];
        })->all();
        $contents = ['schema_version' => 1, 'export_only' => $exportOnly, 'from_school_id' => app(CurrentSchool::class)->id(), 'from_school_name' => app(CurrentSchool::class)->school()->name,
            'to_school_id' => $d['to_school_id'], 'to_school_name' => $d['to_school_id'] === null ? $d['external_school'] : School::findOrFail($d['to_school_id'])->name,
            'source_enrolment_fingerprint' => hash('sha256', json_encode($state, JSON_THROW_ON_ERROR)), 'source_learner_id' => (int) $learner->id, 'learner_user_id' => (int) $learner->user_id, 'learner_name' => $learner->user->full_name, 'student_number' => $learner->student_number,
            'guardian_id' => (int) $guardian->id, 'guardian_name' => $guardian->full_name, 'session_year_id' => (int) $cover->session_year_id,
            'last_attended_on' => $d['last_attended_on'], 'starts_on' => $d['starts_on'], 'selection' => $d, 'reports' => $reportData, 'documents' => $documents,
            'released_marks' => $marks, 'attendance' => $this->attendance((int) $learner->id, $d['last_attended_on'])];

        return ['contents' => $contents, 'source_state_hash' => hash('sha256', json_encode($state, JSON_THROW_ON_ERROR))];
    }

    public function documents(User $actor, User $guardian, Students $learner, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $choices = collect(app(ExitEvidence::class)->documents($actor, $learner))->keyBy('id');
        $p = app(ProfilePolicy::class);
        $parent = $p->resolve($guardian, $learner->user, 'learner');
        $rows = [];
        foreach ($ids as $id) {
            $choice = $choices->get($id);
            abort_if($choice === null, 404);
            $definition = ProfileFieldDefinition::findOrFail($choice['definition_id']);
            abort_unless($p->field($parent, $definition), 404);
            $rows[] = $choice;
        }

        return $rows;
    }

    /** Read already-scanned, currently permitted file bytes at bundle creation. */
    public function file(User $actor, array $contents, string $kind, array $item): array
    {
        if ($kind === 'report') {
            $card = ReportCard::with('batch')->where('learner_id', $contents['source_learner_id'])->findOrFail($item['id']);
            abort_unless($card->batch->state === 'released' && hash_equals($item['sha256'], $card->pdf_sha256), 409);

            return ['bytes' => app(ReportArtifacts::class)->read($card), 'path' => 'reports/'.$card->id.'.pdf'];
        }
        $learner = Students::with('user')->findOrFail($contents['source_learner_id']);
        $guardian = User::findOrFail($contents['guardian_id']);
        $this->documents($actor, $guardian, $learner, [$item['id']]);
        $file = ProfileFile::where('user_id', $learner->user_id)->findOrFail($item['id']);
        $read = app(ProfileFiles::class)->read($actor, $learner->user, $file->id.'.'.$file->extension);

        return ['bytes' => $read['bytes'], 'path' => 'documents/'.$file->id.'.'.$file->extension];
    }

    private function attendance(int $learner, string $through): array
    {
        $rows = [];
        $legacy = Attendance::where('student_id', $learner)->whereDate('date', '<=', $through)->orderBy('id')->limit(20001)->get();
        $canonical = AttendanceEntry::with('register')->where('learner_id', $learner)->whereHas('register', fn ($q) => $q->whereDate('on_date', '<=', $through))->orderBy('id')->limit(20001)->get();
        abort_if($legacy->count() > 20000 || $canonical->count() > 20000, 422, 'This attendance history needs a scoped export.');
        foreach ($legacy as $r) {
            $date = substr((string) $r->date, 0, 10);
            $rows[$date.':homeroom'] = ['on_date' => $date, 'kind' => 'homeroom', 'state' => app(RegisterLegacy::class)->state((int) $r->type)];
        }
        foreach ($canonical as $r) {
            $date = $r->register->on_date->toDateString();
            $lesson = $r->register->timetable_lesson_id;
            $rows[$date.':'.($lesson ?? 'homeroom')] = ['on_date' => $date, 'kind' => $lesson === null ? 'homeroom' : 'period', 'state' => $r->state];
        }
        ksort($rows);

        return array_values($rows);
    }
}
