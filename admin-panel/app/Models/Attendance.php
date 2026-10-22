<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use BelongsToSchool, HasFactory;
    use SoftDeletes;

    protected $hidden = ['remark', 'deleted_at', 'created_at', 'updated_at'];

    // Attendance type values (kept in sync with the `type` column comment).
    public const TYPE_ABSENT = 0;

    public const TYPE_PRESENT = 1;

    public const TYPE_HOLIDAY = 3;

    public const TYPE_FIRST_HALF = 4;

    public const TYPE_SECOND_HALF = 5;

    /**
     * Map a leave-day type to the matching attendance type. The leave_details.type
     * column is not validated at write time (the mobile APIs store whatever they are
     * sent), so values are inconsistent in practice: "Full", "First Half",
     * "Second Half" from the web form, plus a generic "half" from the apps. This
     * normalises all of them:
     *   - anything mentioning "first"  -> First Half (4)
     *   - anything mentioning "second" -> Second Half (5)
     *   - a generic "half" (which half is unknown) -> First Half (4); both halves
     *     count the same (0.5 present / 0.5 absent) so the numbers are unaffected
     *   - everything else ("Full", empty, unknown) -> Absent (0)
     */
    public static function leaveTypeToType(?string $leaveType): int
    {
        $normalized = strtolower(trim((string) $leaveType));

        return match (true) {
            str_contains($normalized, 'first') => self::TYPE_FIRST_HALF,
            str_contains($normalized, 'second') => self::TYPE_SECOND_HALF,
            str_contains($normalized, 'half') => self::TYPE_FIRST_HALF,
            default => self::TYPE_ABSENT,
        };
    }

    /**
     * Translate an internal attendance `type` to the value the mobile apps expect
     * for the calendar grid on the attendance-report screen:
     *   present (1) / holiday (3) -> 0 (normal day, no circle)
     *   absent (0)                -> 1 (red circle)
     *   first half (4)            -> 2 (light-blue circle)
     *   second half (5)           -> 3 (yellow circle)
     */
    public static function typeToReportType(int $type): int
    {
        return match ($type) {
            self::TYPE_ABSENT => 1,
            self::TYPE_FIRST_HALF => 2,
            self::TYPE_SECOND_HALF => 3,
            default => 0, // present, holiday, or anything else
        };
    }

    /**
     * Build the monthly attendance report payload consumed by the Student/Parent
     * attendance-report APIs. `full_day_*`/`first_half_absent`/`second_half_absent`
     * are whole-day counts, but `total_present`/`total_absent` split each half-day
     * absence into 0.5 present + 0.5 absent so the totals reflect actual attendance
     * (e.g. one full present day + one half-day absence = 1.5 present / 0.5 absent).
     * The `attendance` array uses the frontend `type` scheme via
     * {@see typeToReportType()}. $month/$year are optional filters (null =
     * unfiltered), matching the Student/Parent getAttendance APIs.
     */
    public static function monthlyReport(int $studentId, int $sessionYearId, ?int $month = null, ?int $year = null): array
    {
        $records = self::where('student_id', $studentId)
            ->where('session_year_id', $sessionYearId)
            ->when($month !== null, fn ($query) => $query->whereMonth('date', $month))
            ->when($year !== null, fn ($query) => $query->whereYear('date', $year))
            ->orderBy('date')
            ->get(['date', 'type']);

        $typeByDate = [];
        foreach ($records as $record) {
            $date = $record->date instanceof \DateTimeInterface
                ? $record->date->format('Y-m-d')
                : (string) $record->date;
            $typeByDate[$date] = (int) $record->type;
        }

        // Approved leaves override whatever attendance is on record for that date, and
        // backfill days nobody has marked attendance for yet. This mirrors the
        // write-time rule applied when attendance is actually submitted (see
        // AttendanceController::store / TeacherApiController::submitAttendance), so the
        // report doesn't wait for someone to re-run attendance-marking after approval.
        $userId = Students::where('id', $studentId)->value('user_id');

        if ($userId !== null) {
            $leaveDetails = LeaveDetail::when($month !== null, fn ($query) => $query->whereMonth('date', $month))
                ->when($year !== null, fn ($query) => $query->whereYear('date', $year))
                ->whereHas('leave', function ($query) use ($userId, $sessionYearId) {
                    $query->where('user_id', $userId)
                        ->where('status', 1)
                        ->where('session_year_id', $sessionYearId);
                })
                ->get(['date', 'type']);

            foreach ($leaveDetails as $detail) {
                $date = $detail->date instanceof \DateTimeInterface
                    ? $detail->date->format('Y-m-d')
                    : (string) $detail->date;
                $typeByDate[$date] = self::leaveTypeToType($detail->type);
            }
        }

        ksort($typeByDate);

        $fullDayPresent = 0;
        $fullDayAbsent = 0;
        $firstHalfAbsent = 0;
        $secondHalfAbsent = 0;
        $attendance = [];

        foreach ($typeByDate as $date => $type) {
            match ($type) {
                self::TYPE_PRESENT => $fullDayPresent++,
                self::TYPE_ABSENT => $fullDayAbsent++,
                self::TYPE_FIRST_HALF => $firstHalfAbsent++,
                self::TYPE_SECOND_HALF => $secondHalfAbsent++,
                default => null,
            };

            $attendance[] = [
                'date' => $date,
                'type' => self::typeToReportType($type),
            ];
        }

        $halfDayCount = $firstHalfAbsent + $secondHalfAbsent;

        return [
            'total_present' => $fullDayPresent + ($halfDayCount * 0.5),
            'total_absent' => $fullDayAbsent + ($halfDayCount * 0.5),
            'full_day_present' => $fullDayPresent,
            'full_day_absent' => $fullDayAbsent,
            'first_half_absent' => $firstHalfAbsent,
            'second_half_absent' => $secondHalfAbsent,
            'attendance' => $attendance,
        ];
    }

    public function student()
    {
        return $this->belongsTo(Students::class)->with('user');
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class', 'section', 'class.medium', 'streams');
    }
}
