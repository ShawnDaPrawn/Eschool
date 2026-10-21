<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ChatFile;
use App\Models\ChatMessage;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\File;
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
 * Student app: chat with the student's teachers.
 */
class ChatController extends Controller
{
    use StudentAccess;

    public function getChatUserList(Request $request)
    {
        try {
            $offset = max((int) $request->input('offset', 0), 0);
            $limit = $request->input('limit');
            $limit = is_numeric($limit) ? (int) $limit : null;
            $limit = ($limit !== null && $limit > 0) ? $limit : null;
            $search = $request->search;

            $user = Auth::user();

            $session_year_id = getSettings('session_year')['session_year'];

            // Resolve StudentSession with class_section relationship eager loaded
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $user->student->id)
                ->with('class_section')
                ->first();

            // Guard: student not enrolled in current session year
            if (! $studentSession || ! $studentSession->class_section) {
                return ResponseService::errorResponse('Student session not found for the current year.');
            }

            $teacher_ids = $this->chatTeacherIds($studentSession, $user->student, $session_year_id);

            // return response()->json([$subject_teacher_id, $teacher_ids]);

            $teachers = Teacher::whereIn('id', $teacher_ids)
                ->with('user:id,first_name,last_name,image,mobile,email', 'subjects.subject');

            if ($search) {
                $teachers->where(function ($query) use ($search) {
                    $query->whereHas('user', function ($subquery) use ($search) {
                        $subquery->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%");
                    });
                });
            }

            if ($limit !== null) {
                $teachers->offset($offset)->limit($limit);
            }

            $teachers = $teachers->get();

            $data = [];

            foreach ($teachers as $teacher) {

                $unreadCount = 0;
                $subjectData = [];

                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = [
                        'id' => $subject->subject->id ?? '',
                        'name' => $subject->subject->name ?? '',
                    ];
                }

                $lastMessage = ChatMessage::with('file')
                    ->where(function ($query) use ($user, $teacher) {
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
                    ->where('user_id', $teacher->user->id)
                    ->first();

                if ($lastReadMessage) {
                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                    if (! empty($lastReadMessageId)) {
                        $unreadCount = ChatMessage::where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id)
                            ->where('id', '>', $lastReadMessageId)
                            ->count();
                    } else {
                        $unreadCount = ChatMessage::where('sender_id', $teacher->user->id)
                            ->where('modal_id', $user->id)
                            ->count();
                    }
                }

                $data[] = [
                    'id' => $teacher->id,
                    'user_id' => $teacher->user->id,
                    'first_name' => $teacher->user->first_name,
                    'last_name' => $teacher->user->last_name,
                    'email' => $teacher->user->email,
                    'qualification' => $teacher->qualification,
                    'image' => $teacher->user->image,
                    'mobile_no' => $teacher->user->mobile,
                    'subjects' => $subjectData,
                    'last_message' => $lastMessage ?? null,
                    'unread_message' => $unreadCount ?? 0,
                ];
            }

            $total_items = count($data);

            $unreadusers = array_filter($data, function ($teacher) {
                return $teacher['unread_message'] > 0;
            });

            $totalunreadusers = count($unreadusers);

            $data = collect($data)->sortByDesc(function ($item) {
                return optional($item['last_message'])->id ?? 0;
            })->values();

            return ResponseService::successResponse('Data Fetched Successfully', [
                'items' => $data,
                'total_items' => $total_items,
                'total_unread_users' => $totalunreadusers,
            ]);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function sendMessage(Request $request)
    {
        // Limits from the chat settings (the same values the app is given).
        $chatSettings = getSettings();
        $maxFiles = max(1, (int) ($chatSettings['max_files_or_images_in_one_message'] ?? 10));
        $maxFileKb = max(1, (int) ceil(((float) ($chatSettings['max_file_size_in_bytes'] ?? 10)) * 1000000 / 1024));
        $maxChars = max(1, (int) ($chatSettings['max_characters_in_text_message'] ?? 500));

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer',
            'message' => 'required_without:file|nullable|string|max:'.$maxChars,
            'file' => 'nullable|array|max:'.$maxFiles,
            'file.*' => 'file|max:'.$maxFileKb,
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sender_id = $request->user()->id;
            $receiver_id = (int) $request->receiver_id;

            // Only the teachers getChatUserList offers this student.
            $session_year_id = getSettings('session_year')['session_year'];
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $request->user()->student->id)
                ->with('class_section')
                ->first();
            $allowed_user_ids = ($studentSession && $studentSession->class_section)
                ? Teacher::whereIn('id', $this->chatTeacherIds($studentSession, $request->user()->student, $session_year_id))->pluck('user_id')->map(fn ($id) => (int) $id)->all()
                : [];
            if (! in_array($receiver_id, $allowed_user_ids, true)) {
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
            $unreadCount = 0;

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
                        $chatfile[] = asset('storage/' . $file->file_name);
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

            $student = Students::with('user')->where('user_id', $sender_id)->first();

            $lastReadMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $student->user_id)->first();

            if ($lastReadMessage) {

                $lastReadMessageId = $lastReadMessage->last_read_message_id;

                if (! empty($lastReadMessageId) || ($lastReadMessageId != null)) {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $student->user_id)->where('id', '>', $lastReadMessageId)->count();
                } else {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $student->user_id)->count();
                }
            }

            $student_subject = $student->subjects();

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }
            $subject_id = array_merge($core_subjects, $elective_subjects);

            $subjects = Subject::whereIn('id', $subject_id)->get();
            $subjectArray = [];
            foreach ($subjects as $subject) {
                $subjectArray[] = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ];
            }

            $userinfo = [
                'id' => $student->id,
                'user_id' => $student->user_id, // Assuming this is the correct property name
                'first_name' => $student->user->first_name,
                'last_name' => $student->user->last_name,
                'image' => $student->user->image,
                'roll_no' => $student->roll_number,
                'admission_no' => $student->admission_no,
                'gender' => $student->user->gender,
                'dob' => $student->user->dob,
                'subjects' => $subjectArray,
                'address' => $student->user->current_address,
                'last_message' => $data ?? null,
                'class_name' => $student->class_section->class->name . ' ' . $student->class_section->section->name . ' ' . $student->class_section->class->medium->name,
                'isParent' => 0,
                'unread_message' => $unreadCount ?? 0,
            ];

            $title = $student->user->first_name . ' ' . $student->user->last_name;
            $body = $request->message ?? $count . ' Files Received';
            $type = 'chat';
            $image = null;
            $user[] = $receiver_id;
            $userinfo = (object) $userinfo;

            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            ResponseService::successResponse('message_sent_successfully', $data);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getUserChatMessage(Request $request)
    {
        try {
            $offset = max((int) $request->offset, 0);
            // At most 100 messages per page.
            $limit = min(max((int) ($request->limit ?? 100), 1), 100);

            $messages = ChatMessage::with(['file' => function ($query) {
                $query->select('message_id', 'file_name');
            }])
                ->where(function ($query) use ($request) {
                    $query->where('modal_id', $request->user_id)
                        ->orWhere('modal_id', Auth::id());
                })
                ->where(function ($query) use ($request) {
                    $query->where('sender_id', $request->user_id)
                        ->orWhere('sender_id', Auth::id());
                })
                ->select('id', 'sender_id', 'body', 'date')
                ->latest('id');

            $total_items = $messages->count();

            $messages = $messages->offset($offset)->limit($limit)->get()->toArray();

            foreach ($messages as &$message) {
                if (isset($message['file'])) {
                    $message['files'] = collect($message['file'])->map(function ($file) {
                        return asset('storage/' . $file['file_name']);
                    })->toArray();

                    unset($message['file']);
                } else {
                    $message['files'] = []; // or handle the case where 'file' is not set
                }
            }

            ResponseService::successResponse('Data Fetched Successfully', ['items' => $messages ?? [], 'total_items' => $total_items]);
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
     * The teachers a student may chat with: the class teachers and the
     * teachers of their subjects in their current class section (semester aware).
     *
     * @return list<int> teacher ids
     */
    private function chatTeacherIds(StudentSessions $studentSession, Students $student, $session_year_id): array
    {
        $class_section_id = $studentSession->class_section_id;
        $subject_id = $this->studentSubjectIds($student);

        $currentSemester = Semester::get()->first(function ($semester) {
            return $semester->current;
        });

        $isClassHaveSemester = ClassSessionConfig::classHasSemester(
            $studentSession->class_section->class_id,
            $session_year_id
        );

        $class_teachers_id = ClassTeacher::where('class_section_id', $class_section_id)
            ->where('session_year_id', $session_year_id)
            ->when($isClassHaveSemester && $currentSemester, function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester->id);
            })
            ->pluck('class_teacher_id')
            ->toArray();

        $subject_teacher_id = SubjectTeacher::where('class_section_id', $class_section_id)
            ->where('session_year_id', $session_year_id)
            ->when($isClassHaveSemester && $currentSemester, function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester->id);
            })
            ->whereIn('subject_id', $subject_id)
            ->pluck('teacher_id')
            ->toArray();

        return array_values(array_unique(array_merge($class_teachers_id, $subject_teacher_id)));
    }
}
