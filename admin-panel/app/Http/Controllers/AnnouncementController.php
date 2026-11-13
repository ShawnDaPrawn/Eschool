<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\File;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Term;
use App\Services\Platform\Files\FileNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        if (! Auth::user()->can('announcement-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::SubjectTeacher()->with('class.medium', 'section')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        $editingNotice = null;
        if ($request->filled('notice_id')) {
            $request->validate(['notice_id' => 'required|integer|min:1']);
            abort_unless($request->user()->can('announcement-edit'), 403);
            $editingNotice = Announcement::with(['table', 'file'])->findOrFail($request->integer('notice_id'));
            abort_unless($this->canManageAnnouncement($editingNotice), 403);
        }

        return view('announcement.index', compact('class_section', 'session_years', 'editingNotice'));
    }

    public function getClassSectionsForAnnouncement(Request $request)
    {
        $session_year_id = $request->session_year_id ?: getSettings('session_year')['session_year'];
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') && $user->teacher;

        $class_sections = ClassSection::SubjectTeacher()->with('class.medium', 'section', 'class.streams')
            ->whereHas('subject_teachers', function ($q) use ($session_year_id, $user, $isTeacher) {
                $q->where('session_year_id', $session_year_id);
                if ($isTeacher) {
                    $q->where('teacher_id', $user->teacher->id);
                }
            })->get();

        $result = [];
        foreach ($class_sections as $section) {
            $class = ClassSchool::withSessionConfigFor($session_year_id)
                ->where('id', $section->class->id)
                ->first();

            $hasTerms = $class && $class->getIncludeTermsForSession($session_year_id);

            $baseName = $section->class->name.' '.$section->section->name.' - '.$section->class->medium->name.' '.($section->class->streams->name ?? '');

            if ($hasTerms) {
                if ($isTeacher) {
                    $terms = Term::where('session_year_id', $session_year_id)
                        ->whereIn('id', function ($q) use ($section, $session_year_id, $user) {
                            $q->select('term_id')
                                ->from('subject_teachers')
                                ->where('class_section_id', $section->id)
                                ->where('session_year_id', $session_year_id)
                                ->where('teacher_id', $user->teacher->id)
                                ->whereNotNull('term_id')
                                ->whereNull('deleted_at');
                        })
                        ->orderBy('id', 'ASC')
                        ->get();
                } else {
                    $terms = Term::where('session_year_id', $session_year_id)
                        ->orderBy('id', 'ASC')
                        ->get();
                }

                foreach ($terms as $term) {
                    $result[] = [
                        'id' => $section->id,
                        'name' => $baseName.' - '.$term->name,
                        'class_id' => $section->class->id,
                        'term_id' => $term->id,
                    ];
                }
            } else {
                $result[] = [
                    'id' => $section->id,
                    'name' => $baseName,
                    'class_id' => $section->class->id,
                    'term_id' => null,
                ];
            }
        }

        return response()->json($result);
    }

    public function getAssignData(Request $request)
    {
        $data = $request->data;
        $class_id = $request->class_id;
        $class_section_id = $request->class_section_id;
        $term_id = $request->term_id;
        $session_year_id = $request->session_year_id ?: getSettings('session_year')['session_year'];

        if ($data == 'class_section' && $class_id != '') {
            $user = Auth::user();

            if ($user->hasRole('Teacher') && $user->teacher) {
                // Only return subjects assigned to this teacher
                $query = SubjectTeacher::where('class_section_id', $class_section_id)
                    ->where('teacher_id', $user->teacher->id)
                    ->where('session_year_id', $session_year_id);

                if ($term_id) {
                    $query->where('term_id', $term_id);
                }

                $info = $query->with('subject')->get();
            } else {
                $query = ClassSubject::where('class_id', $class_id)
                    ->where('session_year_id', $session_year_id);

                if ($term_id) {
                    $query->where('term_id', $term_id);
                }

                $info = $query->with('subject')->get();
            }
        } elseif ($data == 'class') {
            $info = ClassSchool::get();
        } else {
            $info = '';
        }

        return response()->json($info);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('announcement-create')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $this->validateAudience($request, false);
        if ($request->input('set_data') === 'class') {
            $request->validate(['get_data' => 'required|array|min:1|max:100', 'get_data.*' => 'required|integer|min:1|distinct']);
            abort_unless(ClassSchool::whereIn('id', $request->input('get_data'))->count() === count($request->input('get_data')), 404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'set_data' => 'required|in:class,class_section,noticeboard',
            'file' => 'nullable|array',
            'file.*' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:4096',
        ], [
            'title.required' => trans('title_is_required'),
            'set_data.required' => trans('assign_to_field_is_required'),
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        DB::beginTransaction();
        try {
            $user = [];
            // $data = getSettings('session_year');
            if (! empty($request->get_data)) {
                $getdata = count($request->get_data);
            } else {
                $getdata = 1;
            }
            for ($i = 0; $i < $getdata; $i++) {
                $announcement = new Announcement;
                $announcement->title = $request->title;
                $announcement->description = $request->description;
                $announcement->session_year_id = getSettings('session_year')['session_year'];
                $announcement->term_id = $request->term_id ?: null;
                if (! empty($request->set_data)) {
                    if ($request->set_data == 'class_section') {
                        $teacher_id = Auth::user()->teacher->id;
                        $allocation = $this->allocationQuery($request)->where('subject_id', $request->get_data[$i]);
                        $subject_teacher_id = (clone $allocation)->pluck('id');
                        $subject_name = $allocation->with('subject')->get();
                        if (count($subject_name)) {
                            if (count($subject_teacher_id) != 0) {
                                for ($j = 0; $j < count($subject_teacher_id); $j++) {
                                    $subject_teacher = SubjectTeacher::find($subject_teacher_id[$j]);
                                    $announcement->table()->associate($subject_teacher);
                                    $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');
                                }
                            }
                            $title = 'New announcement in '.$subject_name[0]->subject->name;
                            $body = $request->title;
                        } else {
                            DB::rollBack();
                            $response = [
                                'error' => true,
                                'message' => trans('no_data_found'),
                            ];

                            return response()->json($response);
                        }
                    }
                    if ($request->set_data == 'class') {
                        $class = ClassSchool::findOrFail($request->get_data[$i]);
                        abort_if(Auth::user()->hasRole('Teacher'), 403);
                        $announcement->table()->associate($class);
                        $get_class = ClassSection::select('id')->where('class_id', $request->get_data[$i])->get()->pluck('id');
                        $user = Students::select('user_id')->whereIn('class_section_id', $get_class)->get()->pluck('user_id');
                        $title = $request->title;
                        $body = $request->description;
                    }
                    if ($request->set_data == 'noticeboard') {
                        $announcement->table_id = null;
                        $announcement->table_type = '';
                        $user = Students::select('user_id')->get()->pluck('user_id');
                        $title = 'Noticeboard updated';
                        $body = $request->title;
                    }
                }
                $type = $request->set_data;
                $image = null;
                $userinfo = null;

                $announcement->save();
                if ($request->hasFile('file')) {
                    foreach ($request->file as $file_upload) {
                        $file = new File;
                        $file->file_name = FileNames::forUpload($file_upload);
                        $file->type = 1;
                        $file->file_url = $file_upload->store('announcement', 'public');
                        $file->modal()->associate($announcement);
                        $file->save();
                    }
                }
                sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
            DB::commit();
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                $response = [
                    'error' => false,
                    'message' => 'Data Stored successfully. But App push notification not send.',
                ];
            } else {
                DB::rollBack();
                $message = trans('error_occurred');
                $exceptionMessage = strtolower((string) $e->getMessage());
                if (str_contains($exceptionMessage, 'permission') || str_contains($exceptionMessage, 'unauthorized')) {
                    $message = trans('no_permission_message');
                } elseif (str_contains($exceptionMessage, 'subject') && str_contains($exceptionMessage, 'not')) {
                    $message = trans('no_data_found');
                }
                $response = [
                    'error' => true,
                    'message' => $message,
                ];
            }
        }

        return response()->json($response);
    }

    public function update(Request $request)
    {
        if (! Auth::user()->can('announcement-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $announcement = Announcement::findOrFail($request->input('edit_id'));
        abort_unless($this->canManageAnnouncement($announcement), 403);
        abort_unless((int) $announcement->session_year_id === (int) getSettings('session_year')['session_year'], 423);
        $this->validateAudience($request, true);
        $request->validate([
            'title' => 'required',
            'set_data' => 'required|in:class,class_section,noticeboard',
            'file' => 'nullable|array',
            'file.*' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif|max:4096',
        ], [
            'title.required' => trans('title_is_required'),
            'set_data.required' => trans('assign_to_field_is_required'),
        ]);
        DB::beginTransaction();
        try {
            $user = [];
            $title = ''; // Initialize $title to avoid undefined variable
            $body = '';  // Initialize $body to avoid undefined variable
            if (Auth::user()->teacher) {
                $teacher_id = Auth::user()->teacher->id;
            }
            $announcement->title = $request->title;
            $announcement->description = $request->description;
            $announcement->term_id = $request->term_id ?: null;
            if (! empty($request->set_data)) {
                if ($request->set_data == 'class_section') {
                    $allocation = $this->allocationQuery($request)->where('subject_id', $request->get_data);
                    $subject_teacher_id = (clone $allocation)->pluck('id');
                    $subject_name = $allocation->with('subject')->get();
                    if (count($subject_teacher_id) != 0) {
                        for ($j = 0; $j < count($subject_teacher_id); $j++) {
                            $subject_teacher = SubjectTeacher::find($subject_teacher_id[$j]);
                            $announcement->table()->associate($subject_teacher);
                            $user = Students::select('user_id')->where('class_section_id', $request->class_section_id)->get()->pluck('user_id');
                        }
                        if (isset($subject_name[0]) && isset($subject_name[0]->subject)) {
                            $title = 'Update announcement in '.$subject_name[0]->subject->name;
                        } else {
                            $title = 'Update announcement';
                        }
                        $body = $request->title;
                    }
                }
                if ($request->set_data == 'class') {
                    $class = ClassSchool::findOrFail($request->get_data);
                    abort_if(Auth::user()->hasRole('Teacher'), 403);
                    $announcement->table()->associate($class);
                    $get_class = ClassSection::select('id')->where('class_id', $request->get_data)->get()->pluck('id');
                    $user = Students::select('user_id')->whereIn('class_section_id', $get_class)->get()->pluck('user_id');
                    $title = $request->title;
                    $body = $request->description;
                }
                if ($request->set_data == 'noticeboard') {
                    $announcement->table_id = null;
                    $announcement->table_type = '';
                    $user = Students::select('user_id')->get()->pluck('user_id');
                    $title = 'Noticeboard updated';
                    $body = $request->title;
                }
            }
            $type = $request->set_data;
            $image = null;
            $userinfo = null;

            $announcement->save();

            if ($request->hasFile('file')) {
                foreach ($request->file as $file_upload) {
                    $file = new File;
                    $file->file_name = FileNames::forUpload($file_upload);
                    $file->type = 1;
                    $file->file_url = $file_upload->store('announcement', 'public');
                    $file->modal()->associate($announcement);
                    $file->save();
                }
            }
            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
            DB::commit();
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                $response = [
                    'error' => false,
                    'message' => 'Data Stored successfully. But App push notification not send.',
                ];
            } else {
                DB::rollBack();
                $response = [
                    'error' => true,
                    'message' => trans('error_occurred'),
                ];
            }
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show()
    {
        if (! Auth::user()->can('announcement-list')) {
            $response = [
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

        $session_year_id = $_GET['session_year_id'] ?? getSettings('session_year')['session_year'];
        $sql = Announcement::with('table', 'file', 'term')->where('session_year_id', $session_year_id);

        // Filter announcements for teachers
        if (Auth::user()->hasRole('Teacher')) {
            $teacher_id = Auth::user()->teacher->id;
            $sql->where(function ($query) use ($teacher_id) {
                $query->whereHasMorph('table', ['App\\Models\\SubjectTeacher'], function ($q) use ($teacher_id) {
                    $q->where('teacher_id', $teacher_id);
                });
            });
        }

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('title', 'LIKE', "%$search%")
                    ->orwhere('description', 'LIKE', "%$search%");
            });
        }

        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher');
        $canEdit = $user->can('announcement-edit');
        $canDelete = $user->can('announcement-delete');
        foreach ($res as $row) {
            $operate = '';
            // Teachers manage their own subject announcements; other authorised roles manage class and noticeboard announcements.
            // Gate the action buttons on the granular permissions so custom roles work, not on hardcoded role names.
            $canManageRow = $this->canManageAnnouncement($row);
            if ($canManageRow) {
                if ($canEdit) {
                    $operate .= '<a class="m3-button m3-button--text" href="'.e(route('announcement.index', ['notice_id' => $row->id])).'">'.e(__('Edit notice')).'</a>';
                }
                if ($canDelete) {
                    $operate .= '<a class="m3-button m3-button--text" data-notice-delete href="'.e(route('announcement.destroy', $row->id)).'">'.e(__('Delete notice')).'</a>';
                }
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['title'] = $row->title;
            $tempRow['description'] = $row->description;
            $tempRow['type'] = $row->table_type;
            if ($tempRow['type'] == 'App\\Models\\ClassSection') {
                $assign = 'class_section';
                $class = $row->table?->class?->name.' - '.$row->table?->section?->name;
                $class1 = $class;
            }
            if ($tempRow['type'] == 'App\\Models\\ClassSchool') {
                $assign = 'class';
                $class = $row->table?->name;
                $class1 = $class;
            }
            if ($tempRow['type'] == 'App\\Models\\SubjectTeacher') {
                $assign = 'Subject';
                $class = $row->table;
                $termName = $row->term ? ' - '.$row->term->name : '';
                $class1 = $row->table?->class_section?->class?->name.'-'.$row->table?->class_section?->section?->name.$termName.' - '.$row->table?->subject?->name;
            }
            if ($tempRow['type'] == '') {
                $assign = 'noticeboard';
                $class = trans('noticeboard');
                $class1 = $class;
            }
            $tempRow['assign'] = $assign;
            $tempRow['assign_to'] = $class;
            $tempRow['assignto'] = $class1;
            $tempRow['get_data'] = $row->table_id;
            $tempRow['file'] = $row->file;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('announcement-delete')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $announcement = Announcement::findOrFail($id);
        abort_unless($this->canManageAnnouncement($announcement), 403);
        try {
            $announcement->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    private function canManageAnnouncement(Announcement $announcement): bool
    {
        $actor = Auth::user();
        if ($actor->hasRole('Teacher')) {
            return $announcement->table_type === SubjectTeacher::class && $actor->teacher
                && SubjectTeacher::whereKey($announcement->table_id)->where('teacher_id', $actor->teacher->id)->exists();
        }

        return in_array($announcement->table_type, ['', ClassSchool::class], true);
    }

    private function validateAudience(Request $request, bool $editing): void
    {
        $request->validate(['set_data' => 'required|in:class,class_section,noticeboard']);
        abort_if(Auth::user()->hasRole('Teacher') && $request->input('set_data') !== 'class_section', 403);
        if ($request->input('set_data') === 'class_section') {
            abort_unless(Auth::user()->teacher, 403);
            $rules = ['class_section_id' => 'required|integer|min:1', 'term_id' => 'nullable|integer|min:1'];
            $request->validate($rules + ($editing ? ['get_data' => 'required|integer|min:1'] : ['get_data' => 'required|array|min:1|max:100', 'get_data.*' => 'required|integer|min:1|distinct']));
            $subjects = $editing ? [$request->integer('get_data')] : $request->input('get_data');
            abort_unless($this->allocationQuery($request)->whereIn('subject_id', $subjects)->distinct()->count('subject_id') === count($subjects), 404);
        } elseif ($editing && $request->input('set_data') === 'class') {
            $request->validate(['get_data' => 'required|integer|min:1']);
            ClassSchool::findOrFail($request->integer('get_data'));
        }
    }

    private function allocationQuery(Request $request): Builder
    {
        return SubjectTeacher::where('class_section_id', $request->integer('class_section_id'))
            ->where('teacher_id', Auth::user()->teacher->id)
            ->where('session_year_id', getSettings('session_year')['session_year'])
            ->where('term_id', $request->input('term_id') ?: null);
    }
}
