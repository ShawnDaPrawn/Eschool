<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\Platform\TemplateMail;
use App\Models\ClassTeacher;
use App\Models\EmailTemplate;
use App\Models\FormField;
use App\Models\Settings;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\SchoolTable;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! Auth::user()->can('teacher-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacherFields = FormField::where('for', 3)->orderBy('rank', 'ASC')->get();

        return view('teacher.index', compact('teacherFields'));
    }

    public function teacherListIndex()
    {
        if (! Auth::user()->can('teacher-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacherFields = FormField::where('for', 3)->orderBy('rank', 'ASC')->get();

        return view('teacher.details', compact('teacherFields'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('teacher-create') || ! Auth::user()->can('teacher-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'gender' => 'required',
                'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
                'mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
                'dob' => 'required|date',
                'qualification' => 'required',
                'current_address' => 'required',
                'permanent_address' => 'required',
            ],
            [
                'email.email' => trans('invalid_email'),
                'mobile.regex' => trans('mobile_number_between_7_and_15_digits'),
            ]
        );
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            // check if email exists in deleted_at records
            $check_user = User::where('email', $request->email)->onlyTrashed();
            if ($check_user->count()) {
                $user_exists = $check_user->first();
                DB::table('users')->where('id', $user_exists->id)->update(['deleted_at' => null]);
                $user = User::findOrFail($user_exists->id);
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    // made file name with combination of current time
                    $file_name = time().'-'.FileNames::forUpload($image);
                    // made file path to store in database
                    $file_path = 'teachers/'.$file_name;
                    // resized image
                    resizeImage($image);
                    // stored image to storage/public/teachers folder
                    $destinationPath = storage_path('app/public/teachers');
                    $image->move($destinationPath, $file_name);

                    $user->image = $file_path;
                } else {
                    $user->image = '';
                }
                $teacher_plain_text_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));
                $user->password = Hash::make($teacher_plain_text_password);

                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->gender = $request->gender;
                $user->current_address = $request->current_address;
                $user->permanent_address = $request->permanent_address;
                $user->email = $request->email;
                $user->mobile = $request->mobile;
                $user->dob = date('Y-m-d', strtotime($request->dob));
                $user->update();

                $check_teacher = SchoolTable::query('teachers')->where('user_id', $user->id)->whereNotNull('deleted_at');
                if ($check_teacher->count()) {
                    $teacher_exists = $check_teacher->first();
                    SchoolTable::query('teachers')->where('id', $teacher_exists->id)->update(['deleted_at' => null]);

                    $teacher = Teacher::findOrFail($teacher_exists->id);

                    $formFields = FormField::where('for', 3)->orderBy('rank', 'ASC')->get();
                    $data = [];
                    $status = 0;
                    $dynamic_data = json_decode($teacher->dynamic_field_values ?? '[]', true);
                    foreach ($formFields as $form_field) {
                        try {
                            // INPUT TYPE CHECKBOX
                            if ($form_field->type == 'checkbox') {
                                if ($status == 0) {
                                    $data[] = $request->input('checkbox', []);
                                    $status = 1;
                                }
                            } elseif ($form_field->type == 'file') {
                                // INPUT TYPE FILE
                                $get_file = '';
                                $field = str_replace(' ', '_', $form_field->name);
                                if ($dynamic_data && count($dynamic_data) > 0) {
                                    foreach ($dynamic_data as $field_data) {
                                        if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                            $get_file = $field_data[$field];
                                        }
                                    }
                                }
                                $hidden_file_name = 'file-'.$field;

                                if ($request->hasFile($field)) {
                                    if ($get_file) {
                                        Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                                    }
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('teachers', 'public')];
                                } else {
                                    if ($request->$hidden_file_name) {
                                        $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                    }
                                }
                            } else {
                                $field = str_replace(' ', '_', $form_field->name);
                                $fieldValue = $request->input($field, null);
                                $data[] = [str_replace(' ', '_', $form_field->name) => $fieldValue];
                            }
                        } catch (Exception $e) {
                            // Log the error but continue processing other fields
                            throw $e;
                        }
                    }
                    $teacher->user_id = $user->id;
                    $teacher->qualification = $request->qualification;
                    $teacher->dynamic_fields = json_encode($data);
                    $teacher->update();
                }
                if ($request->grant_permission) {
                    $user->syncPermissions([
                        'student-create',
                        'student-list',
                        'student-edit',
                        'student-delete',
                        'parents-list',
                        'parents-edit',
                    ]);
                } else {
                    $user->revokePermissionTo([
                        'student-create',
                        'student-list',
                        'student-edit',
                        'student-delete',
                        'parents-list',
                        'parents-edit',
                    ]);
                }
                $user->assignRole('Teacher');
                $school_name = getSettings('school_name');

                $data = [
                    'subject' => 'Welcome to '.$school_name['school_name'],
                    'name' => $request->first_name,
                    'email' => $request->email,
                    'password' => $teacher_plain_text_password,
                    'school_name' => $school_name['school_name'],
                ];

                MailService::sendWithFallback('teacher.email', $data, function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['subject']);
                });
            } else {
                $user = new User;
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    // made file name with combination of current time
                    $file_name = time().'-'.FileNames::forUpload($image);
                    // made file path to store in database
                    $file_path = 'teachers/'.$file_name;
                    // resized image
                    resizeImage($image);
                    // stored image to storage/public/teachers folder
                    $destinationPath = storage_path('app/public/teachers');
                    $image->move($destinationPath, $file_name);

                    $user->image = $file_path;
                } else {
                    $user->image = '';
                }
                $teacher_plain_text_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));
                $user->password = Hash::make($teacher_plain_text_password);

                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->gender = $request->gender;
                $user->current_address = $request->current_address;
                $user->permanent_address = $request->permanent_address;
                $user->email = $request->email;
                $user->mobile = $request->mobile;
                $user->dob = date('Y-m-d', strtotime($request->dob));
                $user->save();

                $teacher = new Teacher;

                $formFields = FormField::where('for', 3)->orderBy('rank', 'ASC')->get();
                $data = [];
                $status = 0;
                $dynamic_data = json_decode($teacher->dynamic_field_values ?? '[]', true);
                foreach ($formFields as $form_field) {
                    try {
                        // INPUT TYPE CHECKBOX
                        if ($form_field->type == 'checkbox') {
                            if ($status == 0) {
                                $data[] = $request->input('checkbox', []);
                                $status = 1;
                            }
                        } elseif ($form_field->type == 'file') {
                            // INPUT TYPE FILE
                            $get_file = '';
                            $field = str_replace(' ', '_', $form_field->name);
                            if ($dynamic_data && count($dynamic_data) > 0) {
                                foreach ($dynamic_data as $field_data) {
                                    if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                        $get_file = $field_data[$field];
                                    }
                                }
                            }
                            $hidden_file_name = 'file-'.$field;

                            if ($request->hasFile($field)) {
                                if ($get_file) {
                                    Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                                }
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('teachers', 'public')];
                            } else {
                                if ($request->$hidden_file_name) {
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                }
                            }
                        } else {
                            $field = str_replace(' ', '_', $form_field->name);
                            $fieldValue = $request->input($field, null);
                            $data[] = [str_replace(' ', '_', $form_field->name) => $fieldValue];
                        }
                    } catch (Exception $e) {
                        throw $e;
                    }
                }
                $teacher->user_id = $user->id;
                $teacher->qualification = $request->qualification;
                $teacher->dynamic_fields = json_encode($data);
                $teacher->save();
                if ($request->grant_permission) {
                    $user->syncPermissions([
                        'student-create',
                        'student-list',
                        'student-edit',
                        'student-delete',
                        'parents-list',
                        'parents-edit',
                    ]);
                } else {
                    $user->revokePermissionTo([
                        'student-create',
                        'student-list',
                        'student-edit',
                        'student-delete',
                        'parents-list',
                        'parents-edit',
                    ]);
                }
                $user->assignRole('Teacher');
                $roleName = $user->getRoleNames()->first();
                $school_name = getSettings('school_name');
                $school_email = getSettings('school_email');
                $school_contact = getSettings('school_phone');

                $applink = Settings::whereIn('type', ['teacher_app_link', 'teacher_ios_app_link'])->pluck('message', 'type')->toArray();
                $staffTemplate = EmailTemplate::where('type', 'staff_registration')->value('content');

                $cleanSchoolName = is_array($school_name) ? ($school_name['school_name'] ?? '') : $school_name;
                $cleanSchoolEmail = is_array($school_email) ? ($school_email['school_email'] ?? '') : $school_email;
                $cleanSchoolContact = is_array($school_contact) ? ($school_contact['school_phone'] ?? $school_contact['school_contact'] ?? '') : $school_contact;

                $androidLink = '';
                $iosLink = '';
                if (isset($applink)) {
                    // If $applink is an array or object, extract the string value
                    $androidLink = is_array($applink) ? ($applink['teacher_app_link'] ?? '') : ($applink->teacher_app_link ?? '');
                    $iosLink = is_array($applink) ? ($applink['teacher_ios_app_link'] ?? '') : ($applink->teacher_ios_app_link ?? '');
                }

                $data = [
                    'subject' => 'Welcome to '.(string) $cleanSchoolName,
                    'name' => (string) $request->first_name,
                    'email' => (string) $request->email,
                    'password' => (string) $teacher_plain_text_password,
                    'role' => (string) $roleName,
                    'school_name' => (string) $cleanSchoolName,
                    'school_email' => (string) $cleanSchoolEmail,
                    'school_contact' => (string) $cleanSchoolContact,
                    'android_app' => (string) $androidLink,
                    'ios_app' => (string) $iosLink,
                    'url' => (string) url('/'),
                    'support_email' => (string) $cleanSchoolEmail,
                    'support_contact' => (string) $cleanSchoolContact,

                ];

                // Sending the welcome email must never fail teacher creation
                try {
                    if (empty($staffTemplate)) {
                        Log::warning('Skipped teacher welcome email: staff_registration email template is missing', [
                            'user_id' => $user->id,
                        ]);
                    } else {
                        MailService::queue(TemplateMail::staffWelcome($data, 'teacher.email'), (int) $user->id);
                    }
                } catch (Throwable $e) {
                    Log::error('Failed to send teacher welcome email: '.$e->getMessage(), [
                        'user_id' => $user->id,
                    ]);
                }
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
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
        if (! Auth::user()->can('teacher-list')) {
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

        $sql = Teacher::with('user');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")
                ->orwhere('user_id', 'LIKE', "%$search%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")
                        ->orwhere('last_name', 'LIKE', "%$search%")
                        ->orwhere('gender', 'LIKE', "%$search%")
                        ->orwhere('email', 'LIKE', "%$search%")
                        ->orwhere('dob', 'LIKE', '%'.convertDateFormat($search, 'Y-m-d').'%')
                        ->orwhere('qualification', 'LIKE', "%$search%")
                        ->orwhere('current_address', 'LIKE', "%$search%")
                        ->orwhere('permanent_address', 'LIKE', "%$search%");
                }));
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
        $teacherIds = $res->pluck('id')->toArray();
        $teachersWithSubjectTeachers = SubjectTeacher::whereIn('teacher_id', $teacherIds)->pluck('teacher_id')->unique()->toArray();
        $teachersWithClassTeachers = ClassTeacher::whereIn('class_teacher_id', $teacherIds)->pluck('class_teacher_id')->unique()->toArray();

        foreach ($res as $row) {
            $relatedData = [];
            if (in_array($row->id, $teachersWithSubjectTeachers)) {
                $relatedData[] = 'subject teachers';
            }
            if (in_array($row->id, $teachersWithClassTeachers)) {
                $relatedData[] = 'class teachers';
            }

            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' data-url='.url('teachers').' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata"
            data-warning-text="'.trans('teacher_deleted_permanently_warning').'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').'
            data-id='.$row->id.' data-user_id='.$row->user_id.'
            data-url='.url('teachers', $row->user_id).'
            title="Delete"><i class="fa fa-trash"></i></a>';
            $isActive = (int) ($row->user?->status ?? 1) === 1;
            $operate .= '&nbsp;&nbsp;<a class="btn btn-xs btn-rounded btn-icon teacher-status '.($isActive ? 'btn-inverse-danger' : 'btn-inverse-success').'"
            data-url='.url('teachers/'.$row->user_id.'/status').' data-status='.($isActive ? 0 : 1).'
            data-confirm-text="'.e(trans($isActive ? 'teacher_deactivate_confirm' : 'teacher_activate_confirm')).'"
            title="'.e(trans($isActive ? 'deactivate' : 'activate')).'"><i class="fa '.($isActive ? 'fa-ban' : 'fa-check').'"></i></a>';

            $data = getSettings('date_formate');

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['first_name'] = $row->user?->first_name;
            $tempRow['last_name'] = $row->user?->last_name;
            $tempRow['gender'] = $row->user?->gender;
            $tempRow['current_address'] = $row->user?->current_address;
            $tempRow['permanent_address'] = $row->user?->permanent_address;
            $tempRow['email'] = $row->user?->email;
            $tempRow['dob'] = $row->user?->dob ? date($data['date_formate'], strtotime($row->user->dob)) : '';

            $tempRow['mobile'] = $row->user?->mobile;
            $tempRow['image'] = $row->user?->image;
            $tempRow['qualification'] = $row->qualification;
            $tempRow['status'] = $isActive ? 1 : 0;
            $tempRow['dynamic_data_field'] = json_decode($row->dynamic_fields ?? '[]');

            if ($row->user?->can(['student-create', 'student-list', 'student-edit', 'student-delete', 'parents-list', 'parents-edit'])) {
                $tempRow['has_student_permissions'] = 1;
            } else {
                $tempRow['has_student_permissions'] = 0;
            }

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Deactivates or reactivates a teacher. A deactivated teacher can't sign in
     * to the panel or the teacher app, and is signed out of the app at once.
     */
    public function changeStatus(Request $request, $id)
    {
        if (! Auth::user()->can('teacher-edit')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }

        $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()]);
        }

        $teacher = Teacher::where('user_id', $id)->with('user')->first();
        if (! $teacher || ! $teacher->user || $teacher->user_id === Auth::id()) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }

        try {
            $user = $teacher->user;
            $user->status = (int) $request->input('status');
            $user->save();
            if ($user->status !== 1) {
                AppTokens::revokeAll($user);
            }

            return response()->json(['error' => false, 'message' => trans('data_update_successfully')]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => true, 'message' => trans('error_occurred')]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        ResponseService::noPermissionThenSendJson('teacher-edit');
        $teacher = Teacher::find($id);

        return response($teacher);
    }

    public function update(Request $request)
    {
        if (! Auth::user()->can('teacher-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'gender' => 'required',
                'email' => 'required|email|unique:users,email,'.$request->user_id.',id,deleted_at,NULL',
                'mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
                'dob' => 'required|date',
                'qualification' => 'required',
                'current_address' => 'required',
                'permanent_address' => 'required',
            ],
            [
                'email.email' => trans('invalid_email'),
                'mobile.regex' => trans('mobile_number_between_7_and_15_digits'),
            ]
        );
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $user = User::find($request->user_id);
            if ($request->hasFile('image')) {
                if (Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }
                $image = $request->file('image');
                // made file name with combination of current time
                $file_name = time().'-'.FileNames::forUpload($image);
                // made file path to store in database
                $file_path = 'teachers/'.$file_name;
                // resized image
                resizeImage($image);
                // stored image to storage/public/teachers folder
                $destinationPath = storage_path('app/public/teachers');
                $image->move($destinationPath, $file_name);

                $user->image = $file_path;
            }
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->gender = $request->gender;
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->dob = date('Y-m-d', strtotime($request->dob));
            $user->save();

            $teacher = Teacher::find($request->id);

            // Teacher dynamic fields
            $formFields = FormField::where('for', 3)->orderBy('rank', 'ASC')->get();
            $data = [];
            $status = 0;
            $i = 0;
            $dynamic_data = json_decode($teacher->dynamic_fields ?? '[]', true);
            foreach ($formFields as $form_field) {
                // INPUT TYPE CHECKBOX
                if ($form_field->type == 'checkbox') {
                    if ($status == 0) {
                        $data[] = $request->input('checkbox', []);
                        $status = 1;
                    }
                } elseif ($form_field->type == 'file') {
                    // INPUT TYPE FILE
                    $get_file = '';
                    $field = str_replace(' ', '_', $form_field->name);
                    if (! is_null($dynamic_data)) {
                        foreach ($dynamic_data as $field_data) {
                            if (isset($field_data[$field])) { // GET OLD FILE IF EXISTS
                                $get_file = $field_data[$field];
                            }
                        }
                    }
                    $hidden_file_name = $field;

                    if ($request->hasFile($field)) {
                        if ($get_file) {
                            Storage::disk('public')->delete($get_file); // DELETE OLD FILE IF NEW FILE IS SELECT
                        }
                        $data[] = [
                            str_replace(' ', '_', $form_field->name) => $request->file($field)->store('teachers', 'public'),
                        ];
                    } else {
                        if ($request->$hidden_file_name) {
                            $data[] = [
                                str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name,
                            ];
                        }
                    }
                } else {
                    $field = str_replace(' ', '_', $form_field->name);
                    $data[] = [
                        str_replace(' ', '_', $form_field->name) => $request->$field,
                    ];
                }
            }
            $status = 0;
            // End teacher dynamic field
            $teacher->user_id = $user->id;
            $teacher->qualification = $request->qualification;
            $teacher->dynamic_fields = json_encode($data);
            $teacher->save();

            if ($request->edit_grant_permission) {
                $user->syncPermissions([
                    'student-create',
                    'student-list',
                    'student-edit',
                    'student-delete',
                    'parents-list',
                    'parents-edit',
                ]);
            } else {
                $user->revokePermissionTo([
                    'student-create',
                    'student-list',
                    'student-edit',
                    'student-delete',
                    'parents-list',
                    'parents-edit',
                ]);
            }

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('teacher-delete')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            DB::transaction(function () use ($id) {

                $teacher = Teacher::where('user_id', $id)
                    ->with('user')
                    ->firstOrFail();

                $user = $teacher->user;

                /**
                 * Remove class-teacher permission
                 */
                if ($user->hasPermissionTo('class-teacher')) {
                    $user->revokePermissionTo('class-teacher');
                }

                /**
                 * Delete user image
                 */
                if ($user->image && Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }

                /**
                 * IMPORTANT:
                 * Delete Teacher FIRST (triggers cascade)
                 */
                $teacher->delete();

                /**
                 * Then delete User, signed out of the apps even if restored later
                 */
                $user->tokens()->delete();
                $user->delete();
            });

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }
}
