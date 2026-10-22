<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Platform\Files\FileNames;
use App\Services\ResponseService;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Assignment;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\File;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Semester;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! Auth::user()->can('lesson-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $sessionYearId = getSettings('session_year')['session_year'] ?? null;

        $classSections = ClassSection::SubjectTeacher()
            ->with('class.medium', 'section', 'class.streams')
            ->when($sessionYearId, function ($query) use ($sessionYearId) {
                $query->whereHas('subject_teachers', function ($q) use ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                });
            })
            ->get();
        $user = Auth::user();

        $unique_class_sections = [];

        foreach ($classSections as $section) {
            if (! isset($unique_class_sections[$section->id])) {
                $baseName = $section->class->name.' '.$section->section->name.' - '.$section->class->medium->name
                    .' '.($section->class->streams->name ?? '');
                $unique_class_sections[$section->id] = (object) [
                    'id' => $section->id,
                    'class_id' => $section->class->id,
                    'name' => trim($baseName),
                ];
            }
        }

        $unique_class_sections = array_values($unique_class_sections);

        return response(view('lessons.index', compact('unique_class_sections')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLessonRequest $request)
    {
        try {
            DB::beginTransaction();
            $lesson = new Lesson;
            $lesson->name = $request->name;
            $lesson->description = $request->description;
            $lesson->class_section_id = $request->class_section_id;
            $lesson->subject_id = $request->subject_id;
            $lesson->semester_id = $request->semester_id ?: null;
            $lesson->save();

            foreach ($request->file as $key => $file) {
                if ($file['type']) {
                    $lesson_file = new File;
                    $lesson_file->file_name = $file['name'];
                    $lesson_file->modal()->associate($lesson);

                    if ($file['type'] == 'file_upload') {
                        $lesson_file->type = 1;
                        $lesson_file->file_url = $file['file']->store('lessons', 'public');
                    } elseif ($file['type'] == 'youtube_link') {
                        $lesson_file->type = 2;

                        $image = $file['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.FileNames::forUpload($image);
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $lesson_file->file_thumbnail = $file_path;
                        $lesson_file->file_url = $file['link'];
                    } elseif ($file['type'] == 'video_upload') {
                        $lesson_file->type = 3;

                        $image = $file['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.FileNames::forUpload($image);
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $lesson_file->file_thumbnail = $file_path;

                        $lesson_file->file_url = $file['file']->store('lessons', 'public');
                    } elseif ($file['type'] == 'other_link') {
                        $lesson_file->type = 4;

                        $image = $file['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.FileNames::forUpload($image);
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $lesson_file->file_thumbnail = $file_path;
                        $lesson_file->file_url = $file['link'];
                    }
                    $lesson_file->save();
                }
            }

            DB::commit();
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubjectLesson  $subjectLesson
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (! Auth::user()->can('lesson-list')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        $sql = Lesson::lessonteachers()->with('subject', 'class_section', 'topic', 'semester');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('name', 'LIKE', "%$search%")
                    ->orwhere('description', 'LIKE', "%$search%")
                    ->orwhere('created_at', 'LIKE', '%'.convertDateFormat($search).'%')
                    ->orwhere('updated_at', 'LIKE', '%'.convertDateFormat($search).'%')
                    ->orWhereHas('class_section.section', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('class_section.class', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })->orWhereHas('subject', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    });
            });
        }
        if (! empty($_GET['subject_id'])) {
            $sql = $sql->where('subject_id', $_GET['subject_id']);
        }
        if (! empty($_GET['class_id'])) {
            $sql = $sql->where('class_section_id', $_GET['class_id']);
        }
        if (! empty($_GET['semester_id'])) {
            $sql = $sql->where('semester_id', $_GET['semester_id']);
        }
        if (! empty($_GET['lesson_id'])) {
            $sql = $sql->where('id', $_GET['lesson_id']);
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $lessonIds = $res->pluck('id')->toArray();
        $lessonsWithTopics = LessonTopic::whereIn('lesson_id', $lessonIds)->pluck('lesson_id')->unique()->toArray();
        $lessonsWithFiles = File::where('modal_type', 'App\Models\Lesson')->whereIn('modal_id', $lessonIds)->pluck('modal_id')->unique()->toArray();

        foreach ($res as $row) {

            $row = (object) $row;
            $relatedData = [];
            if (in_array($row->id, $lessonsWithTopics)) {
                $relatedData[] = 'lesson topics';
            }
            if (in_array($row->id, $lessonsWithFiles)) {
                $relatedData[] = 'lesson files';
            }

            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="'.route('lesson.destroy', $row->id).'"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    data-id="'.$row->id.'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            $tempRow['class_section_id'] = $row->class_section_id;
            $tempRow['class_section_name'] = $row->class_section->class->name.' '.$row->class_section->section->name.' - '.$row->class_section->class->medium->name;
            $tempRow['semester_id'] = $row->semester_id;
            $tempRow['semester_name'] = $row->semester ? $row->semester->name : '-';
            $tempRow['subject_id'] = $row->subject_id;
            $tempRow['subject_name'] = $row->subject->name.' - '.$row->subject->type;
            $tempRow['topic'] = $row->topic;
            $tempRow['file'] = $row->file;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\SubjectLesson  $subjectLesson
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLessonRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $lesson = Lesson::findOrFail($request->edit_id);
            $lesson->name = $request->name;
            $lesson->description = $request->description;
            $lesson->semester_id = $request->semester_id ?: null;
            $lesson->save();

            // Update the Old Files
            foreach ($request->edit_file ?? [] as $file) {
                if ($file['type']) {
                    $lesson_file = File::find($file['id']);
                    $lesson_file->file_name = $file['name'];

                    if ($file['type'] == 'file_upload') {
                        $lesson_file->type = 1;
                        if (! empty($file['file'])) {
                            if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                            }
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        }
                    } elseif ($file['type'] == 'youtube_link') {
                        $lesson_file->type = 2;
                        if (! empty($file['thumbnail'])) {
                            if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_thumbnail'))) {
                                Storage::disk('public')->delete($lesson_file->getRawOriginal('file_thumbnail'));
                            }

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.FileNames::forUpload($image);
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $lesson_file->file_thumbnail = $file_path;
                        }

                        $lesson_file->file_url = $file['link'];
                    } elseif ($file['type'] == 'video_upload') {
                        $lesson_file->type = 3;
                        if (! empty($file['file'])) {
                            if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                            }
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        }

                        if (! empty($file['thumbnail'])) {
                            if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_thumbnail'))) {
                                Storage::disk('public')->delete($lesson_file->getRawOriginal('file_thumbnail'));
                            }

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.FileNames::forUpload($image);
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $lesson_file->file_thumbnail = $file_path;
                        }
                    } elseif ($file['type'] == 'other_link') {
                        $lesson_file->type = 4;
                        if (! empty($file['thumbnail'])) {
                            if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_thumbnail'))) {
                                Storage::disk('public')->delete($lesson_file->getRawOriginal('file_thumbnail'));
                            }

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.FileNames::forUpload($image);
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $lesson_file->file_thumbnail = $file_path;
                        }
                        $lesson_file->file_url = $file['link'];
                    }

                    $lesson_file->save();
                }
            }

            // Add the new Files
            if ($request->file) {
                foreach ($request->file as $key => $file) {
                    if ($file['type']) {
                        $lesson_file = new File;
                        $lesson_file->file_name = $file['name'];
                        $lesson_file->modal()->associate($lesson);

                        if ($file['type'] == 'file_upload') {
                            $lesson_file->type = 1;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == 'youtube_link') {
                            $lesson_file->type = 2;

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.FileNames::forUpload($image);
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $lesson_file->file_thumbnail = $file_path;
                            $lesson_file->file_url = $file['link'];
                        } elseif ($file['type'] == 'video_upload') {
                            $lesson_file->type = 3;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.FileNames::forUpload($image);
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $lesson_file->file_thumbnail = $file_path;
                        } elseif ($file['type'] == 'other_link') {
                            $lesson_file->type = 4;

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.FileNames::forUpload($image);
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $lesson_file->file_thumbnail = $file_path;
                            $lesson_file->file_url = $file['link'];
                        }
                        $lesson_file->save();
                    }
                }
            }

            DB::commit();
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'exception' => $e,
            ];
        }

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubjectLesson  $subjectLesson
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('lesson-delete')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $lesson = Lesson::findOrFail($id);

            // Cascade handled at model level
            $lesson->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function search(Request $request)
    {
        $lesson = new Lesson;
        if (isset($request->subject_id)) {
            $lesson = $lesson->where('subject_id', $request->subject_id);
        }

        if (isset($request->class_section_id)) {
            $lesson = $lesson->where('class_section_id', $request->class_section_id);
        }

        if (! empty($request->semester_id)) {
            $lesson = $lesson->where('semester_id', $request->semester_id);
        }
        $lesson = $lesson->get();
        $response = [
            'error' => false,
            'data' => $lesson,
            'message' => 'Lesson fetched successfully',
        ];

        return response()->json($response);
    }

    public function deleteFile($id)
    {
        ResponseService::noPermissionThenSendJson('lesson-delete');
        try {
            $file = File::findOrFail($id);
            // Only the lesson, topic and assignment pages remove files through this route.
            $editPermission = [
                Lesson::class => 'lesson-edit',
                LessonTopic::class => 'topic-edit',
                Assignment::class => 'assignment-edit',
            ][$file->modal_type] ?? null;
            if (! $editPermission || ! Auth::user()->can($editPermission)) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ]);
            }
            if (Storage::disk('public')->exists($file->file_url)) {
                Storage::disk('public')->delete($file->file_url);
            }
            $file->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getTeacherSemesterSubjects(Request $request)
    {
        $currentSemester = Semester::get()->first(function ($semester) {
            return $semester->current;
        });

        $session_year_id = (int) (
            $request->input('session_year_id')
            ?: getSettings('session_year')['session_year']
        );

        $classSectionId = (int) $request->input('class_section_id');

        $classSection = ClassSection::select('class_id')
            ->where('id', $classSectionId)
            ->first();

        if (! $classSection) {
            return response()->json([]);
        }

        $class = ClassSchool::withSessionConfigFor($session_year_id)
            ->find($classSection->class_id);

        if (! $class) {
            return response()->json([]);
        }

        $subjects = collect();
        $teacher_id = Teacher::where('user_id', Auth::id())->value('id');

        $subjects = SubjectTeacher::where('teacher_id', $teacher_id)
            ->where('class_section_id', $classSectionId)
            ->where('session_year_id', $session_year_id)
            ->with('subject', 'semester')
            ->get();

        return response()->json($subjects);
    }
}
