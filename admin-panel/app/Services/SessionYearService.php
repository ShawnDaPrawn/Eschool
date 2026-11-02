<?php

namespace App\Services;

use App\Actions\Transfers\TransferClassSessionConfig;
use App\Actions\Transfers\TransferClassSubject;
use App\Actions\Transfers\TransferClassTeacherAndSubjectTeacher;
use App\Actions\Transfers\TransferClassTimetable;
use App\Actions\Transfers\TransferExamGrades;
use App\Actions\Transfers\TransferLeaveSettings;
use App\Models\ClassSchool;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\OnlineExam;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Term;
use App\Services\Academics\TermCalendar;
use App\Services\People\Leavers\LeaverState;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionYearService
{
    public function store(array $data): SessionYear
    {
        return DB::transaction(function () use ($data) {

            $startDate = Carbon::createFromFormat('d-m-Y', $data['start_date'])->toDateString();
            $endDate = Carbon::createFromFormat('d-m-Y', $data['end_date'])->toDateString();
            $sessionYear = SessionYear::create([
                'name' => $data['name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            if (! empty($data['term_data'])) {
                $this->storeTerms(
                    $sessionYear->id,
                    $data['term_data'],
                    $startDate,
                    $endDate
                );
            } else {
                /*
                    No terms created for this session year — seed a
                    ClassSessionConfig row for every class so the table stays
                    authoritative (each class explicitly has no terms).
                */
                $this->initialiseClassSessionConfigs($sessionYear->id);
            }

            $this->handleTransfers($sessionYear->id, $data);

            if (($data['set_as_current'] ?? '0') == '1') {
                $this->makeCurrent($sessionYear);
            }

            return $sessionYear;
        });
    }

    /**
     * Make a session year the school's current one: the year the apps show and
     * the admin panel opens on.
     */
    public function makeCurrent(SessionYear $sessionYear): void
    {
        $previous = Settings::where('type', 'session_year')->value('message');
        $changed = (string) $previous !== (string) $sessionYear->id;

        DB::transaction(function () use ($sessionYear, $changed) {
            SessionYear::where('default', 1)->whereKeyNot($sessionYear->id)->update(['default' => 0]);
            $sessionYear->default = 1;
            $sessionYear->save();
            Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $sessionYear->id]);

            if ($changed) {
                $this->applyPromotions($sessionYear->id);
            }
        });

        // The admin panel's year picker follows the new current year.
        if ($changed && request()->hasSession()) {
            session(['session_year' => $sessionYear->id]);
        }
    }

    /**
     * Promotions recorded for a year take effect when it becomes current: students
     * move to the class they were promoted to, and those who left become leavers,
     * reading their records until 31 March instead of being deactivated
     * (LeaverState, Action Plan 1.21).
     */
    protected function applyPromotions(int $sessionYearId): void
    {
        $sessions = StudentSessions::where('session_year_id', $sessionYearId)->get(['student_id', 'class_section_id', 'status']);

        $sessions->where('status', 1)->whereNotNull('class_section_id')->groupBy('class_section_id')
            ->each(fn ($group, $classSectionId) => Students::whereIn('id', $group->pluck('student_id'))->update(['class_section_id' => $classSectionId]));

        $leaving = $sessions->where('status', 0)->pluck('student_id');
        if ($leaving->isNotEmpty()) {
            app(LeaverState::class)->begin($leaving, SessionYear::query()->findOrFail($sessionYearId));
        }
    }

    protected function storeTerms(
        int $sessionYearId,
        array $terms,
        string $startDate,
        string $endDate
    ): void {
        $year = SessionYear::query()->lockForUpdate()->findOrFail($sessionYearId);
        $rows = [];
        foreach ($terms as $term) {
            $rows[] = [
                'name' => $term['name'],
                'starts_on' => Carbon::createFromFormat('d-m-Y', $term['start_date'])->toDateString(),
                'ends_on' => Carbon::createFromFormat('d-m-Y', $term['end_date'])->toDateString(),
                'grade_from' => $term['grade_from'] ?? 0, 'grade_to' => $term['grade_to'] ?? 12,
                'marks_lock_on' => $term['marks_lock_on'] ?? null, 'reports_on' => $term['reports_on'] ?? null,
            ];
        }
        app(TermCalendar::class)->initialise($year, $rows);
    }

    /**
     * Seed a "no terms" ClassSessionConfig row for every class.
     *
     * Used when a session year is created without term data, so
     * term-aware features can rely on an explicit
     * include_terms = false per class instead of a missing row.
     */
    protected function initialiseClassSessionConfigs(int $sessionYearId): void
    {
        $classIds = ClassSchool::pluck('id')->all();

        if (empty($classIds)) {
            return;
        }

        $now = now();

        $rows = array_map(static fn (int $classId): array => [
            'class_id' => $classId,
            'session_year_id' => $sessionYearId,
            'include_terms' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $classIds);

        // upsert keeps it idempotent against the
        // (class_id, session_year_id) unique constraint.
        ClassSessionConfig::upsert(
            $rows,
            ['class_id', 'session_year_id'],
            ['include_terms', 'updated_at']
        );
    }

    protected function handleTransfers(int $newSessionId, array $data): void
    {
        // Use the explicitly chosen source session year; fall back to the system default
        $currentSessionId = ! empty($data['source_session_year_id'])
            ? (int) $data['source_session_year_id']
            : getSettings('session_year')['session_year'];

        if (! empty($data['term_data'])) {
            app(TransferClassSessionConfig::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_subject'] ?? '0') == '1') {
            app(TransferClassSubject::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_teacher_subject'] ?? '0') == '1') {
            app(TransferClassTeacherAndSubjectTeacher::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_timetable'] ?? '0') == '1') {
            app(TransferClassTimetable::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_exam_grades'] ?? '0') == '1') {
            app(TransferExamGrades::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_leave_settings'] ?? '0') == '1') {
            app(TransferLeaveSettings::class)
                ->execute($currentSessionId, $newSessionId);
        }

        // Note: term transfer is handled via storeTerms() using term_data
    }

    // Clear Data of Session Year
    public function clearSpecificData(int $sessionYearId, array $types): void
    {
        SessionYear::findOrFail($sessionYearId);

        DB::transaction(function () use ($sessionYearId, $types) {

            foreach ($types as $type) {
                match ($type) {
                    'class_subject' => $this->deleteClassSubjects($sessionYearId),
                    'class_teachers' => $this->deleteClassTeachers($sessionYearId),
                    'notifications' => $this->deleteNotifications($sessionYearId),
                    'timetable' => $this->deleteTimetable($sessionYearId),
                    'attendance' => $this->deleteAttendance($sessionYearId),
                    'assignments' => $this->deleteAssignments($sessionYearId),
                    'exams' => $this->deleteExams($sessionYearId),
                    'grades' => $this->deleteGrades($sessionYearId),
                    'announcements' => $this->deleteAnnouncements($sessionYearId),
                    'holidays' => $this->deleteHolidays($sessionYearId),
                    'events' => $this->deleteEvents($sessionYearId),
                    'leaves' => $this->deleteLeaves($sessionYearId),
                    'allowed_leave_days' => $this->deleteAllowedLeaveDays($sessionYearId),
                };
            }
        });
    }

    private function deleteClassSubjects(int $sessionYearId): void
    {
        ClassSubject::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteClassTeachers(int $sessionYearId): void
    {
        ClassTeacher::where('session_year_id', $sessionYearId)->delete();
        SubjectTeacher::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteNotifications(int $sessionYearId): void
    {
        DB::table('notifications')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteAttendance(int $sessionYearId): void
    {
        DB::table('attendances')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteAssignments(int $sessionYearId): void
    {
        DB::table('assignments')
            ->where('session_year_id', $sessionYearId)
            ->delete();

        DB::table('assignment_submissions')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteExams(int $sessionYearId): void
    {
        Exam::where('session_year_id', $sessionYearId)
            ->chunkById(200, function ($onlineExams) {
                foreach ($onlineExams as $onlineExam) {
                    $onlineExam->delete();
                }
            });

        OnlineExam::where('session_year_id', $sessionYearId)
            ->chunkById(200, function ($onlineExams) {
                foreach ($onlineExams as $onlineExam) {
                    $onlineExam->delete();
                }
            });
    }

    private function deleteTimetable(int $sessionYearId): void
    {
        DB::table('timetables')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteGrades(int $sessionYearId): void
    {
        DB::table('grades')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteAnnouncements(int $sessionYearId): void
    {
        DB::table('announcements')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteEvents(int $sessionYearId): void
    {
        DB::table('events')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteHolidays(int $sessionYearId): void
    {
        DB::table('holidays')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteLeaves(int $sessionYearId): void
    {
        Leave::where('session_year_id', $sessionYearId)->delete();

        LeaveDetail::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteAllowedLeaveDays(int $sessionYearId): void
    {
        DB::table('leave_masters')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }
}
