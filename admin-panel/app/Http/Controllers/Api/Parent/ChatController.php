<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\ChatFile;
use App\Models\ChatMessage;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\File;
use App\Models\Parents;
use App\Models\ReadMessage;
use App\Models\Semester;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Parent app: chat with the teachers of the parent's children.
 */
class ChatController extends Controller
{
    use ParentAccess;

    public function getChatUserList(Request $request)
    {
        try {
            $offset = $request->offset;
            $limit = $request->limit;
            $search = $request->search;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $user = $request->user();
            $parent = $user->parent;

            // Get current semester (once, shared across all children)
            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            // The same teacher set sendMessage() allows as receivers.
            $scope = $this->chatTeacherScope($parent, $session_year_id, $currentSemester);
            $childrens = $scope['childrens'];
            $semesterClassSectionIds = $scope['semester_class_section_ids'];
            $nonSemesterClassSectionIds = $scope['non_semester_class_section_ids'];
            $teacher_ids = $scope['teacher_ids'];

            // --- Teacher query (unchanged) ---
            if ($search) {
                $teachers = Teacher::whereIn('id', $teacher_ids)
                    ->where(function ($query) use ($search) {
                        $query->whereHas('user', function ($subquery) use ($search) {
                            $subquery->where('first_name', 'like', "%$search%")
                                ->orWhere('last_name', 'like', "%$search%");
                        });
                    })
                    ->with('user:id,first_name,last_name,image,mobile,email', 'subjects.subject')
                    ->offset($offset)->limit($limit)->get();
            } else {
                $teachers = Teacher::whereIn('id', $teacher_ids)
                    ->with('user:id,first_name,last_name,image,mobile,email', 'subjects.subject')
                    ->offset($offset)->limit($limit)->get();
            }

            $data = [];

            foreach ($teachers as $teacher) {
                $unreadCount = 0;
                $subjectData = [];

                // --- Semester-aware class teacher section lookup ---
                $class_teacher_section_ids = [];

                foreach ($semesterClassSectionIds as $csi => $semId) {
                    $ids = ClassTeacher::where('class_teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->where('semester_id', $semId)
                        ->where('class_section_id', $csi)
                        ->pluck('class_section_id')->toArray();
                    $class_teacher_section_ids = array_merge($class_teacher_section_ids, $ids);
                }

                if (! empty($nonSemesterClassSectionIds)) {
                    $ids = ClassTeacher::where('class_teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->whereIn('class_section_id', $nonSemesterClassSectionIds)
                        ->pluck('class_section_id')->toArray();
                    $class_teacher_section_ids = array_merge($class_teacher_section_ids, $ids);
                }

                // --- Semester-aware subject teacher section lookup ---
                $subject_teacher_class_section_ids = [];

                foreach ($semesterClassSectionIds as $csi => $semId) {
                    $ids = SubjectTeacher::where('teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->where('semester_id', $semId)
                        ->where('class_section_id', $csi)
                        ->pluck('class_section_id')->toArray();
                    $subject_teacher_class_section_ids = array_merge($subject_teacher_class_section_ids, $ids);
                }

                if (! empty($nonSemesterClassSectionIds)) {
                    $ids = SubjectTeacher::where('teacher_id', $teacher->id)
                        ->where('session_year_id', $session_year_id)
                        ->whereIn('class_section_id', $nonSemesterClassSectionIds)
                        ->pluck('class_section_id')->toArray();
                    $subject_teacher_class_section_ids = array_merge($subject_teacher_class_section_ids, $ids);
                }

                $teacher_class_section_ids = array_unique(
                    array_merge($class_teacher_section_ids, $subject_teacher_class_section_ids)
                );

                $classes = ClassSection::whereIn('id', $class_teacher_section_ids)
                    ->with('class.medium', 'section', 'class.streams', 'class.shifts')->get();

                $studentsArray = [];
                foreach ($childrens as $child) {
                    $students = Students::with('user')
                        ->where('id', $child->id)
                        ->whereIn('class_section_id', $teacher_class_section_ids)
                        ->get();

                    foreach ($students as $student) {
                        $studentsArray[] = $student->user->first_name.' '.$student->user->last_name;
                    }
                }

                $class_names = [];
                foreach ($classes as $class) {
                    if ($class) {
                        $class_names[] = ($class->class->name ?? '').' '
                            .($class->section->name ?? '').' '
                            .($class->class->medium->name ?? '');
                    }
                }

                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = [
                        'id' => $subject->subject->id ?? '',
                        'name' => $subject->subject->name ?? '',
                    ];
                }

                $lastMessage = ChatMessage::with('file')->where(function ($query) use ($user, $teacher) {
                    $query->where('modal_id', $teacher->user->id)
                        ->where('sender_id', $user->id);
                })
                    ->orWhere(function ($query) use ($user, $teacher) {
                        $query->where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id);
                    })
                    ->select('id', 'body', 'date')
                    ->latest('id')
                    ->first();

                $lastReadMessage = ReadMessage::where('modal_id', $user->id)
                    ->where('user_id', $teacher->user->id)->first();

                if ($lastReadMessage) {
                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                    if (! empty($lastReadMessageId)) {
                        $unreadCount = ChatMessage::where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id)
                            ->where('id', '>', $lastReadMessageId)->count();
                    } else {
                        $unreadCount = ChatMessage::where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id)->count();
                    }
                }

                $data[] = [
                    'id' => $teacher->id,
                    'user_id' => $teacher->user->id,
                    'first_name' => $teacher->user->first_name,
                    'last_name' => $teacher->user->last_name,
                    'qualification' => $teacher->qualification,
                    'email' => $teacher->user->email,
                    'image' => $teacher->user->image,
                    'class_teacher' => $class_names,
                    // No dob: a teacher's date of birth is their default password.
                    'mobile_no' => $teacher->user->mobile,
                    'subjects' => $subjectData,
                    'student_name' => $studentsArray ?? [],
                    'last_message' => $lastMessage ?? null,
                    'unread_message' => $unreadCount ?? 0,
                ];
            }

            $total_items = count($data);

            $unreadusers = array_filter($data, fn ($t) => $t['unread_message'] > 0);
            $totalunreadusers = count($unreadusers);

            $data = collect($data)->sortByDesc(function ($user) {
                return optional($user['last_message'])->id ?? 0;
            })->values();

            ResponseService::successResponse('Data Fetched Successfully', [
                'items' => $data,
                'total_items' => $total_items,
                'total_unread_users' => $totalunreadusers,
            ], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function sendMessage(Request $request)
    {
        // Limits from the chat settings (the same values the app is given).
        $chatSettings = getSettings();
        $maxFiles = max(1, (int) ($chatSettings['max_files_or_images_in_one_message'] ?? 10));
        $maxFileKb = max(1, (int) ceil(((float) ($chatSettings['max_file_size_in_bytes'] ?? 10)) * 1000000 / 1024));

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer',
            'message' => 'required_without:file',
            'file' => 'nullable|array|max:'.$maxFiles,
            'file.*' => 'file|max:'.$maxFileKb,
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sender_id = $request->user()->id;
            $receiver_id = (int) $request->receiver_id;

            // Only the teachers getChatUserList offers this parent.
            if (! in_array($receiver_id, $this->chatAllowedUserIds($request), true)) {
                ResponseService::errorResponse('You can not send a message to this user', null, 103);
            }

            $message = new ChatMessage;
            $message->modal_id = $receiver_id;
            $message->modal_type = 'App/Models/User';
            $message->sender_id = $sender_id;
            $message->body = $request->message ?? '';
            $message->date = Carbon::now();
            $message->save();
            $count = 0;
            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $uploadedFile) {
                    // Each file gets its own generated folder, so a client-chosen name can't
                    // overwrite another file or leave chatfile/. The app shows the last URL
                    // segment, so a cleaned-up original name is kept as the file name.
                    $originalName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename(str_replace('\\', '/', $uploadedFile->getClientOriginalName())));
                    $originalName = ltrim(mb_substr((string) $originalName, -120), '._');
                    $filePath = $originalName !== ''
                        ? $uploadedFile->storeAs('chatfile/'.Str::random(32), $originalName, 'public')
                        : $uploadedFile->store('chatfile', 'public');

                    $file = new ChatFile;
                    $file->file_type = 1;
                    $file->file_name = $filePath;
                    $file->message_id = $message->id;
                    $file->save();
                    $count++;
                }
            }

            $readMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $sender_id)->first();
            if (empty($readMessage)) {
                $readMessage = new ReadMessage;
                $readMessage->modal_id = $receiver_id;
                $readMessage->modal_type = 'App/Models/User';
                $readMessage->user_id = $sender_id;
                $readMessage->save();
            }

            $message = ChatMessage::with('file')->where('id', $message->id)->select('id', 'sender_id', 'body', 'date')->get();

            foreach ($message as $message) {
                $chatfile = [];
                foreach ($message->file as $file) {
                    if (! empty($file)) {
                        $chatfile[] = asset('storage/'.$file->file_name);
                    } else {
                        $chatfile[] = '';
                    }
                }

                $data = [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'body' => $message->body,
                    'date' => $message->date,
                    'files' => $chatfile,
                ];
            }

            $parent = Parents::with('user')->where('user_id', $sender_id)->first();

            $lastReadMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $parent->user_id)->first();

            if ($lastReadMessage) {

                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                if (! empty($lastReadMessageId)) {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $parent->user_id)->where('id', '>', $lastReadMessageId)->count();
                } else {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $parent->user_id)->count();
                }
            }

            $children = $parent->children()->with('user', 'class_section')->get();

            foreach ($children as $child) {
                $child_subject = $child->subjects();

                $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

                $elective_subjects = $child_subject['elective_subject'] ?? [];

                if ($elective_subjects) {
                    $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                }

                $subject_id = array_merge($core_subjects, $elective_subjects);

                $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                $subjectArray = [];
                foreach ($subjects as $subject) {
                    $subjectArray[] = [
                        'id' => $subject->id,
                        'name' => $subject->name,
                    ];
                }

                $childArray[] = [
                    'id' => $child->id,
                    'user_id' => $child->user_id,
                    'child_name' => $child->user->first_name.' '.$child->user->last_name,
                    'class_name' => $child->class_section->class->name.' '.$child->class_section->section->name.' '.$child->class_section->class->medium->name,
                    'admission_no' => $child->admission_no,
                    'image' => $child->user->image,
                    'subject' => $subjectArray ?? [],
                ];
            }

            $userinfo = [
                'id' => $parent->id,
                'user_id' => $parent->user_id, // Assuming this is the correct property name
                'first_name' => $parent->user->first_name,
                'last_name' => $parent->user->last_name,
                'email' => $parent->user->email,
                'mobile_no' => $parent->user->mobile,
                'occupation' => $parent->occupation,
                'image' => $parent->user->image,
                'last_message' => $data ?? null,
                'children' => $childArray ?? [],
                'isParent' => 1,
                'unread_message' => $unreadCount ?? 0,
            ];

            $title = $parent->user->first_name.' '.$parent->user->last_name;
            $body = $request->message ?? $count.' Files Received';
            $type = 'chat';
            $image = null;
            $user[] = $receiver_id;

            $userinfo = (object) $userinfo;
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            ResponseService::successResponse('Message Sent Successfully', $data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getUserChatMessage(Request $request)
    {
        try {

            $offset = $request->offset;
            $limit = $request->limit;

            $messages = ChatMessage::with(['file' => function ($query) {
                $query->select('message_id', 'file_name');
            }])
                // Only the conversation between the parent and that user.
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('sender_id', Auth::id())->where('modal_id', $request->user_id);
                    })->orWhere(function ($q) use ($request) {
                        $q->where('sender_id', $request->user_id)->where('modal_id', Auth::id());
                    });
                })
                ->select('id', 'sender_id', 'body', 'date')
                ->latest('id');

            $total_items = $messages->count();

            $messages = $messages->offset($offset)->limit($limit)->get()->toArray();

            foreach ($messages as &$message) {
                if (isset($message['file'])) {
                    $message['files'] = collect($message['file'])->map(function ($file) {
                        return asset('storage/'.$file['file_name']);
                    })->toArray();

                    unset($message['file']);
                } else {
                    $message['files'] = []; // or handle the case where 'file' is not set
                }
            }
            ResponseService::successResponse('Data Fetched Successfully', [
                'items' => $messages ?? [],
                'total_items' => $total_items,
            ], [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function readAllMessages(Request $request)
    {
        try {
            $user = Auth::id();
            $teacher = $request->user_id;

            $lastMessage = ChatMessage::where('sender_id', $teacher)->where('modal_id', $user)->latest('id')->first();
            if ($lastMessage) {
                $message_id = $lastMessage->id;
            }

            // Update Read Message id
            $readMessage = ReadMessage::where('modal_id', $user)->where('user_id', $teacher)->first();

            if ($readMessage) {
                $readMessage->last_read_message_id = $message_id;
                $readMessage->save();
            }
            ResponseService::successResponse('Message Read');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * The teachers a parent may chat with: the class teachers and subject teachers
     * of their children's current class sections (semester aware). Also returns the
     * intermediate data getChatUserList shows alongside each teacher.
     *
     * @return array{teacher_ids: list<int>, childrens: \Illuminate\Support\Collection, semester_class_section_ids: array<int, int>, non_semester_class_section_ids: list<int>}
     */
    private function chatTeacherScope(Parents $parent, $session_year_id, ?Semester $currentSemester): array
    {
        $childrens = $parent->children()
            ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
            })->get();

        $class_section_ids = [];
        $subject_id = [];

        // Track which class_section_ids require semester filtering and which don't
        $semesterClassSectionIds = []; // class_section_id => semester_id
        $nonSemesterClassSectionIds = []; // plain array

        foreach ($childrens as $children) {
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $children->id)->first();

            $sectionId = $studentSession
                ? $studentSession->class_section_id
                : $children->class_section_id;

            $class_section_ids[] = $sectionId;

            // Resolve class_id from ClassSection
            $classSection = ClassSection::find($sectionId);
            $class_id = $classSection ? $classSection->class_id : null;

            $isClassHaveSemester = $class_id
                ? ClassSessionConfig::classHasSemester($class_id, $session_year_id)
                : false;

            if ($isClassHaveSemester && $currentSemester) {
                $semesterClassSectionIds[$sectionId] = $currentSemester->id;
            } else {
                $nonSemesterClassSectionIds[] = $sectionId;
            }

            $student_subject = $children->subjects();

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }
            $subject_id = array_merge($subject_id, $core_subjects, $elective_subjects);
        }

        $subject_id = array_unique($subject_id);

        // --- Class teacher IDs with semester awareness ---
        $class_teachers_id = [];
        foreach ($semesterClassSectionIds as $csi => $semId) {
            $ids = ClassTeacher::where('class_section_id', $csi)
                ->where('session_year_id', $session_year_id)
                ->where('semester_id', $semId)
                ->pluck('class_teacher_id')->toArray();
            $class_teachers_id = array_merge($class_teachers_id, $ids);
        }
        if (! empty($nonSemesterClassSectionIds)) {
            $ids = ClassTeacher::whereIn('class_section_id', $nonSemesterClassSectionIds)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_teacher_id')->toArray();
            $class_teachers_id = array_merge($class_teachers_id, $ids);
        }

        // --- Subject teacher IDs with semester awareness ---
        $subject_teacher_id = [];
        foreach ($semesterClassSectionIds as $csi => $semId) {
            $ids = SubjectTeacher::where('class_section_id', $csi)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)
                ->where('semester_id', $semId)
                ->pluck('teacher_id')->toArray();
            $subject_teacher_id = array_merge($subject_teacher_id, $ids);
        }
        if (! empty($nonSemesterClassSectionIds)) {
            $ids = SubjectTeacher::whereIn('class_section_id', $nonSemesterClassSectionIds)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)
                ->pluck('teacher_id')->toArray();
            $subject_teacher_id = array_merge($subject_teacher_id, $ids);
        }

        return [
            'teacher_ids' => array_values(array_unique(array_merge($class_teachers_id, $subject_teacher_id))),
            'childrens' => $childrens,
            'semester_class_section_ids' => $semesterClassSectionIds,
            'non_semester_class_section_ids' => $nonSemesterClassSectionIds,
        ];
    }

    /**
     * User ids of the teachers the logged-in parent may chat with (the users
     * getChatUserList lists).
     *
     * @return list<int>
     */
    private function chatAllowedUserIds(Request $request): array
    {
        $parent = $request->user()?->parent;
        if (! $parent) {
            return [];
        }

        $session_year_id = getSettings('session_year')['session_year'];
        $currentSemester = Semester::where('session_year_id', $session_year_id)->get()->first(fn ($semester) => $semester->current);
        $teacherIds = $this->chatTeacherScope($parent, $session_year_id, $currentSemester)['teacher_ids'];

        return Teacher::whereIn('id', $teacherIds)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }
}
