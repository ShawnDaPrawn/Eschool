<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\AbsenceAlertService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * The bulk attendance sheet a class teacher downloads, fills in and uploads.
 * Rows are saved the way the Add Attendance form saves them.
 */
class AttendanceImport implements ToCollection, WithHeadingRow, WithReadFilter
{
    public $class_section_id;

    public function __construct($class_section_id)
    {
        $this->class_section_id = $class_section_id;
    }

    /** Refuses a file over RowLimit::MAX_ROWS rows while it is read. */
    public function readFilter(): IReadFilter
    {
        return new RowLimit;
    }

    public function collection(Collection $rows)
    {
        $sessionYearId = (int) getSettings('session_year')['session_year'];
        $enrolled = StudentSessions::where(['class_section_id' => $this->class_section_id, 'session_year_id' => $sessionYearId])
            ->pluck('student_id')->all();

        $validator = Validator::make($rows->toArray(), [
            // A sheet downloaded for another class is refused rather than saved under this one.
            '*.student_id' => ['required', 'numeric', Rule::in($enrolled)],
            '*.date' => 'required|date',
            '*.type' => 'required|in:0,1,4,5',
        ], [
            '*.type' => 'The type must be 0 (Absent), 1 (Present), 4 (First Half) or 5 (Second Half).',
            '*.student_id.in' => trans('student_not_in_this_class_section'),
        ]);
        if ($validator->fails()) {
            // Name the spreadsheet row: data starts on row 2, under the heading row.
            $row = (int) strtok((string) array_key_first($validator->errors()->messages()), '.') + 2;
            throw ValidationException::withMessages(['file' => trans('row').' '.$row.': '.$validator->errors()->first()]);
        }

        $studentUserIds = Students::whereIn('id', $rows->pluck('student_id'))->pluck('user_id', 'id');
        $leaveTypes = [];
        $newlyAbsent = [];
        foreach ($rows as $row) {
            $date = date('Y-m-d', strtotime((string) $row['date']));
            $leaveTypeByUserId = $leaveTypes[$date] ??= Leave::approvedTypesForDate($date);

            $attendance = Attendance::where('date', $date)
                ->where('class_section_id', $this->class_section_id)
                ->where('student_id', $row['student_id'])
                ->first() ?? new Attendance;
            $wasAbsent = $attendance->exists && (int) $attendance->type === Attendance::TYPE_ABSENT;

            $attendance->class_section_id = $this->class_section_id;
            $attendance->student_id = $row['student_id'];
            $attendance->date = $date;
            $attendance->session_year_id = $sessionYearId;
            $userId = $studentUserIds[$row['student_id']] ?? null;
            // Approved leave overrides the sheet, as on the Add Attendance form.
            $attendance->type = $userId !== null && isset($leaveTypeByUserId[$userId])
                ? Attendance::leaveTypeToType($leaveTypeByUserId[$userId])
                : $row['type'];
            $attendance->status = 1;
            $attendance->save();

            if ((int) $attendance->type === Attendance::TYPE_ABSENT && ! $wasAbsent) {
                $newlyAbsent[$date][] = $row['student_id'];
            }
        }

        foreach ($newlyAbsent as $date => $studentIds) {
            app(AbsenceAlertService::class)->notify($studentIds, $date, $sessionYearId);
        }
    }
}
