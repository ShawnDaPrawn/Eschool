<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Event;
use App\Models\ExamClass;
use App\Models\ExamTimetable;
use App\Models\Leave;
use App\Models\MultipleEvent;
use App\Models\Semester;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\GalleryService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher app: the home screen.
 */
class DashboardController extends Controller
{
    use TeacherAccess;

    public function dashboard(Request $request)
    {
        try {
            $current_day = Carbon::now()->format('l');
            $current_date = Carbon::now()->format('Y-m-d');
            $setting = getSettings();
            $session_year_id = $setting['session_year'];

            $teacher = $request->user()->teacher;
            $user_id = $teacher->user_id;
            $class_section_id = $teacher->class_sections()->where('session_year_id', $session_year_id)->pluck('class_section_id');

            // Find the class in which teacher is assigns as Class Teacher
            $class_teacher = ClassSection::whereIn('id', $class_section_id)->with('class.medium', 'section', 'class.streams', 'class.shifts')->get();

            // Find the Classes in which teacher is taking subjects
            $class_section_ids = $teacher->classes()->where('session_year_id', $session_year_id)->pluck('class_section_id');

            $class_sections = ClassSection::whereIn('id', $class_section_ids)->with('class.medium', 'section', 'class.streams', 'class.shifts')->get();
            $class_section = $class_sections->diff($class_teacher);

            $class_teacher_section_ids = ClassTeacher::where('class_teacher_id', $teacher->id)->where('session_year_id', $session_year_id)->pluck('class_section_id');

            // Teacher assignments
            $classTeacherAssignments = ClassTeacher::with('semester')
                ->where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->get();

            // Base query
            $student_leave_request = Leave::where('status', 0)
                ->where('to_date', '>=', $current_date)
                ->where('session_year_id', $session_year_id)
                ->with('leave_detail', 'user.student.class_section', 'file');

            // Apply teacher access filters. Start from "none": an empty group would be
            // dropped and show a teacher with no class every pending leave in the school.
            $student_leave_request->where(function ($mainQuery) use ($classTeacherAssignments) {
                $mainQuery->whereRaw('1 = 0');
                foreach ($classTeacherAssignments as $assignment) {
                    // FULL SESSION ACCESS (No semester)
                    if (! $assignment->semester_id) {
                        $mainQuery->orWhere(function ($q) use ($assignment) {
                            $q->whereHas('user.student', function ($studentQuery) use ($assignment) {
                                $studentQuery->where('class_section_id', $assignment->class_section_id);
                            });
                        });

                        continue;
                    }

                    // SEMESTER BASED ACCESS
                    $semester = $assignment->semester;

                    if (! $semester) {
                        continue;
                    }

                    // Find next semester
                    $nextSemester = Semester::where('session_year_id', $assignment->session_year_id)
                        ->where('start_date', '>', $semester->end_date)
                        ->orderBy('start_date', 'asc')
                        ->first();

                    // Effective end date including break
                    $effectiveEndDate = $nextSemester
                        ? Carbon::parse($nextSemester->start_date)->subDay()
                        : $semester->end_date;

                    $mainQuery->orWhere(function ($q) use (
                        $assignment,
                        $semester,
                        $effectiveEndDate
                    ) {

                        // Match class section
                        $q->whereHas('user.student', function ($studentQuery) use ($assignment) {
                            $studentQuery->where('class_section_id', $assignment->class_section_id);
                        });

                        // Semester + break range
                        $q->whereBetween('from_date', [
                            $semester->start_date,
                            $effectiveEndDate,
                        ]);
                    });
                }
            });

            // Month filter
            if ($request->month) {
                $student_leave_request->whereHas('leave_detail', function ($q) use ($request) {
                    $q->whereMonth('date', $request->month);
                });
            }

            if ($request->class_section_id) {
                $student_leave_request->whereHas('user.student', function ($q) use ($request) {
                    $q->where('class_section_id', $request->class_section_id);
                });
            }

            $student_leave_request->orderBy('id', 'DESC');

            $student_leave_request = $student_leave_request->get();

            // Calculate total days
            foreach ($student_leave_request as $leave) {

                $totalDays = 0;

                foreach ($leave->leave_detail as $detail) {

                    if ($detail->type == 'Full') {
                        $totalDays += 1;
                    } else {
                        $totalDays += 0.5;
                    }
                }

                $leave->total_days = $totalDays;
            }

            $subject_id = SubjectTeacher::where('teacher_id', $teacher->id)->where('session_year_id', $session_year_id)->pluck('id');
            $timetable = Timetable::whereIn('subject_teacher_id', $subject_id)->where('session_year_id', $session_year_id)
                ->where('day_name', $current_day)->with('class_section', 'subject')->orderBy('start_time', 'ASC')->get();

            $class_ids = ClassSection::with('class')->whereIn('id', $class_teacher_section_ids)->pluck('class_id');

            $sql = ExamClass::with('exam.session_year:id,name', 'exam.timetable.subject', 'class', 'class.medium', 'class.streams')->whereIn('class_id', $class_ids);

            $exam_data_db = $sql->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where('exam_id', $data->exam_id)->whereIn('class_id', $class_ids)->first();
                $starting_date = $starting_date_db['min(date)'];

                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where('exam_id', $data->exam_id)->whereIn('class_id', $class_ids)->first();
                $ending_date = $ending_date_db['max(date)'];

                $currentTime = Carbon::now();
                $current_date = date($currentTime->toDateString());
                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = '1'; // Upcoming = 0 , On Going = 1 , Completed = 2
                } elseif ($current_date < $starting_date) {
                    $exam_status = '0'; // Upcoming = 0 , On Going = 1 , Completed = 2
                } else {
                    $exam_status = '2'; // Upcoming = 0 , On Going = 1 , Completed = 2
                }

                // $request->status  =  0 :- all exams , 1 :- Upcoming , 2 :- On Going , 3 :- Completed

                if ($exam_status == 0) {
                    $exam_data[] = [
                        'id' => $data->exam->id,
                        'name' => $data->exam->name,
                        'description' => $data->exam->description,
                        'publish' => $data->exam->publish,
                        'session_year' => $data->exam->session_year->name,
                        'exam_starting_date' => $starting_date,
                        'exam_ending_date' => $ending_date,
                        'exam_status' => $exam_status,
                        'class_id' => $data->class_id,
                        'class_name' => $data->class->name.'-'.$data->class->medium->name,
                        'class_streams' => $data->class->streams->name ?? null,
                        'exam_timetable' => $data->exam->timetable,
                    ];
                }
            }

            if (! empty($exam_data)) {
                usort($exam_data, function ($a, $b) {
                    return strtotime($a['exam_starting_date']) - strtotime($b['exam_starting_date']);
                });
            }

            $events = Event::where('start_date', '>=', $current_date)->limit(5)->latest()->get();

            foreach ($events as $event) {
                if ($event->type == 'multiple') {
                    $hasdaySchedule = MultipleEvent::where('event_id', $event->id)->first();
                    if ($hasdaySchedule) {
                        $eventsList[] = [
                            'id' => $event->id,
                            'has_day_schedule' => 1,
                            'title' => $event->title,
                            'type' => $event->type,
                            'start_date' => $event->start_date,
                            'end_date' => $event->end_date,
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time,
                            'image' => $event->image,
                            'description' => $event->description,
                        ];
                    } else {
                        $eventsList[] = [
                            'id' => $event->id,
                            'has_day_schedule' => 0,
                            'title' => $event->title,
                            'type' => $event->type,
                            'start_date' => $event->start_date,
                            'end_date' => $event->end_date,
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time,
                            'image' => $event->image,
                            'description' => $event->description,
                        ];
                    }
                } else {
                    $eventsList[] = [
                        'id' => $event->id,
                        'has_day_schedule' => 0,
                        'title' => $event->title,
                        'type' => $event->type,
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date,
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'image' => $event->image,
                        'description' => $event->description,
                    ];
                }
            }

            $staff_leave_requests = Leave::where('status', 1)
                ->where('to_date', '>=', Carbon::now()->format('Y-m-d'))
                ->where('user_id', '!=', $user_id)
                ->whereDoesntHave('user.student')
                ->with(['leave_detail', 'user.roles'])
                ->orderBy('id', 'DESC')
                ->get();

            // Group leave requests by date categories
            $today = Carbon::now()->format('Y-m-d');
            $tomorrow = Carbon::now()->addDay()->format('Y-m-d');
            $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');

            // Filter leave requests into categories
            $staff_leave_data = [
                'today' => [],
                'tomorrow' => [],
                'upcoming' => [],
            ];

            foreach ($staff_leave_requests as $leaveRequest) {
                foreach ($leaveRequest->leave_detail as $detail) {

                    $leaveInfo = [
                        'user_name' => $leaveRequest->user->full_name,
                        'image' => $leaveRequest->user->image,
                        'date' => date('d-m-Y', strtotime($detail->date)),
                        'role' => $leaveRequest->user->roles->pluck('name')->implode(', '),
                        'type' => $detail->type,
                    ];

                    // dd($leaveInfo);
                    if ($detail->date == $today) {
                        $staff_leave_data['today'][] = $leaveInfo;
                    } elseif ($detail->date == $tomorrow) {
                        $staff_leave_data['tomorrow'][] = $leaveInfo;
                    } elseif ($detail->date > $upcoming_date) {
                        $staff_leave_data['upcoming'][] = $leaveInfo;
                    }
                }
            }

            $staff_leave_data['upcoming'] = collect($staff_leave_data['upcoming'])
                ->sortBy(function ($item) {
                    return [$item['date'], $item['user_name']];
                })
                ->values()
                ->toArray();

            $gallery = app(GalleryService::class)->getGallery();

            $data = [
                'class_teacher' => $class_teacher ?? [],
                'other_classes' => $class_section ?? [],
                'student_leave_request' => $student_leave_request ?? [],
                'timetable' => $timetable ?? [],
                'upcoming_exams' => isset($exam_data) ? $exam_data : [],
                'staff_leaves' => $staff_leave_data ?? [],
                'events' => $eventsList ?? [],
                'gallery' => $gallery,
            ];
            return ResponseService::successResponse('Data Fetched Successfully', $data ?? []);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
