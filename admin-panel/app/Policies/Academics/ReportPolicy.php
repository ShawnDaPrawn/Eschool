<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\ReportBatch;
use App\Models\Academics\ReportCard;
use App\Models\Academics\TeachingAllocation;
use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use Illuminate\Database\Eloquent\Builder;

class ReportPolicy
{
    /** Current school appointments govern report history, including earlier years. */
    public function leads(User $actor, string $kind, int $grade): bool
    {
        return app(AcademicAuthority::class)->currentLeadership($actor, $kind, $grade);
    }

    public function scope(User $actor, int $year, int $roomId): ClassSection
    {
        $authority = app(AcademicAuthority::class);
        $authority->staff($actor);
        SessionYear::findOrFail($year);
        $room = ClassSection::with('class.gradeLevel', 'section')->findOrFail($roomId);
        abort_if($room->class?->grade_level_id === null, 409, __('Map this homeroom to a grade first.'));
        abort_unless($authority->manages($actor) || $this->leads($actor, 'grade_head', (int) $room->class->grade_level_id)
            || $this->leads($actor, 'principal', (int) $room->class->grade_level_id), 403);

        return $room;
    }

    /** History follows the grade saved on the report, never a later room mapping. */
    public function visibleBatches(User $actor): Builder
    {
        $authority = app(AcademicAuthority::class);
        $fresh = $authority->staff($actor);
        $query = ReportBatch::query();
        if ($authority->manages($fresh) || $this->leads($fresh, 'principal', 0)) {
            return $query;
        }
        $grades = AcademicLeadership::where('user_id', $fresh->id)->where('kind', 'grade_head')
            ->whereDate('starts_on', '<=', $authority->today())->whereDate('ends_on', '>=', $authority->today())->select('grade_level_id');

        return $query->whereIn('grade_level_id', $grades);
    }

    public function batch(User $actor, int $id): ReportBatch
    {
        $authority = app(AcademicAuthority::class);
        $authority->staff($actor);
        $batch = ReportBatch::findOrFail($id);
        abort_unless($this->visibleBatches($actor)->whereKey($id)->exists(), 403);

        return $batch;
    }

    public function sign(User $actor, ReportBatch $batch, string $kind): void
    {
        abort_unless(in_array($kind, ['grade_head', 'principal'], true) && $this->leads($actor, $kind, (int) $batch->grade_level_id), 403);
    }

    public function comment(User $actor, ReportCard $card, ?int $subjectId): bool
    {
        $authority = app(AcademicAuthority::class);
        $authority->staff($actor);
        $batch = $card->batch;
        $teachers = $authority->teachers($actor);
        if ($subjectId === null) {
            $allowed = HomeroomAllocation::where('class_section_id', $batch->class_section_id)->where('session_year_id', $batch->session_year_id)->whereIn('teacher_id', $teachers)
                ->whereDate('starts_on', '<=', $authority->today())->whereDate('ends_on', '>=', $authority->today())->exists();
        } else {
            $allowed = TeachingAllocation::whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $authority->today())->whereDate('ends_on', '>=', $authority->today())
                ->whereHas('group', fn ($q) => $q->where('active', true)->where('subject_id', $subjectId)->where('grade_level_id', $batch->grade_level_id)->where('session_year_id', $batch->session_year_id)
                    ->whereHas('memberships', fn ($m) => $m->where('learner_id', $card->learner_id)->whereDate('ends_on', '>=', $authority->today())->whereDate('starts_on', '<=', $authority->today())))->exists();
        }
        $override = ! $allowed;
        if ($override) {
            abort_unless($authority->manages($actor) || $this->leads($actor, 'grade_head', (int) $batch->grade_level_id) || $this->leads($actor, 'principal', (int) $batch->grade_level_id), 403);
        }
        if ($subjectId !== null) {
            abort_unless(in_array($subjectId, array_column($card->payload['subjects'], 'subject_id'), true), 404);
        }

        return $override;

    }

    public function family(User $actor, int $id): ReportCard
    {
        $card = ReportCard::with('batch')->findOrFail($id);
        app(ReleasedMarks::class)->learner($actor, (int) $card->learner_id);
        abort_unless($card->batch->state === 'released' && $card->generated_fingerprint === $card->batch->fingerprint, 404);

        return $card;
    }
}
