<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Parents;
use App\Models\Semester;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher app: the teacher's classes and subjects, and their students.
 */
class ClassController extends Controller
{
    use TeacherAccess;

    public function classes(Request $request)
    {
        try {
            $settings = getSettings('session_year');
            $sessionYearId = $settings['session_year'];
            $teacher = $request->user()->teacher;

            [$classTeacherSections, $otherClassSections] = $this->getTeacherClassSections(
                $teacher,
                $sessionYearId
            );

            ResponseService::successResponse('Teacher Classes Fetched Successfully.', [
                'class_teacher' => $classTeacherSections->isNotEmpty() ? $classTeacherSections : (object) null,
                'other' => $otherClassSections,
            ]);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function subjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter_by_semester' => 'nullable|boolean',
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $filter_by_semester = $request->filter_by_semester ? $request->filter_by_semester : null;

            $user = $request->user();
            $teacher = $user->teacher;
            $subjects = $teacher->subjects()
                ->where('session_year_id', $session_year_id)
                ->with([
                    'subject',
                    'semester:id,name,session_year_id,start_date,end_date',
                ]);

            if ($request->class_section_id) {
                // if ($filter_by_semester) {
                //     $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id');
                //     $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

                //     $currentSemester = Semester::where('session_year_id', $session_year_id)
                //         ->get()
                //         ->first(fn($s) => $s->current);

                //     $subjects = $subjects->where('class_section_id', $request->class_section_id)
                //         ->when($isClassHaveSemester && $currentSemester, function ($query) use ($currentSemester) {
                //             $query->where('semester_id', $currentSemester->id);
                //         });
                // }
                $subjects = $subjects->where('class_section_id', $request->class_section_id);
            }

            if ($request->subject_id) {
                $subjects = $subjects->where('subject_id', $request->subject_id);
            }

            $subjects = $subjects->with('subject')->get()->map(function ($item) {
                $subject = clone $item->subject;
                $subject->setRelation('semester', $item->semester);
                $item->setRelation('subject', $subject);
                $item->unsetRelation('semester');

                return $item;
            });

            ResponseService::successResponse('Teacher Subject Fetched Successfully.', $subjects);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'subject_id' => 'nullable',
            'semester_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $user = Auth::user()->teacher;
            $class_section_id = $request->class_section_id;
            $semester_id = $request->semester_id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_id = ClassSection::where('id', $class_section_id)->value('class_id');

            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            // Verify that the teacher is assigned to this specific class section
            $isTeacherAssigned = ClassTeacher::where('class_section_id', $class_section_id)
                ->where('class_teacher_id', $user->id)
                ->where('session_year_id', $session_year_id)
                // ->when($isClassHaveSemester && $semester_id, fn ($q) => $q->where('semester_id', $semester_id)->orWhereNull('semester_id'))
                ->exists();

            if (! $isTeacherAssigned) {
                ResponseService::errorResponse('You are not assigned to this class section', null, 403);
            }

            // Get student IDs for this class section in the current session year
            $studentIds = StudentSessions::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->pluck('student_id');

            $sql = Students::with('user:id,first_name,last_name,image,gender,dob,current_address,permanent_address', 'class_section')
                ->whereIn('id', $studentIds);

            $data = $sql->orderBy('roll_number')->get();

            if (isset($request->subject_id)) {
                $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');
                $class_subject = ClassSubject::where('subject_id', $request->subject_id)
                    ->where('semester_id', $semester_id)
                    ->where('session_year_id', $session_year_id)
                    ->where('class_id', $class_id)->first();

                if ($class_subject->type == 'Elective') {
                    foreach ($data as $student) {
                        $student_id[] = $student->id;
                    }
                    $student_subject = StudentSubject::whereIn('student_id', $student_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('semester_id', $semester_id)
                        ->where('session_year_id', $session_year_id)
                        ->where('class_section_id', $class_section_id)->pluck('student_id');

                    if ($student_subject) {
                        $sql = Students::with('user:id,first_name,last_name,image,gender,dob,current_address,permanent_address', 'class_section')
                            ->whereIn('id', $student_subject);
                        $data = $sql->orderBy('id')->get();
                    }
                }
            }
            ResponseService::successResponse('Student Details Fetched Successfully', $data);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            if (! $this->teachesStudent($request->student_id)) {
                ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $student_data_ids = Students::select('user_id', 'class_section_id', 'father_id', 'mother_id', 'guardian_id')->where('id', $request->student_id)->get();
            $full_day_present = Attendance::where('student_id', $request->student_id)->where('session_year_id', $session_year_id)->where('type', Attendance::TYPE_PRESENT)->count();
            $full_day_absent = Attendance::where('student_id', $request->student_id)->where('session_year_id', $session_year_id)->where('type', Attendance::TYPE_ABSENT)->count();
            // Each half-day absence is 0.5 present + 0.5 absent, matching Attendance::monthlyReport()'s totals rule.
            $half_day_count = Attendance::where('student_id', $request->student_id)->where('session_year_id', $session_year_id)->whereIn('type', [Attendance::TYPE_FIRST_HALF, Attendance::TYPE_SECOND_HALF])->count();
            $student_total_present = $full_day_present + ($half_day_count * 0.5);
            $student_total_absent = $full_day_absent + ($half_day_count * 0.5);

            $today_date_string = Carbon::now();
            $today_date_string->toDateTimeString();
            $today_date = date('Y-m-d', strtotime($today_date_string->toDateString()));

            $student_today_attendance = Attendance::where('student_id', $request->student_id)->where('date', $today_date)->get();
            if ($student_today_attendance->count()) {
                foreach ($student_today_attendance as $student_attendance) {
                    if ($student_attendance['type'] == 1) {
                        $today_attendance = 'Present';
                    } else {
                        $today_attendance = 'Absent';
                    }
                }
            } else {
                $today_attendance = 'Not Taken';
            }
            foreach ($student_data_ids as $student_data_ids) {
                $father_data = Parents::where('id', $student_data_ids['father_id'])->get();
                $mother_data = Parents::where('id', $student_data_ids['mother_id'])->get();
                if ($student_data_ids['guardian_id'] != 0) {
                    $guardian_data = Parents::where('id', $student_data_ids['guardian_id'])->get();

                    ResponseService::successResponse('Student Details Fetched Successfully', null, [
                        'gurdian_data' => $guardian_data,
                        'father_data' => $father_data,
                        'mother_data' => $mother_data,
                        'total_present' => $student_total_present,
                        'total_absent' => $student_total_absent,
                        'today_attendance' => $today_attendance,
                    ]);
                } else {
                    ResponseService::successResponse('Student Details Fetched Successfully', null, [
                        'father_data' => $father_data,
                        'mother_data' => $mother_data,
                        'total_present' => $student_total_present,
                        'total_absent' => $student_total_absent,
                        'today_attendance' => $today_attendance,
                    ]);
                }
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Returns [$classTeacherSections, $otherClassSections] for the given teacher
     * and session year, filtered to the current semester where applicable.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Collection, 1: \Illuminate\Database\Eloquent\Collection}
     */
    private function getTeacherClassSections($teacher, $sessionYearId): array
    {
        $currentSemester = Semester::where('session_year_id', $sessionYearId)
            ->get()
            ->first(fn ($s) => $s->current);

        $classSectionsWith = ['class.medium', 'section', 'class.streams', 'class.shifts'];

        // Class Teacher Sections
        $classTeacherRecords = ClassTeacher::when(
            $currentSemester,
            fn ($q) => $q->where(fn ($q) => $q->where('semester_id', $currentSemester->id)
                ->orWhereNull('semester_id'))
        )
            ->where('session_year_id', $sessionYearId)
            ->where('class_teacher_id', $teacher->id)
            ->whereIn(
                'class_section_id',
                $teacher->class_sections()
                    ->where('session_year_id', $sessionYearId)
                    ->pluck('class_section_id')
            )
            ->get();

        $classTeacherSections = ClassSection::whereIn('id', $classTeacherRecords->pluck('class_section_id'))
            ->with($classSectionsWith)
            ->get();

        // Subject Teacher Sections (excluding class teacher ones)
        $subjectSectionIds = SubjectTeacher::when(
            $currentSemester,
            fn ($q) => $q->where(fn ($q) => $q->where('semester_id', $currentSemester->id)
                ->orWhereNull('semester_id'))
        )
            ->where('session_year_id', $sessionYearId)
            ->where('teacher_id', $teacher->id)
            ->pluck('class_section_id');

        $otherClassSections = ClassSection::whereIn('id', $subjectSectionIds)
            ->with($classSectionsWith)
            ->get()
            ->diff($classTeacherSections);

        return [$classTeacherSections, $otherClassSections];
    }
}
