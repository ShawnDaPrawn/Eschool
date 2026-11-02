<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * "Every child teaches something" (Action Plan 4.7): per grade, how many
 * current learners have tutored at least once this term. Counts only: no
 * learner is named, listed or ranked. Staff who manage the academic structure
 * (academic-structure-manage) or hold a current principal appointment see
 * every grade; a current grade head sees their own grade. Anyone else is
 * refused. engineering/specs/community/peer-tutoring.md.
 */
class PeerMeasure
{
    public function available(User $actor): bool
    {
        try {
            return $this->grades($actor) !== null;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /**
     * @return list<array{grade_level_id: int, grade: string, term: string, from: string, to: string, learners: int, tutored: int, share: int}>
     */
    public function rows(User $actor): array
    {
        $visible = $this->grades($actor);
        abort_if($visible === null, 403);
        $today = app(AcademicAuthority::class)->today();
        $year = (int) schoolSetting('session_year');
        $gradeByClass = ClassSchool::query()->whereNotNull('grade_level_id')->pluck('grade_level_id', 'id');
        $classBySection = ClassSection::query()->pluck('class_id', 'id');
        $learners = Students::query()->whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))->select('id');
        $byGrade = [];
        foreach (Enrolment::query()->where('session_year_id', $year)->onDate($today)->whereIn('learner_id', $learners)
            ->orderBy('learner_id')->get(['learner_id', 'class_section_id']) as $enrolment) {
            $grade = $gradeByClass[$classBySection[$enrolment->class_section_id] ?? 0] ?? null;
            if ($grade !== null && ($visible === [] || in_array((int) $grade, $visible, true))) {
                $byGrade[(int) $grade][(int) $enrolment->learner_id] = true;
            }
        }
        $grades = GradeLevel::query()->whereIn('id', array_keys($byGrade))->orderBy('sort_order')->get(['id', 'name']);
        $rows = [];
        foreach ($grades as $grade) {
            $ids = array_keys($byGrade[(int) $grade->id]);
            $window = app(PeerTerms::class)->window((int) $grade->id);
            $tutored = count(app(PeerMatches::class)->tutoredThisTerm($ids, $window, ['completed']));
            $rows[] = ['grade_level_id' => (int) $grade->id, 'grade' => (string) $grade->name, 'term' => $window['label'],
                'from' => $window['from'], 'to' => $window['to'], 'learners' => count($ids), 'tutored' => $tutored,
                'share' => (int) round($tutored * 100 / max(1, count($ids)))];
        }
        $this->grades($actor);

        return $rows;
    }

    /**
     * The grades this actor may see: [] for every grade, a list of grade level
     * ids for a grade head, or null when the actor may not see the measure.
     *
     * @return list<int>|null
     */
    private function grades(User $actor): ?array
    {
        $fresh = app(PeerAccess::class)->actor($actor);
        $academic = app(AcademicAuthority::class);
        $academic->staff($fresh);
        $year = (int) schoolSetting('session_year');
        if ($academic->manages($fresh) || $academic->leads($fresh, 'principal', 0, $year)) {
            return [];
        }
        $grades = [];
        foreach (GradeLevel::query()->orderBy('sort_order')->pluck('id') as $grade) {
            if ($academic->leads($fresh, 'grade_head', (int) $grade, $year)) {
                $grades[] = (int) $grade;
            }
        }

        return $grades === [] ? null : $grades;
    }
}
