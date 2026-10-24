<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\File;
use App\Models\Leave;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Rules\uniqueLessonInClass;
use App\Rules\uniqueTopicInLesson;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Teacher app: lessons, their topics and the files attached to them.
 */
class LessonController extends Controller
{
    use TeacherAccess;

    public function getLesson(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('lesson-list');

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'nullable|numeric',
            'class_section_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'term_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];

            $query = Lesson::lessonteachers()
                ->with('file')
                ->withCount('topic');

            if ($request->filled('lesson_id')) {
                $query->where('id', $request->lesson_id);
            }

            if ($request->filled('class_section_id')) {
                $query->where('class_section_id', $request->class_section_id);
                $this->applyTermFilter($query, $request->class_section_id, $sessionYearId, $request->term_id);
            }

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $data = $query->orderByDesc('id')->get();

            return ResponseService::successResponse('Lesson Fetched Successfully.', $data);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function createLesson(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-create');

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'description' => 'required',
                'class_section_id' => 'required|numeric|exists:class_sections,id',
                'subject_id' => 'required|numeric|exists:subjects,id',
                'term_id' => 'nullable|numeric|exists:terms,id',

                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',
            ]
        );

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueLessonInClass($request->class_section_id, $request->subject_id)],
            ]
        );

        if ($validator2->fails()) {
            return ResponseService::validationError($validator2->errors()->first());
        }

        try {
            $session_year_id = getSettings('session_year')['session_year'];

            // Term (conditional)
            $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
            $isClassHaveTerm = ClassSessionConfig::classHasTerm($classId, $session_year_id);
            $termId = $isClassHaveTerm ? $request->term_id : null;

            if (! $this->teachesSubject($request->class_section_id, $request->subject_id, $termId)) {
                return ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }

            $lesson = new Lesson;
            $lesson->name = $request->name;
            $lesson->description = $request->description;
            $lesson->class_section_id = $request->class_section_id;
            $lesson->subject_id = $request->subject_id;
            $lesson->term_id = $termId;
            $lesson->save();

            if ($request->file) {
                foreach ($request->file as $file) {
                    if ($file['type']) {
                        $lesson_file = new File;
                        $lesson_file->file_name = $file['name'];
                        $lesson_file->modal()->associate($lesson);

                        if ($file['type'] == '1') {
                            $lesson_file->type = 1;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == '2') {
                            $lesson_file->type = 2;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        } elseif ($file['type'] == '3') {
                            $lesson_file->type = 3;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == '4') {
                            $lesson_file->type = 4;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        }
                        $lesson_file->save();
                    }
                }
            }

            return ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateLesson(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-edit');

        $validator = Validator::make(
            $request->all(),
            [
                'lesson_id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'class_section_id' => 'required|numeric',
                'subject_id' => 'required|numeric',

                'edit_file' => 'nullable|array',
                'edit_file.*.id' => 'required|numeric',
                'edit_file.*.type' => 'nullable|in:1,2,3,4',
                'edit_file.*.name' => 'required_with:edit_file.*.type',
                'edit_file.*.link' => 'required_if:edit_file.*.type,2,4',

                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',

                //            'edit_file' => 'nullable|array',
                //            'edit_file.*.id' => 'required|numeric',
                //            'edit_file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
                //            'edit_file.*.name' => 'required_with:edit_file.*.type',
                //            'edit_file.*.link' => 'required_if:edit_file.*.type,youtube_link,other_link',
                //
                //            'file' => 'nullable|array',
                //            'file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
                //            'file.*.name' => 'required_with:file.*.type',
                //            'file.*.thumbnail' => 'required_if:file.*.type,youtube_link,video_upload,other_link',
                //            'file.*.file' => 'required_if:file.*.type,file_upload,video_upload',
                //            'file.*.link' => 'required_if:file.*.type,youtube_link,other_link',

                // Regex for Youtube Link
                // 'file.*.link'=>['required_if:file.*.type,youtube_link','regex:/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((?:\w|-){11})(?:&list=(\S+))?$/'],
                // Regex for Other Link
                // 'file.*.link'=>'required_if:file.*.type,other_link|url'
            ]
        );
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueLessonInClass($request->class_section_id, $request->subject_id, $request->lesson_id)],
            ]
        );
        if ($validator2->fails()) {
            return ResponseService::validationError($validator2->errors()->first());
        }
        try {
            $lesson = Lesson::lessonTeachers()->findOrFail($request->lesson_id);

            // The lesson scope matches class sections and subjects separately, so check the
            // lesson's own pair, and the pair it is being moved to.
            if (! $this->ownsLesson($lesson) || ! $this->teachesSubject($request->class_section_id, $request->subject_id)) {
                return ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }

            $lesson->name = $request->name;
            $lesson->description = $request->description;
            $lesson->class_section_id = $request->class_section_id;
            $lesson->subject_id = $request->subject_id;
            $lesson->save();

            // Update the Old Files
            if ($request->edit_file) {
                foreach ($request->edit_file as $file) {
                    if ($file['type']) {
                        // Only files attached to this lesson can be edited through it.
                        $lesson_file = File::where('modal_type', Lesson::class)
                            ->where('modal_id', $lesson->id)
                            ->find($file['id']);
                        if ($lesson_file) {
                            $lesson_file->file_name = $file['name'];

                            if ($file['type'] == '1') {
                                $lesson_file->type = 1;
                                if (! empty($file['file'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_url'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_url'));
                                    }
                                    $lesson_file->file_url = $file['file']->store('lessons', 'public');
                                }
                            } elseif ($file['type'] == '2') {
                                $lesson_file->type = 2;
                                if (! empty($file['thumbnail'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_thumbnail'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_thumbnail'));
                                    }
                                    $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                                }

                                $lesson_file->file_url = $file['link'];
                            } elseif ($file['type'] == '3') {
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
                                    $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                                }
                            } elseif ($file['type'] == '4') {
                                $lesson_file->type = 4;
                                if (! empty($file['thumbnail'])) {
                                    if (Storage::disk('public')->exists($lesson_file->getRawOriginal('file_thumbnail'))) {
                                        Storage::disk('public')->delete($lesson_file->getRawOriginal('file_thumbnail'));
                                    }
                                    $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                                }
                                $lesson_file->file_url = $file['link'];
                            }

                            $lesson_file->save();
                        }
                    }
                }
            }

            // Add the new Files
            if ($request->file) {
                foreach ($request->file as $file) {
                    if ($file['type']) {
                        $lesson_file = new File;
                        $lesson_file->file_name = $file['name'];
                        $lesson_file->modal()->associate($lesson);

                        if ($file['type'] == '1') {
                            $lesson_file->type = 1;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                        } elseif ($file['type'] == '2') {
                            $lesson_file->type = 2;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        } elseif ($file['type'] == '3') {
                            $lesson_file->type = 3;
                            $lesson_file->file_url = $file['file']->store('lessons', 'public');
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                        } elseif ($file['type'] == '4') {
                            $lesson_file->type = 4;
                            $lesson_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            $lesson_file->file_url = $file['link'];
                        }
                        $lesson_file->save();
                    }
                }
            }

            return ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteLesson(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-delete');

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $lesson = Lesson::lessonteachers()->where('id', $request->lesson_id)->firstOrFail();
            if (! $this->ownsLesson($lesson)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $lesson->delete();

            return ResponseService::successResponse('data_delete_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getTopic(Request $request): JsonResponse
    {
        ResponseService::noPermissionThenSendJson('topic-list');

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            // The teacher app lists and edits each topic's files.
            $data = LessonTopic::lessontopicteachers()
                ->with('file')
                ->where('lesson_id', $request->lesson_id)
                ->orderByDesc('id')
                ->get();

            return ResponseService::successResponse('Topic Fetched Successfully.', $data);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function createTopic(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-create');

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'description' => 'required',
                'lesson_id' => 'required|numeric',
                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',
            ]
        );

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueTopicInLesson($request->lesson_id)],
            ]
        );
        if ($validator2->fails()) {
            return ResponseService::validationError($validator2->errors()->first());
        }

        try {
            if (! $this->ownsLesson(Lesson::lessonTeachers()->find($request->lesson_id))) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $topic = new LessonTopic;
            $topic->name = $request->name;
            $topic->description = $request->description;
            $topic->lesson_id = $request->lesson_id;
            $topic->save();

            if ($request->file) {
                foreach ($request->file as $data) {
                    if ($data['type']) {
                        $file = new File;
                        $file->file_name = $data['name'];
                        $file->modal()->associate($topic);

                        if ($data['type'] == '1') {
                            $file->type = 1;
                            $file->file_url = $data['file']->store('lessons', 'public');
                        } elseif ($data['type'] == '2') {
                            $file->type = 2;
                            $file->file_thumbnail = $data['thumbnail']->store('lessons', 'public');
                            $file->file_url = $data['link'];
                        } elseif ($data['type'] == '3') {
                            $file->type = 3;
                            $file->file_thumbnail = $data['thumbnail']->store('lessons', 'public');
                            $file->file_url = $data['file']->store('lessons', 'public');
                        } elseif (in_array($data['type'], ['4', 'other_link'], true)) { // the app sends 4
                            $file->type = 4;
                            $file->file_thumbnail = $data['thumbnail']->store('lessons', 'public');
                            $file->file_url = $data['link'];
                        }

                        $file->save();
                    }
                }
            }

            return ResponseService::successResponse('data_store_successfully');
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateTopic(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-edit');
        $validator = Validator::make(
            $request->all(),
            [
                'topic_id' => 'required|numeric',
                'name' => 'required',
                'description' => 'required',
                'edit_file' => 'nullable|array',
                'edit_file.*.type' => 'nullable|in:1,2,3,4',
                'edit_file.*.name' => 'required_with:edit_file.*.type',
                'edit_file.*.link' => 'required_if:edit_file.*.type,2,',

                'file' => 'nullable|array',
                'file.*.type' => 'nullable|in:1,2,3,4',
                'file.*.name' => 'required_with:file.*.type',
                'file.*.thumbnail' => 'required_if:file.*.type,2,3,4',
                'file.*.file' => 'required_if:file.*.type,1,3',
                'file.*.link' => 'required_if:file.*.type,2,4',
            ]
        );
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        $validator2 = Validator::make(
            $request->all(),
            [
                'name' => ['required', new uniqueTopicInLesson($request->lesson_id, $request->topic_id)],
            ]
        );
        if ($validator2->fails()) {
            return ResponseService::validationError($validator2->errors()->first());
        }
        try {
            $topic = LessonTopic::lessonTopicTeachers()->findOrFail($request->topic_id);

            if (! $this->ownsLesson($topic->lesson)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $topic->name = $request->name;
            $topic->description = $request->description;
            $topic->save();

            // Update the Old Files
            if ($request->edit_file) {
                foreach ($request->edit_file as $key => $file) {
                    if ($file['type']) {
                        // Only files attached to this topic can be edited through it.
                        $topic_file = File::where('modal_type', LessonTopic::class)
                            ->where('modal_id', $topic->id)
                            ->find($file['id']);
                        if (! $topic_file) {
                            continue;
                        }
                        $topic_file->file_name = $file['name'];

                        if ($file['type'] == '1') {
                            // Type File :- File Upload
                            $topic_file->type = 1;
                            if (! empty($file['file'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_url'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_url'));
                                }
                                $topic_file->file_url = $file['file']->store('lessons', 'public');
                            }
                        } elseif ($file['type'] == '2') {
                            // Type File :- Youtube Link Upload
                            $topic_file->type = 2;
                            if (! empty($file['thumbnail'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_thumbnail'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_thumbnail'));
                                }
                                $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            }

                            $topic_file->file_url = $file['link'];
                        } elseif ($file['type'] == '3') {
                            // Type File :- Vedio Upload
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
                                $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            }
                        } elseif ($file['type'] == '4') {
                            $topic_file->type = 4;
                            if (! empty($file['thumbnail'])) {
                                if (Storage::disk('public')->exists($topic_file->getRawOriginal('file_thumbnail'))) {
                                    Storage::disk('public')->delete($topic_file->getRawOriginal('file_thumbnail'));
                                }
                                $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                            }
                            $topic_file->file_url = $file['link'];
                        }

                        $topic_file->save();
                    }
                }
            }

            // Add the new Files
            if ($request->file) {
                foreach ($request->file as $file) {
                    $topic_file = new File;
                    $topic_file->file_name = $file['name'];
                    $topic_file->modal()->associate($topic);

                    if ($file['type'] == '1') {
                        $topic_file->type = 1;
                        $topic_file->file_url = $file['file']->store('lessons', 'public');
                    } elseif ($file['type'] == '2') {
                        $topic_file->type = 2;
                        $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                        $topic_file->file_url = $file['link'];
                    } elseif ($file['type'] == '3') {
                        $topic_file->type = 3;
                        $topic_file->file_url = $file['file']->store('lessons', 'public');
                        $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                    } elseif ($file['type'] == '4') {
                        $topic_file->type = 4;
                        $topic_file->file_thumbnail = $file['thumbnail']->store('lessons', 'public');
                        $topic_file->file_url = $file['link'];
                    }
                    $topic_file->save();
                }
            }

            return ResponseService::successResponse('data_store_successfully');
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteTopic(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-delete');

        try {
            $topic = LessonTopic::LessonTopicTeachers()->findOrFail($request->topic_id);
            if (! $this->ownsLesson($topic->lesson)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $topic->delete();

            return ResponseService::successResponse('data_delete_successfully');
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function updateFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $file = File::find($request->file_id);
            if (! $file || ! $this->canManageFile($file)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $file->file_name = $request->name;

            if ($file->type == '1') {
                // Type File :- File Upload

                if (! empty($request->file)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }

                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_url = $request->file->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_url = $request->file->store('topics', 'public');
                    } else {

                        $file->file_url = $request->file->store('other', 'public');
                    }
                }
            } elseif ($file->type == '2') {
                // Type File :- Youtube Link Upload

                if (! empty($request->thumbnail)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_thumbnail'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_thumbnail'));
                    }

                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_thumbnail = $request->thumbnail->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_thumbnail = $request->thumbnail->store('topics', 'public');
                    } else {

                        $file->file_thumbnail = $request->thumbnail->store('other', 'public');
                    }
                }
                $file->file_url = $request->link;
            } elseif ($file->type == '3') {
                // Type File :- Vedio Upload

                if (! empty($request->file)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }

                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_url = $request->file->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_url = $request->file->store('topics', 'public');
                    } else {

                        $file->file_url = $request->file->store('other', 'public');
                    }
                }

                if (! empty($request->thumbnail)) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_thumbnail'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_thumbnail'));
                    }
                    if ($file->modal_type == "App\Models\Lesson") {

                        $file->file_thumbnail = $request->thumbnail->store('lessons', 'public');
                    } elseif ($file->modal_type == "App\Models\LessonTopic") {

                        $file->file_thumbnail = $request->thumbnail->store('topics', 'public');
                    } else {

                        $file->file_thumbnail = $request->thumbnail->store('other', 'public');
                    }
                }
            }
            $file->save();

            return ResponseService::successResponse('data_store_successfully', $file);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $file = File::find($request->file_id);
            if (! $file || ! $this->canManageFile($file)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $file->delete();

            return ResponseService::successResponse('data_delete_successfully');
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /** Whether the teacher may change or delete a stored file, judged by what the file is attached to. */
    private function canManageFile(File $file): bool
    {
        return match ($file->modal_type) {
            Lesson::class => $this->ownsLesson(Lesson::lessonTeachers()->find($file->modal_id)),
            LessonTopic::class => $this->ownsLesson(
                LessonTopic::lessonTopicTeachers()->find($file->modal_id)?->lesson
            ),
            Assignment::class => Assignment::assignmentTeachers()->whereKey($file->modal_id)->exists(),
            Announcement::class => $this->ownsAnnouncement(Announcement::find($file->modal_id)),
            Leave::class => Leave::whereKey($file->modal_id)->where('user_id', Auth::id())->exists(),
            default => false,
        };
    }
}
