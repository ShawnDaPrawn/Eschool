<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AttendanceImport implements ToCollection, WithHeadingRow
{
    public $class_section_id;

    public $date;

    /**
     * @param  Collection  $collection
     */
    public function __construct($class_section_id)
    {
        $this->class_section_id = $class_section_id;
    }

    public function collection(Collection $rows)
    {
        $validator = Validator::make($rows->toArray(), [
            '*.student_id' => 'required|numeric',
            '*.date' => 'required',
            '*.type' => 'required|in:0,1,4,5',
        ], [
            '*.type' => 'The type must be 0 (Absent), 1 (Present), 4 (First Half) or 5 (Second Half).',
        ]);
        $validator->validate();
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        foreach ($rows as $row) {
            $session_year = getSettings('session_year');
            $date = date('Y-m-d', strtotime($row['date']));

            $attendance = Attendance::where('date', $date)
                ->where('class_section_id', $this->class_section_id)
                ->where('student_id', $row['student_id'])
                ->first();

            if ($attendance) {
                $attendance->type = $row['type'];
                $attendance->save();
            } else {
                $attendance = new Attendance;
                $attendance->class_section_id = $this->class_section_id;
                $attendance->student_id = $row['student_id'];
                $attendance->session_year_id = $session_year['session_year'];
                $attendance->date = $date;
                $attendance->type = $row['type'];
                $attendance->save();
            }

        }
    }
}
