<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\TimetableCollection;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\ClassSessionConfig;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamTimetable;
use App\Models\MultipleEvent;
use App\Models\Semester;
use App\Models\Slider;
use App\Models\Student;
use App\Models\StudentSessions;
use App\Models\Timetable;
use App\Services\GalleryService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Student app: the home screen.
 */
class DashboardController extends Controller
{
    use StudentAccess;

    public function dashboard(Request $request)
    {
        try {
            /** @var Student $student */
            $student = $request->user()->student;
            $date = Carbon::now();
            $day = $date->format('l');
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // Fetch student session
            $studentSession = StudentSessions::with('class_section')
                ->where([
                    'student_id' => $student->id,
                    'session_year_id' => $session_year_id,
                ])
                ->first();

            $class_id = $studentSession->class_section->class_id;
            $class_section_id = $studentSession->class_section_id;

            // Get current semester (if any) for this session year
            $currentSemester = Semester::where('session_year_id', $session_year_id)
                ->get()
                ->first(fn ($semester) => $semester->current);

            // Check if this class uses semesters
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            $sliders = Slider::where('type', 1)->orWhere('type', 3)->get()->toArray();
            $subjects = $student->subjects();

            if ($subjects['elective_subject'] == []) {
                $subjects['elective_subject'] = null;
            }

            $announcements = Announcement::where('table_type', '')
                ->where('session_year_id', $session_year_id)
                ->with('file')
                ->latest()
                ->limit(3)
                ->get();

            $student_subject = $student->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            // Assignments
            $assignmentsQuery = Assignment::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)
                ->whereDoesntHave('submission', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })
                ->where('due_date', '>', $date)
                ->with(['subject', 'file'])
                ->orderBy('due_date', 'asc')
                ->limit(2);

            // Filter by semester_id when the class has a semester
            if ($isClassHaveSemester && $currentSemester) {
                $assignmentsQuery->where('semester_id', $currentSemester->id);
            }

            $assignments = $assignmentsQuery->get();

            // Timetable
            $timetableQuery = Timetable::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->where('day_name', $day)
                ->whereHas('subject_teacher', function ($q) use ($subject_id) {
                    $q->whereIn('subject_id', $subject_id);
                })
                ->orderBy('start_time', 'asc');

            // Filter by semester_id when the class has a semester
            if ($isClassHaveSemester && $currentSemester) {
                $timetableQuery->where('semester_id', $currentSemester->id);
            }

            $timetables = $timetableQuery->get();

            // Exams
            $examQuery = Exam::with(['timetable' => function ($q) use ($class_id, $subject_id) {
                $q->where('class_id', $class_id)
                    ->whereIn('subject_id', $subject_id)
                    ->with(['subject'])
                    ->orderBy('date');
            }])->limit(10);

            // Filter exams by semester_id when the class has a semester
            if ($isClassHaveSemester && $currentSemester) {
                $examQuery->where('semester_id', $currentSemester->id);
            }

            $exam_data_db = $examQuery->get();

            $exam_data = [];
            foreach ($exam_data_db as $data) {
                $exam_timetable = [];

                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))
                    ->where(['exam_id' => $data->id, 'class_id' => $class_id])
                    ->first();
                $starting_date = $starting_date_db['min(date)'];

                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))
                    ->where(['exam_id' => $data->id, 'class_id' => $class_id])
                    ->first();
                $ending_date = $ending_date_db['max(date)'];

                $current_date = Carbon::now()->toDateString();

                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = '1'; // On Going
                } elseif ($current_date < $starting_date) {
                    $exam_status = '0'; // Upcoming
                } else {
                    $exam_status = '2'; // Completed
                }

                foreach ($data->timetable as $item) {
                    $exam_date = Carbon::parse($item->date);
                    if ($date->toDateString() <= $exam_date->toDateString()) {
                        $exam_timetable[] = [
                            'id' => $item->id,
                            'total_marks' => $item->total_marks,
                            'passing_marks' => $item->passing_marks,
                            'date' => $item->date,
                            'starting_time' => $item->start_time,
                            'ending_time' => $item->end_time,
                            'subject' => [
                                'id' => $item->subject->id,
                                'name' => $item->subject->name,
                                'bg_color' => $item->subject->bg_color,
                                'image' => $item->subject->image,
                                'type' => $item->subject->type,
                            ],
                        ];
                    }
                }

                if ($exam_status != 2) {
                    $exam_data[] = [
                        'id' => $data->id,
                        'name' => $data->name,
                        'description' => $data->description,
                        'publish' => $data->publish,
                        'session_year' => $data->session_year->name,
                        'exam_starting_date' => $starting_date,
                        'exam_ending_date' => $ending_date,
                        'exam_status' => $exam_status,
                        'exam_timetable' => $exam_timetable,
                    ];
                }
            }

            // Events
            $events = Event::where(function ($query) use ($date) {
                $query->where('start_date', '>=', $date)
                    ->orWhere('end_date', '>=', $date)
                    ->orWhereDate('start_date', '=', $date)
                    ->orWhere('end_date', '=', $date);
            })
                ->orderBy('start_date')
                ->limit(3)
                ->get();

            $event = [];
            foreach ($events as $row) {
                $has_day_schedule = 0;
                if ($row->type == 'multiple') {
                    $has_day_schedule = MultipleEvent::where('event_id', $row->id)->exists() ? 1 : 0;
                }
                $event[] = [
                    'id' => $row->id,
                    'has_day_schedule' => $has_day_schedule,
                    'title' => $row->title,
                    'type' => $row->type,
                    'start_date' => $row->start_date,
                    'end_date' => $row->end_date,
                    'start_time' => $row->start_time,
                    'end_time' => $row->end_time,
                    'image' => $row->image,
                    'description' => $row->description,
                ];
            }

            $gallery = app(GalleryService::class)->getGallery();

            $data = [
                'sliders' => $sliders,
                'subjects' => $subjects,
                'announcements' => $announcements,
                'assignments' => $assignments,
                'timetables' => new TimetableCollection($timetables),
                'exams' => $exam_data,
                'events' => $event,
                'gallery' => $gallery,
            ];

            return ResponseService::successResponse('Data Fetched Successfully.', (object) $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
