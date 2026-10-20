<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonTopicRequest;
use App\Http\Requests\UpdateLessonTopicRequest;
use App\Models\ClassSection;
use App\Models\File;
use App\Models\LessonTopic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LessonTopicController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! Auth::user()->can('topic-list')) {
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

        return response(view('lessons.topic', compact('unique_class_sections')));
    }

    /**
     * /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLessonTopicRequest $request)
    {
        try {
            DB::beginTransaction();
            $topic = new LessonTopic;
            $topic->name = $request->name;
            $topic->description = $request->description;
            $topic->lesson_id = $request->lesson_id;
            $topic->save();

            foreach ($request->file ?? [] as $data) {
                if ($data['type']) {
                    $file = new File;
                    $file->file_name = $data['name'];
                    $file->modal()->associate($topic);

                    if ($data['type'] == 'file_upload') {
                        $file->type = 1;
                        $file->file_url = $data['file']->store('lessons', 'public');
                    } elseif ($data['type'] == 'youtube_link') {
                        $file->type = 2;

                        $image = $data['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $file->file_thumbnail = $file_path;
                        $file->file_url = $data['link'];
                    } elseif ($data['type'] == 'video_upload') {
                        $file->type = 3;

                        $image = $data['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $file->file_thumbnail = $file_path;
                        $file->file_url = $data['file']->store('lessons', 'public');
                    } elseif ($data['type'] == 'other_link') {
                        $file->type = 4;

                        $image = $data['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $file->file_thumbnail = $file_path;

                        $file->file_url = $data['link'];
                    }

                    $file->save();
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
     * @param  \App\Models\LessonTopic  $lessonTopic
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (! Auth::user()->can('topic-list')) {
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

        $sql = LessonTopic::lessontopicteachers()->with('lesson.class_section', 'lesson.subject', 'lesson.semester', 'file');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('name', 'LIKE', "%$search%")
                    ->orwhere('description', 'LIKE', "%$search%")
                    ->orWhereHas('lesson.class_section.section', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })->orWhereHas('lesson.class_section.class', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })->orWhereHas('lesson.subject', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    })->orWhereHas('lesson', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    });
            });
        }
        if (! empty($_GET['subject_id'])) {

            $sql = $sql->whereHas('lesson', function ($q) {
                $q->where('subject_id', $_GET['subject_id']);
            });
        }
        if (! empty($_GET['class_id'])) {

            $sql = $sql->whereHas('lesson', function ($q) {
                $q->where('class_section_id', $_GET['class_id']);
            });
        }
        if (! empty($_GET['semester_id'])) {
            $sql = $sql->whereHas('lesson', function ($q) {
                $q->where('semester_id', $_GET['semester_id']);
            });
        }
        if (! empty($_GET['lesson_id'])) {
            $sql = $sql->where('lesson_id', $_GET['lesson_id']);
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $row) {

            $row = (object) $row;
            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href='.route('lesson-topic.destroy', $row->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            $tempRow['lesson_id'] = $row->lesson_id;
            $tempRow['lesson_name'] = $row->lesson->name;
            $tempRow['class_section_id'] = $row->lesson->class_section->id;
            $tempRow['class_section_name'] = $row->lesson->class_section->class->name.' '.$row->lesson->class_section->section->name.' - '.$row->lesson->class_section->class->medium->name;
            $tempRow['semester_name'] = $row->lesson->semester ? $row->lesson->semester->name : '-';
            $tempRow['subject_id'] = $row->lesson->subject->id;
            $tempRow['subject_name'] = $row->lesson->subject->name.' - '.$row->lesson->subject->type;
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
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLessonTopicRequest $request, LessonTopic $lessonTopic)
    {
        try {
            DB::beginTransaction();
            $topic = LessonTopic::findOrFail($request->edit_id);
            $topic->name = $request->name;
            $topic->description = $request->description;
            $topic->save();

            // Update the Old Files
            foreach ($request->edit_file ?? [] as $key => $file) {
                if ($file['type']) {
                    $topic_file = File::find($file['id']);
                    $topic_file->file_name = $file['name'];

                    if ($file['type'] == 'file_upload') {
                        $topic_file->type = 1;
                        if (! empty($file['file'])) {
                            if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                            }
                            $topic_file->file_url = $file['file']->store('lessons', 'public');
                        }
                    } elseif ($file['type'] == 'youtube_link') {
                        $topic_file->type = 2;
                        if (! empty($file['thumbnail'])) {
                            if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_thumbnail'))) {
                                Storage::disk('public')->delete($topic_file->getRawOriginal('file_thumbnail'));
                            }

                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.$image->getClientOriginalName();
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $topic_file->file_thumbnail = $file_path;
                        }

                        $topic_file->file_url = $file['link'];
                    } elseif ($file['type'] == 'video_upload') {
                        $topic_file->type = 3;
                        if (! empty($file['file'])) {
                            if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                            }
                            $topic_file->file_url = $file['file']->store('lessons', 'public');
                        }

                        if (! empty($file['thumbnail'])) {
                            if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_thumbnail'))) {
                                Storage::disk('public')->delete($topic_file->getRawOriginal('file_thumbnail'));
                            }
                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.$image->getClientOriginalName();
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $topic_file->file_thumbnail = $file_path;
                        }
                    } elseif ($file['type'] == 'other_link') {
                        $topic_file->type = 4;
                        if (! empty($file['thumbnail'])) {
                            if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_thumbnail'))) {
                                Storage::disk('public')->delete($topic_file->getRawOriginal('file_thumbnail'));
                            }
                            $image = $file['thumbnail'];
                            // made file name with combination of current time
                            $file_name = time().'-'.$image->getClientOriginalName();
                            // made file path to store in database
                            $file_path = 'lessons/'.$file_name;
                            // resized image
                            resizeImage($image);
                            // stored image to storage/public/lessons folder
                            $destinationPath = storage_path('app/public/lessons');
                            $image->move($destinationPath, $file_name);

                            $topic_file->file_thumbnail = $file_path;
                        }
                        $topic_file->file_url = $file['link'];
                    }

                    $topic_file->save();
                }
            }

            // Add the new Files
            if ($request->file) {
                foreach ($request->file as $key => $file) {
                    $topic_file = new File;
                    $topic_file->file_name = $file['name'];
                    $topic_file->modal()->associate($topic);

                    if ($file['type'] == 'file_upload') {
                        $topic_file->type = 1;
                        $topic_file->file_url = $file['file']->store('lessons', 'public');
                    } elseif ($file['type'] == 'youtube_link') {
                        $topic_file->type = 2;
                        $image = $file['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $topic_file->file_thumbnail = $file_path;
                        $topic_file->file_url = $file['link'];
                    } elseif ($file['type'] == 'video_upload') {
                        $topic_file->type = 3;
                        $topic_file->file_url = $file['file']->store('lessons', 'public');

                        $image = $file['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $topic_file->file_thumbnail = $file_path;
                    } elseif ($file['type'] == 'other_link') {
                        $topic_file->type = 4;

                        $image = $file['thumbnail'];
                        // made file name with combination of current time
                        $file_name = time().'-'.$image->getClientOriginalName();
                        // made file path to store in database
                        $file_path = 'lessons/'.$file_name;
                        // resized image
                        resizeImage($image);
                        // stored image to storage/public/lessons folder
                        $destinationPath = storage_path('app/public/lessons');
                        $image->move($destinationPath, $file_name);

                        $topic_file->file_thumbnail = $file_path;
                        $topic_file->file_url = $file['link'];
                    }
                    $topic_file->save();
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
     * @param  \App\Models\LessonTopic  $lessonTopic
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('topic-delete')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $topic = LessonTopic::find($id);
            if ($topic->file) {
                foreach ($topic->file as $file) {
                    if (Storage::disk('public')->exists($file->file_url)) {
                        Storage::disk('public')->delete($file->file_url);
                    }
                }
            }
            $topic->file()->delete();
            $topic->delete();
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
}
