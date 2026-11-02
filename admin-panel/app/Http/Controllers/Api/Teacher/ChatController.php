<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\ChatFile;
use App\Models\ChatMessage;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\File;
use App\Models\Parents;
use App\Models\ReadMessage;
use App\Models\Students;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Services\Platform\Files\FileNames;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Teacher app: chat with students and parents.
 */
class ChatController extends Controller
{
    use TeacherAccess;

    /**
     * Page size when a request sends no limit; it used to load everything. The
     * apps send 15, and the student chat's messages default to 100 as well.
     */
    private const DEFAULT_PAGE_SIZE = 100;

    public function getChatUserList(Request $request)
    {
        try {

            $offset = max((int) $request->offset, 0);
            $limit = max((int) ($request->limit ?? self::DEFAULT_PAGE_SIZE), 1);
            $user_type = $request->isParent;
            $search = $request->search;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $teacher = $request->user()->teacher;

            $class_section_ids = ClassTeacher::with('class_section')
                ->where('class_teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id')->toArray();

            $subject_teachers = SubjectTeacher::with('class_section')
                ->where('teacher_id', $teacher->id)
                ->where('session_year_id', $session_year_id)
                ->whereNotIn('class_section_id', $class_section_ids)
                ->groupBy('class_section_id')->get();

            $data = [];
            $parents_ids = [];

            if ($class_section_ids) {
                $students = Students::with(['user', 'class_section.class', 'student_subjects.subject'])
                    // This year's placement (a leaver keeps students.class_section_id but has none; Action Plan 1.21).
                    ->whereHas('studentSessions', function ($q) use ($session_year_id, $class_section_ids) {
                        $q->where('session_year_id', $session_year_id)->where('status', 1)->whereIn('class_section_id', $class_section_ids);
                    })->get();

                foreach ($students as $student) {
                    $parents_ids[] = $student->father_id;
                    $parents_ids[] = $student->mother_id;
                    $parents_ids[] = $student->guardian_id;
                }

                $parents_ids = array_filter(array_unique($parents_ids));

                if ($user_type == 0) {
                    foreach ($students as $student) {
                        $unreadCount = 0;
                        if ($student->user_id != 0) {
                            $lastMessage = ChatMessage::with('file')->where(function ($query) use ($student, $teacher) {
                                $query->where('modal_id', $student->user_id)
                                    ->where('sender_id', $teacher->user->id);
                            })
                                ->orWhere(function ($query) use ($student, $teacher) {
                                    $query->where('modal_id', $teacher->user->id)
                                        ->where('sender_id', $student->user_id);
                                })
                                ->select('id', 'body', 'date')
                                ->latest('id')
                                ->first();

                            $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $student->user_id)->first();

                            if ($lastReadMessage) {

                                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                if (! empty($lastReadMessageId)) {
                                    $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                } else {
                                    $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->count();
                                }
                            }

                            $student_subject = $student->subjects();

                            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

                            $elective_subjects = $student_subject['elective_subject'] ?? [];
                            if ($elective_subjects) {
                                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                            }
                            $subject_id = array_merge($core_subjects, $elective_subjects);

                            $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                            $data[] = [
                                'id' => $student->id,
                                'user_id' => $student->user_id, // Assuming this is the correct property name
                                'first_name' => $student->user->first_name ?? '',
                                'last_name' => $student->user->last_name ?? '',
                                'image' => $student->user->image ?? '',
                                'roll_no' => $student->roll_number,
                                'admission_no' => $student->admission_no,
                                'gender' => $student->user->gender,
                                'dob' => $student->user->dob,
                                'subjects' => $subjects,
                                'address' => $student->user->current_address,
                                'last_message' => $lastMessage ?? null,
                                'class_name' => $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name,
                                'isParent' => $user_type,
                                'unread_message' => $unreadCount ?? 0,
                            ];
                        }
                    }
                }
                if ($user_type == 1) {

                    $parents = Parents::with('user')->whereIn('id', $parents_ids)->get();
                    foreach ($parents as $parent) {
                        $unreadCount = 0;
                        $childArray = [];
                        if ($parent->user_id != 0) {
                            $children = $parent->children()->with('user', 'class_section')
                                ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                                    $q->where('session_year_id', $session_year_id)->where('status', 1);
                                })->get();

                            foreach ($children as $child) {
                                $child_subject = $child->subjects();

                                $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

                                $elective_subjects = $child_subject['elective_subject'] ?? [];

                                if ($elective_subjects) {
                                    $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                                }

                                $subject_id = array_merge($core_subjects, $elective_subjects);

                                $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                                $childArray[] = [
                                    'id' => $child->id,
                                    'user_id' => $child->user_id,
                                    'child_name' => $child->user->first_name.' '.$child->user->last_name,
                                    'class_name' => $child->class_section->class->name.' '.$child->class_section->section->name.' '.$child->class_section->class->medium->name,
                                    'admission_no' => $child->admission_no,
                                    'image' => $child->user->image,
                                    'subject' => $subjects ?? [],
                                ];
                            }

                            $lastMessage = ChatMessage::with('file')->where(function ($query) use ($parent, $teacher) {
                                $query->where('modal_id', $parent->user_id)
                                    ->where('sender_id', $teacher->user->id);
                            })
                                ->orWhere(function ($query) use ($parent, $teacher) {
                                    $query->where('modal_id', $teacher->user->id)
                                        ->where('sender_id', $parent->user_id);
                                })
                                ->select('id', 'body', 'date')
                                ->latest('id')
                                ->first();

                            $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $parent->user_id)->first();

                            if ($lastReadMessage) {

                                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                if (! empty($lastReadMessageId)) {
                                    $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                } else {
                                    $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->count();
                                }
                            }
                            $data[] = [
                                'id' => $parent->id,
                                'user_id' => $parent->user_id, // Assuming this is the correct property name
                                'first_name' => $parent->user->first_name ?? '',
                                'last_name' => $parent->user->last_name ?? '',
                                'email' => $parent->user->email ?? '',
                                'mobile_no' => $parent->user->mobile ?? '',
                                'occupation' => $parent->occupation ?? '',
                                'image' => $parent->user->image ?? '',
                                'last_message' => $lastMessage ?? null,
                                'children' => $childArray ?? [],
                                'isParent' => $user_type,
                                'unread_message' => $unreadCount ?? 0,
                            ];
                        }
                    }
                }
            }

            if ($subject_teachers) {

                foreach ($subject_teachers as $subject_teacher) {
                    $class_subject = ClassSubject::where('subject_id', $subject_teacher->subject_id)->where('class_id', $subject_teacher->class_section->class->id)->first();
                    $students = Students::with(['user', 'class_section.class', 'student_subjects.subject'])
                        ->whereHas('studentSessions', function ($q) use ($session_year_id, $subject_teacher) {
                            $q->where('session_year_id', $session_year_id)->where('status', 1)->where('class_section_id', $subject_teacher->class_section_id);
                        })->get();
                    $parents_id = [];

                    // Every father, mother and guardian (groupBy()->keys() only gave the fathers).
                    $parents_id = $students->flatMap(fn ($student) => [$student->father_id, $student->mother_id, $student->guardian_id])
                        ->filter()->unique()->values()->all();

                    $common_parents_ids = array_intersect($parents_ids, $parents_id);

                    $unique_parents_ids = array_diff($parents_id, $common_parents_ids);

                    if ($user_type == 0) {
                        foreach ($students as $student) {
                            $unreadCount = 0;
                            if ($student->user_id != 0) {
                                $lastMessage = ChatMessage::with('file')->where(function ($query) use ($student, $teacher) {
                                    $query->where('modal_id', $student->user_id)
                                        ->where('sender_id', $teacher->user->id);
                                })
                                    ->orWhere(function ($query) use ($student, $teacher) {
                                        $query->where('modal_id', $teacher->user->id)
                                            ->where('sender_id', $student->user_id);
                                    })
                                    ->select('id', 'body', 'date')
                                    ->latest('id')
                                    ->first();

                                $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $student->user_id)->first();

                                if ($lastReadMessage) {

                                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                    if (! empty($lastReadMessageId)) {
                                        $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                    } else {
                                        $unreadCount = ChatMessage::where('sender_id', $student->user_id)->where('modal_id', $teacher->user->id)->count();
                                    }
                                }

                                $student_subject = $student->subjects();

                                $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

                                $elective_subjects = $student_subject['elective_subject'] ?? [];
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
                                if ($class_subject->type == 'Elective') {
                                    // dd($student_subject['elective_subject']->pluck('subject_id'));

                                    $student_subject = $student->student_subjects->where('subject_id', $class_subject->subject_id);

                                    if (! empty($student_subject->toArray())) {
                                        $data[] = [
                                            'id' => $student->id,
                                            'user_id' => $student->user_id, // Assuming this is the correct property name
                                            'first_name' => $student->user->first_name ?? '',
                                            'last_name' => $student->user->last_name ?? '',
                                            'image' => $student->user->image ?? '',
                                            'roll_no' => $student->roll_number,
                                            'admission_no' => $student->admission_no,
                                            'gender' => $student->user->gender,
                                            'dob' => $student->user->dob,
                                            'subjects' => $subjectArray,
                                            'address' => $student->user->current_address,
                                            'last_message' => $lastMessage ?? null,
                                            'class_name' => $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name,
                                            'isParent' => $user_type,
                                            'unread_message' => $unreadCount ?? 0,
                                        ];
                                    }
                                } else {

                                    $data[] = [
                                        'id' => $student->id,
                                        'user_id' => $student->user_id, // Assuming this is the correct property name
                                        'first_name' => $student->user->first_name ?? '',
                                        'last_name' => $student->user->last_name ?? '',
                                        'image' => $student->user->image ?? '',
                                        'roll_no' => $student->roll_number,
                                        'admission_no' => $student->admission_no,
                                        'gender' => $student->user->gender,
                                        'dob' => $student->user->dob,
                                        'subjects' => $subjects,
                                        'address' => $student->user->current_address,
                                        'last_message' => $lastMessage ?? null,
                                        'class_name' => $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name,
                                        'isParent' => $user_type,
                                        'unread_message' => $unreadCount ?? 0,
                                    ];
                                }
                            }
                        }
                    }

                    if ($user_type == 1) {
                        $parents = Parents::with('user')->whereIn('id', $unique_parents_ids)->get();
                        foreach ($parents as $parent) {
                            $unreadCount = 0;
                            $childArray = [];
                            if ($parent->user_id != 0) {
                                $lastMessage = ChatMessage::with('file')->where(function ($query) use ($parent, $teacher) {
                                    $query->where('modal_id', $parent->user_id)
                                        ->where('sender_id', $teacher->user->id);
                                })
                                    ->orWhere(function ($query) use ($parent, $teacher) {
                                        $query->where('modal_id', $teacher->user->id)
                                            ->where('sender_id', $parent->user_id);
                                    })
                                    ->select('id', 'body', 'date')
                                    ->latest('id')
                                    ->first();

                                $lastReadMessage = ReadMessage::where('modal_id', $teacher->user->id)->where('user_id', $parent->user_id)->first();

                                if ($lastReadMessage) {

                                    $lastReadMessageId = $lastReadMessage->last_read_message_id;
                                    if (! empty($lastReadMessageId)) {
                                        $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->where('id', '>', $lastReadMessageId)->count();
                                    } else {
                                        $unreadCount = ChatMessage::where('sender_id', $parent->user_id)->where('modal_id', $teacher->user->id)->count();
                                    }
                                }
                                $children = $parent->children()->with('user', 'class_section')
                                    ->whereHas('studentSessions', function ($q) use ($session_year_id) {
                                        $q->where('session_year_id', $session_year_id)->where('status', 1);
                                    })->get();

                                foreach ($children as $child) {
                                    $child_subject = $child->subjects();

                                    $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

                                    $elective_subjects = $child_subject['elective_subject'] ?? [];

                                    if ($elective_subjects) {
                                        $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
                                    }

                                    $subject_id = array_merge($core_subjects, $elective_subjects);

                                    $subjects = Subject::whereIn('id', $subject_id)->select('id', 'name')->get();

                                    $childArray[] = [
                                        'id' => $child->id,
                                        'user_id' => $child->user_id,
                                        'child_name' => $child->user->first_name.' '.$child->user->last_name,
                                        'class_name' => $child->class_section->class->name.' '.$child->class_section->section->name.' '.$child->class_section->class->medium->name,
                                        'admission_no' => $child->admission_no,
                                        'image' => $child->user->image,
                                        'subject' => $subjects ?? [],
                                    ];
                                }

                                if ($class_subject->type == 'Elective') {
                                    $student_subject = $child->student_subjects->where('subject_id', $class_subject->subject_id);

                                    if (! empty($student_subject->toArray())) {
                                        $data[] = [
                                            'id' => $parent->id,
                                            'user_id' => $parent->user_id, // Assuming this is the correct property name
                                            'first_name' => $parent->user->first_name ?? '',
                                            'last_name' => $parent->user->last_name ?? '',
                                            'email' => $parent->user->email ?? '',
                                            'mobile_no' => $parent->user->mobile ?? '',
                                            'occupation' => $parent->occupation ?? '',
                                            'image' => $parent->user->image ?? '',
                                            'last_message' => $lastMessage ?? null,
                                            'children' => $childArray ?? [],
                                            'isParent' => $user_type,
                                            'unread_message' => $unreadCount ?? 0,
                                        ];
                                    }
                                } else {
                                    $data[] = [
                                        'id' => $parent->id,
                                        'user_id' => $parent->user_id, // Assuming this is the correct property name
                                        'first_name' => $parent->user->first_name ?? '',
                                        'last_name' => $parent->user->last_name ?? '',
                                        'email' => $parent->user->email ?? '',
                                        'mobile_no' => $parent->user->mobile ?? '',
                                        'occupation' => $parent->occupation ?? '',
                                        'image' => $parent->user->image ?? '',
                                        'last_message' => $lastMessage ?? null,
                                        'children' => $childArray ?? [],
                                        'isParent' => $user_type,
                                        'unread_message' => $unreadCount ?? 0,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            $total_items = count($data) ?? 0;

            $unreadusers = array_filter($data, function ($user) {
                return $user['unread_message'] > 0;
            });

            $totalunreadusers = count($unreadusers);

            if ($search) {
                $filteredData = array_filter($data, function ($teacher) use ($search) {
                    $name = $teacher['first_name'].' '.$teacher['last_name'];

                    return stristr($name, $search) !== false;
                });
                $data = collect($filteredData)->sortByDesc(function ($user) {
                    return optional($user['last_message'])->id ?? 0;
                })->splice($offset, $limit)->values();
            } else {
                $data = collect($data)->sortByDesc(function ($user) {
                    return optional($user['last_message'])->id ?? 0;
                })
                    ->splice($offset, $limit)
                    ->values();
            }

            return ResponseService::successResponse('Data Fetched Successfully', ['items' => $data, 'total_items' => $total_items, 'total_unread_users' => $totalunreadusers], [], 100);
        } catch (\Exception $e) {
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
            'receiver_id' => 'required|numeric',
            'message' => 'required_without:file|nullable|string|max:'.$maxChars,
            'file' => 'nullable|array|max:'.$maxFiles,
            'file.*' => 'file|max:'.$maxFileKb,
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sender_id = $request->user()->id;
            $receiver_id = $request->receiver_id;

            // Refuse a user without a teacher record before anything is saved (the
            // notification below needs the teacher), and anyone they don't teach.
            $this->currentTeacher();
            if (! $this->canChatWith($receiver_id)) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
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

                    // The app shows the last part of the stored path as the file's name, so keep
                    // the (cleaned) original name, but inside a directory of its own so one
                    // upload can never overwrite another.
                    $originalName = trim((string) preg_replace('/[^\w.\- ]+/u', '_', FileNames::forUpload($uploadedFile)), '. ');
                    $filePath = $uploadedFile->storeAs(
                        'chatfile/'.Str::random(40),
                        $originalName !== '' ? $originalName : $uploadedFile->hashName(),
                        'public'
                    );

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

            $teacher = Teacher::with('user', 'subjects.subject')->where('user_id', $sender_id)->first();

            $subjectData = [];

            if ($teacher) {
                foreach ($teacher->subjects as $subject) {
                    $subjectData[] = [
                        'id' => $subject->subject->id,
                        'name' => $subject->subject->name,
                    ];
                }
            }

            $lastReadMessage = ReadMessage::where('modal_id', $receiver_id)->where('user_id', $teacher->user_id)->first();

            if ($lastReadMessage) {

                $lastReadMessageId = $lastReadMessage->last_read_message_id;
                if (! empty($lastReadMessageId)) {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $teacher->user_id)->where('id', '>', $lastReadMessageId)->count();
                } else {
                    $unreadCount = ChatMessage::where('modal_id', $receiver_id)->where('sender_id', $teacher->user_id)->count();
                }
            }

            $userinfo = [
                'id' => $teacher->id,
                'user_id' => $teacher->user->id,
                'first_name' => $teacher->user->first_name,
                'last_name' => $teacher->user->last_name,
                'email' => $teacher->user->email,
                'qualification' => $teacher->qualification,
                'image' => $teacher->user->image,
                'mobile_no' => $teacher->user->mobile,
                'subjects' => $subjectData,
                'last_message' => $data ?? null,
                'unread_message' => $unreadCount ?? 0,
            ];

            $title = $teacher->user->first_name.' '.$teacher->user->last_name;
            $body = $request->message ?? $count.' Files Received';
            $type = 'chat';
            $image = null;
            $user[] = $receiver_id;

            $userinfo = (object) $userinfo;
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            return ResponseService::successResponse('message_sent_successfully', $data);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getUserChatMessage(Request $request)
    {
        try {

            $offset = max((int) $request->offset, 0);
            $limit = max((int) ($request->limit ?? self::DEFAULT_PAGE_SIZE), 1);

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
                $message['files'] = collect($message['file'])->map(function ($file) {
                    return asset('storage/'.$file['file_name']);
                })->toArray();

                unset($message['file']);
            }

            return ResponseService::successResponse('Data Fetched Successfully', ['items' => $messages ?? [], 'total_items' => $total_items], [], 100);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function readAllMessages(Request $request)
    {
        try {
            $auth = Auth::id();
            $user = $request->user_id;

            $lastMessage = ChatMessage::where('sender_id', $user)->where('modal_id', $auth)->latest('id')->first();
            if ($lastMessage) {
                $message_id = $lastMessage->id;
            }

            // Update Read Message id
            $readMessage = ReadMessage::where('modal_id', $auth)->where('user_id', $user)->first();

            if ($readMessage) {
                $readMessage->last_read_message_id = $message_id;
                $readMessage->save();
            }

            return ResponseService::successResponse('Message Read');
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Users the teacher may message: students of the class sections they teach this
     * session year and those students' parents/guardians (the people getChatUserList
     * lists), plus anyone who has already written to them.
     */
    private function canChatWith($userId): bool
    {
        if (ChatMessage::where('sender_id', $userId)->where('modal_id', Auth::id())->exists()) {
            return true;
        }

        $sessionYearId = $this->currentSessionYearId();
        // This year's placement, not the learner's last homeroom: a leaver keeps
        // students.class_section_id but has no homeroom this year (Action Plan 1.21).
        $students = Students::whereHas('studentSessions', fn ($q) => $q->where('session_year_id', $sessionYearId)
            ->where('status', 1)->whereIn('class_section_id', $this->teacherClassSectionIds()));

        if ((clone $students)->where('user_id', $userId)->exists()) {
            return true;
        }

        $parentIds = Parents::where('user_id', $userId)->pluck('id');

        return $parentIds->isNotEmpty() && (clone $students)
            ->where(fn ($q) => $q->whereIn('father_id', $parentIds)
                ->orWhereIn('mother_id', $parentIds)
                ->orWhereIn('guardian_id', $parentIds))
            ->exists();
    }
}
