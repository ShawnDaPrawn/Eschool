<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\Platform\TemplateMail;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\EmailTemplate;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\FormField;
use App\Models\OnlineExamStudentAnswer;
use App\Models\Parents;
use App\Models\Platform\Role;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\StudentOnlineExamStatus;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\MailService;
use App\Services\People\RemoveLearner;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Identity\LearnerInvitations;
use App\Services\ResponseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Admin panel: add, list, view, edit and delete students.
 */
class StudentController extends Controller
{
    public function index()
    {
        if (! Auth::user()->can('student-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $is_restricted_teacher = Auth::user()->hasRole('Teacher') && ! Auth::user()->can('student-create');

        // For restricted teachers, class sections will be loaded dynamically via AJAX based on session year
        if ($is_restricted_teacher) {
            $class_section = collect();
        } else {
            $class_section = ClassSection::with('class', 'section', 'class.medium', 'class.streams')->get();
        }

        $category = Category::where('status', 1)->get();
        $formFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return view('students.details', compact('class_section', 'category', 'formFields', 'session_years', 'is_restricted_teacher'));
    }

    public function create()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $class_section = ClassSection::with('class', 'section', 'streams')
            ->get();

        // $class_section = collect(); // default empty

        // // condition for teacher
        // if ($teacher = Auth::user()->teacher) {
        //     $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher->id)->pluck('class_section_id');
        //     $class_section = ClassSection::with('class', 'section', 'streams')
        //         ->whereIn('id', $class_section_ids)->get();
        // } else {
        //     // condition for super admin
        //     $class_section = ClassSection::with('class', 'section', 'streams')
        //         ->get();
        // }

        $studentFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
        $parentFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
        $category = Category::where('status', 1)->get();
        $data = getSettings('session_year');
        $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
        $get_student = Students::withTrashed()->select('id')->latest('id')->pluck('id')->first();
        $admission_no = $session_year.($get_student + 1);
        $admission_date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        return view('students.index', compact('class_section', 'category', 'admission_no', 'studentFields', 'parentFields', 'admission_date'));
    }

    public function store(Request $request)
    {
        // if (!Auth::user()->can('student-create') || !Auth::user()->can('student-edit')) {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $request->validate(
            [
                'first_name' => 'required',
                'last_name' => 'required',
                // Optional. Digits only (leading zeros allowed), 7 to 16 digits. Rejects negatives, decimals & '+'.
                'mobile' => 'nullable|numeric|regex:/^[0-9]{7,16}$/',
                'image' => 'mimes:jpeg,png,jpg|image|max:2048',
                'dob' => 'required',
                'class_section_id' => 'required',
                'category_id' => 'required',
                // admission_no is generated server-side, not from user input
                'admission_date' => 'required|date_format:d-m-Y',
                'current_address' => 'required',
                'permanent_address' => 'required',
                'height' => 'required|numeric|min:0',
                'weight' => 'required|numeric|min:0',
                'parent_guardian_type' => 'required|in:parent,guardian',
            ],
            [
                'mobile.regex' => trans('valid_mobile_number_between_7_and_16_digits'),
                'height.required' => trans('height_required'),
                'height.numeric' => trans('height_must_be_a_number'),
                'height.min' => trans('height_must_be_greater_than_0'),
                'weight.required' => trans('weight_required'),
                'weight.numeric' => trans('weight_must_be_a_number'),
                'weight.min' => trans('weight_must_be_greater_than_0'),
                'guardian_mobile.regex' => trans('guardian_mobile_number_between_7_and_15_digits'),
            ]
        );

        $response = [];
        try {
            DB::beginTransaction();

            // Generate admission_no server-side to prevent tampering
            $data = getSettings('session_year');
            $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
            $get_student = Students::withTrashed()->select('id')->latest('id')->pluck('id')->first();
            $admission_no = $session_year.($get_student + 1);

            $parentRole = Role::where('name', 'Parent')->first();
            $studentRole = Role::where('name', 'Student')->first();
            $father_parent_id = null;
            $mother_parent_id = null;
            $guardian_parent_id = null;
            // Add Father in User and Parent table data
            if ($request->parent_guardian_type == 'parent') {
                if (! intval($request->father_email)) {
                    $request->validate([
                        'father_email' => [
                            'required',
                            'email',
                            'unique:users,email',
                            'unique:parents,email',
                            'different:mother_email',
                        ],
                        'father_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                if (! intval($request->mother_email)) {
                    $request->validate([
                        'mother_email' => [
                            'required',
                            'email',
                            'unique:users,email',
                            'unique:parents,email',
                            'different:father_email',
                        ],
                        'mother_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                $request->validate([
                    'father_email' => 'different:mother_email',
                    'mother_email' => 'different:father_email',
                ], [
                    'father_email.different' => 'Father and Mother email cannot be the same.',
                    'mother_email.different' => 'Father and Mother email cannot be the same.',
                ]);

                $father_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->father_dob ?? '1990-01-01')));

                if (! intval($request->father_email)) {
                    $father_email = $request->father_email;
                    $father_user = new User;

                    $father_image = $request->file('father_image');
                    // made file name with combination of current time
                    $file_name = time().'-'.FileNames::forUpload($father_image);
                    // made file path to store in database
                    $file_path = 'parents/'.$file_name;
                    // resized image
                    resizeImage($father_image);
                    // stored image to storage/public/parents folder
                    $destinationPath = storage_path('app/public/parents');
                    $father_image->move($destinationPath, $file_name);

                    $father_user->image = $file_path;
                    $father_user->password = Hash::make($father_plaintext_password);
                    $father_user->first_name = $request->father_first_name;
                    $father_user->last_name = $request->father_last_name;
                    $father_user->email = $father_email;
                    $father_user->mobile = $request->father_mobile;
                    $father_user->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_user->gender = 'Male';
                    $father_user->save();
                    $father_user->assignRole($parentRole);

                    $father_parent = new Parents;
                    // Parent Dynamic FormField
                    $fatherFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
                    $data = [];
                    $status = 0;
                    $dynamic_data = json_decode($father_parent->dynamic_fields ?? '[]', true);
                    // dd($dynamic_data);

                    foreach ($fatherFields as $form_field) {

                        // INPUT TYPE CHECKBOX
                        if ($form_field->type == 'checkbox') {
                            if ($status == 0) {
                                $data[] = $request->input('father_checkbox', []);
                                $status = 1;
                            }
                        } elseif ($form_field->type == 'file') {
                            // INPUT TYPE FILE
                            $get_file = '';
                            $field = 'father_'.str_replace(' ', '_', $form_field->name);
                            if ($dynamic_data && count($dynamic_data) > 0) {
                                foreach ($dynamic_data as $field_data) {
                                    if (isset($field_data[$field])) {
                                        $get_file = $field_data[$field];
                                    }
                                }
                            }
                            $hidden_file_name = 'file-'.$field;

                            if ($request->hasFile($field)) {
                                if ($get_file) {
                                    Storage::disk('public')->delete($get_file);
                                }
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('parent', 'public')];
                            } else {
                                if ($request->$hidden_file_name) {
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                }
                            }
                        } else {
                            $field = 'father_'.str_replace(' ', '_', $form_field->name);
                            $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                        }
                    }
                    // End Parent Dynamic FormField
                    $father_parent->user_id = $father_user->id;
                    $father_parent->first_name = $request->father_first_name;
                    $father_parent->last_name = $request->father_last_name;
                    $father_parent->image = $father_user->getRawOriginal('image');
                    $father_parent->occupation = $request->father_occupation;
                    $father_parent->mobile = $request->father_mobile;
                    $father_parent->email = $request->father_email;
                    $father_parent->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_parent->gender = 'Male';
                    $father_parent->dynamic_fields = json_encode($data);
                    $father_parent->save();
                    $father_parent_id = $father_parent->id;
                    $father_email = $request->father_email;
                    $father_name = $request->father_first_name.' '.$request->father_last_name;
                } else {
                    $father_parent_id = $request->father_email;
                    $father_email = Parents::where('id', $request->father_email)->pluck('email')->first();
                    $fatherData = Parents::where('id', $request->father_email)->select('first_name', 'last_name')->first();
                    $father_name = $fatherData->full_name;
                }

                // Add Mother in User and Parent table data
                $mother_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->mother_dob)));
                if (! intval($request->mother_email)) {
                    $mother_email = $request->mother_email;
                    $mother_user = new User;

                    $mother_image = $request->file('mother_image');
                    // made file name with combination of current time
                    $file_name = time().'-'.FileNames::forUpload($mother_image);
                    // made file path to store in database
                    $file_path = 'parents/'.$file_name;
                    // resized image
                    resizeImage($mother_image);
                    // stored image to storage/public/parents folder
                    $destinationPath = storage_path('app/public/parents');
                    $mother_image->move($destinationPath, $file_name);

                    $mother_user->image = $file_path;
                    $mother_user->password = Hash::make($mother_plaintext_password);
                    $mother_user->first_name = $request->mother_first_name;
                    $mother_user->last_name = $request->mother_last_name;
                    $mother_user->email = $mother_email;
                    $mother_user->mobile = $request->mother_mobile;
                    $mother_user->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_user->gender = 'Female';
                    $mother_user->save();
                    $mother_user->assignRole($parentRole);

                    $mother_parent = new Parents;

                    // Parent Dynamic FormField
                    $motherFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
                    $data = [];
                    $status = 0;
                    $dynamic_data = json_decode($mother_parent->dynamic_fields ?? '[]', true);

                    foreach ($motherFields as $form_field) {

                        // INPUT TYPE CHECKBOX
                        if ($form_field->type == 'checkbox') {
                            if ($status == 0) {
                                $data[] = $request->input('mother_checkbox', []);
                                $status = 1;
                            }
                        } elseif ($form_field->type == 'file') {
                            // INPUT TYPE FILE
                            $get_file = '';
                            $field = 'mother_'.str_replace(' ', '_', $form_field->name);
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
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('parent', 'public')];
                            } else {
                                if ($request->$hidden_file_name) {
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                }
                            }
                        } else {
                            $field = 'mother_'.str_replace(' ', '_', $form_field->name);
                            $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                        }
                    }
                    // End Parent Dynamic FormField

                    $mother_parent->user_id = $mother_user->id;
                    $mother_parent->first_name = $request->mother_first_name;
                    $mother_parent->last_name = $request->mother_last_name;
                    $mother_parent->image = $mother_user->getRawOriginal('image');
                    $mother_parent->occupation = $request->mother_occupation;
                    $mother_parent->mobile = $request->mother_mobile;
                    $mother_parent->email = $request->mother_email;
                    $mother_parent->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_parent->gender = 'Female';
                    $mother_parent->dynamic_fields = json_encode($data);
                    $mother_parent->save();
                    $mother_parent_id = $mother_parent->id;
                    $mother_email = $request->mother_email;
                    $mother_name = $request->mother_first_name.' '.$request->mother_last_name;
                } else {
                    $mother_parent_id = $request->mother_email;
                    $mother_email = Parents::where('id', $request->mother_email)->pluck('email')->first();
                    $motherData = Parents::where('id', $request->mother_email)->select('first_name', 'last_name')->first();
                    $mother_name = $motherData->full_name;
                }
            } elseif ($request->parent_guardian_type == 'guardian') {
                if (isset($request->guardian_email)) {
                    if (isset($request->guardian_email) && ! intval($request->guardian_email)) {
                        $request->validate([
                            'guardian_email' => 'required|email|unique:parents,email',
                            'guardian_first_name' => 'required',
                            'guardian_last_name' => 'required',
                            'guardian_mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
                            'guardian_gender' => 'required',
                            'guardian_dob' => 'required',
                            'guardian_occupation' => 'required',
                            'guardian_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                        ]);
                    }
                    $guardian_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($request->guardian_dob)));
                    if (! intval($request->guardian_email)) {
                        $guardian_email = $request->guardian_email;
                        $guardian_user = new User;

                        $guardian_image = $request->file('guardian_image');
                        // made file name with combination of current time
                        $file_name = time().'-'.FileNames::forUpload($guardian_image);
                        // made file path to store in database
                        $file_path = 'parents/'.$file_name;
                        // resized image
                        resizeImage($guardian_image);
                        // stored image to storage/public/parents folder
                        $destinationPath = storage_path('app/public/parents');
                        $guardian_image->move($destinationPath, $file_name);

                        $guardian_user->image = $file_path;
                        $guardian_user->password = Hash::make($guardian_plaintext_password);
                        $guardian_user->first_name = $request->guardian_first_name;
                        $guardian_user->last_name = $request->guardian_last_name;
                        $guardian_user->email = $guardian_email;
                        $guardian_user->mobile = $request->guardian_mobile;
                        $guardian_user->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_user->gender = $request->guardian_gender;
                        $guardian_user->save();
                        $guardian_user->assignRole($parentRole);

                        $guardian_parent = new Parents;

                        // Parent Dynamic FormField
                        $guardianFields = FormField::where('for', 2)->orderBy('rank', 'ASC')->get();
                        $data = [];
                        $status = 0;
                        $dynamic_data = json_decode($guardian_parent->dynamic_fields ?? '[]', true);

                        foreach ($guardianFields as $form_field) {

                            // INPUT TYPE CHECKBOX
                            if ($form_field->type == 'checkbox') {
                                if ($status == 0) {
                                    $data[] = $request->input('guardian_checkbox', []);
                                    $status = 1;
                                }
                            } elseif ($form_field->type == 'file') {
                                // INPUT TYPE FILE
                                $get_file = '';
                                $field = 'guardian_'.str_replace(' ', '_', $form_field->name);
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
                                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('parent', 'public')];
                                } else {
                                    if ($request->$hidden_file_name) {
                                        $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                                    }
                                }
                            } else {
                                $field = 'guardian_'.str_replace(' ', '_', $form_field->name);
                                $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                            }
                        }
                        // End Parent Dynamic FormField

                        $guardian_parent->user_id = $guardian_user->id;
                        $guardian_parent->first_name = $request->guardian_first_name;
                        $guardian_parent->last_name = $request->guardian_last_name;
                        $guardian_parent->image = $guardian_user->getRawOriginal('image');
                        $guardian_parent->occupation = $request->guardian_occupation;
                        $guardian_parent->mobile = $request->guardian_mobile;
                        $guardian_parent->email = $guardian_email;
                        $guardian_parent->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_parent->gender = $request->guardian_gender;
                        $guardian_parent->dynamic_fields = json_encode($data);
                        $guardian_parent->save();
                        $guardian_parent_id = $guardian_parent->id;
                        $guardian_name = $request->guardian_first_name.' '.$request->guardian_last_name;
                    } else {
                        $guardian_parent_id = Parents::where('id', $request->guardian_email)->pluck('id')->first();
                        $guardian_email = Parents::where('id', $request->guardian_email)->pluck('email')->first();
                        $guardianData = Parents::where('id', $request->guardian_email)->select('first_name', 'last_name')->first();
                        $guardian_name = $guardianData->full_name;
                    }
                }
            }

            // Create Student User First

            $user = new User;

            // roll number
            $roll_number_db = Students::select(DB::raw('max(roll_number)'))->where('class_section_id', $request->class_section_id)->first();
            $roll_number_db = $roll_number_db['max(roll_number)'];
            $roll_number = $roll_number_db + 1;

            // No date-of-birth password: a random one nobody sees. Each guardian gets
            // a link to choose the learner's password (LearnerInvitations, below).
            $child_password_note = trans('learner_password_by_invitation');

            if ($request->hasFile('image')) {
                $student_image = $request->file('image');
                $file_name = time().'-'.FileNames::forUpload($student_image);
                $file_path = 'students/'.$file_name;
                resizeImage($student_image);
                $destinationPath = storage_path('app/public/students');
                $student_image->move($destinationPath, $file_name);
                $user->image = $file_path;
            }

            $user->password = Hash::make(LearnerInvitations::unguessablePassword());
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            //            $user->email = (isset($request->email)) ? $request->email : "";
            $user->email = $admission_no;
            $user->gender = $request->gender;
            $user->mobile = $request->mobile;
            $user->dob = date('Y-m-d', strtotime($request->dob ?? '2000-01-01'));
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->save();
            $user->assignRole($studentRole);

            $student = new Students;

            // Student dynamic fields
            $studentFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
            $data = [];
            $status = 0;
            $dynamic_data = json_decode($student->dynamic_fields ?? '[]', true);
            foreach ($studentFields as $form_field) {
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
                        $data[] = [str_replace(' ', '_', $form_field->name) => $request->file($field)->store('student', 'public')];
                    } else {
                        if ($request->$hidden_file_name) {
                            $data[] = [str_replace(' ', '_', $form_field->name) => $request->$hidden_file_name];
                        }
                    }
                } else {
                    $field = str_replace(' ', '_', $form_field->name);
                    $data[] = [str_replace(' ', '_', $form_field->name) => $request->$field];
                }
            }

            // End student dynamic field
            $student->user_id = $user->id;
            $student->class_section_id = $request->class_section_id;
            $student->category_id = $request->category_id;
            $student->admission_no = $admission_no;
            $student->roll_number = $roll_number;
            $student->caste = $request->caste;
            $student->religion = $request->religion;
            $student->admission_date = date('Y-m-d', strtotime($request->admission_date ?? '2000-01-01'));
            $student->blood_group = $request->blood_group;
            $student->height = $request->height;
            $student->weight = $request->weight;
            $student->father_id = $father_parent_id;
            $student->mother_id = $mother_parent_id;
            $student->guardian_id = $guardian_parent_id;
            $student->dynamic_fields = json_encode($data);
            $student->save();

            // -------------------------------------------------
            // Create student session entry for current session
            // -------------------------------------------------
            $currentSessionId = getSettings('session_year')['session_year'];
            StudentSessions::create([
                'student_id' => $student->id,
                'session_year_id' => $currentSessionId,
                'previous_session_year_id' => null,
                'class_section_id' => $request->class_section_id,
                'status' => 1,
                'result' => 1,
            ]);

            if ($request->class_section_id) {
                $classSection = ClassSection::where('id', $request->class_section_id)->with('class.medium', 'class.streams', 'section')->first();
                $class_section_name = $classSection->class->name.' - '.$classSection->section->name.' '.$classSection->class->medium->name.'  '.($classSection->class->streams->name ?? '');
            }
            $admin_mail = config('mail.from.address');

            // Send User Credentials via Email
            $settings = getSettings();
            $school_name = $settings['school_name'];
            $school_email = $settings['school_email'];
            $school_contact = $settings['school_phone'];

            $applink = Settings::whereIn('type', ['teacher_app_link', 'teacher_ios_app_link'])
                ->pluck('message', 'type')
                ->toArray();

            $android_app = $applink['teacher_app_link'] ?? '';
            $ios_app = $applink['teacher_ios_app_link'] ?? '';

            if ($request->parent_guardian_type == 'parent') {
                $father_data = [
                    'subject' => 'Welcome to '.$school_name,
                    'email' => (string) $father_email,
                    'name' => (string) ' '.$father_name,
                    'username' => (string) ' '.$father_email,
                    'password' => (string) ' '.$father_plaintext_password,
                    'child_name' => (string) ' '.$request->first_name.' '.$request->last_name,
                    'child_grnumber' => (string) ' '.$admission_no,
                    'child_password' => (string) ' '.$child_password_note,
                    'type' => 'application_accept',
                    'class_name' => (string) $class_section_name,
                    'school_name' => (string) $school_name,
                    'school_email' => (string) $school_email,
                    'school_contact' => (string) $school_contact,
                    'android_app' => (string) $android_app,
                    'ios_app' => (string) $ios_app,
                    'url' => url('/'),

                ];
                MailService::queue(TemplateMail::parentWelcome($father_data)->from($admin_mail, $school_name));

                // MailService::sendWithFallback('students.email', $father_data, function ($message) use ($father_data) {
                //     $message->to($father_data['email'])->subject($father_data['subject']);
                // });

                $mother_data = [
                    'subject' => 'Welcome to '.$school_name,
                    'email' => (string) $mother_email,
                    'name' => (string) ' '.$mother_name,
                    'username' => (string) ' '.$mother_email,
                    'password' => (string) ' '.$mother_plaintext_password,
                    'child_name' => (string) ' '.$request->first_name.' '.$request->last_name,
                    'child_grnumber' => (string) ' '.$admission_no,
                    'child_password' => (string) ' '.$child_password_note,
                    'type' => 'application_accept',
                    'class_name' => (string) $class_section_name,
                    'school_name' => (string) $school_name,
                    'school_email' => (string) $school_email,
                    'school_contact' => (string) $school_contact,
                    'android_app' => (string) $android_app,
                    'ios_app' => (string) $ios_app,
                    'url' => url('/'),
                ];
                MailService::queue(TemplateMail::parentWelcome($mother_data)->from($admin_mail, $school_name));

                // MailService::sendWithFallback('students.email', $mother_data, function ($message) use ($mother_data) {
                //     $message->to($mother_data['email'])->subject($mother_data['subject']);
                // });
            } else {
                $template = EmailTemplate::where('type', 'student_registration')->value('content');
                if (empty($template)) {
                    $template = "Hello , registration successful! Thank you for joining {$school_name}.";
                }

                $applink = Settings::whereIn('type', ['teacher_app_link', 'teacher_ios_app_link'])
                    ->pluck('message', 'type')
                    ->toArray();

                $android_app = $applink['teacher_app_link'] ?? '';
                $ios_app = $applink['teacher_ios_app_link'] ?? '';

                $guardian_data = [
                    'subject' => 'Welcome to '.(string) $school_name,
                    'email' => (string) $guardian_email,
                    'name' => (string) $guardian_name,
                    'username' => (string) $guardian_email,
                    'password' => (string) $guardian_plaintext_password,
                    'child_name' => (string) ($request->first_name.' '.$request->last_name),
                    'child_grnumber' => (string) $admission_no,
                    'child_password' => (string) $child_password_note,
                    'type' => 'application_accept',
                    'class_name' => (string) $class_section_name,
                    'school_name' => (string) $school_name,
                    'school_email' => (string) $school_email,
                    'school_contact' => (string) $school_contact,
                    'android_app' => (string) $android_app,
                    'ios_app' => (string) $ios_app,
                    'url' => (string) url('/'),
                ];

                MailService::queue(TemplateMail::parentWelcome($guardian_data, $template));
            }

            DB::commit();

            // After the commit, so a link never names a learner that was rolled back.
            rescue(fn () => app(LearnerInvitations::class)->inviteGuardians($user, Auth::user()));

            return ResponseService::successResponse(trans('data_store_successfully'));
        } catch (ValidationException $e) {
            DB::rollback();

            return ResponseService::errorResponse($e->validator->errors()->first(), $e->validator->errors(), 422);
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                return ResponseService::successResponse(trans('email_not_send'));
            } else {
                DB::rollback();
                report($e);

                return ResponseService::errorResponse(trans('error_occurred'), $e);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show()
    {
        if (! Auth::user()->can('student-list') && ! Auth::user()->can('generate-id-card')) {
            return response()->json(['message' => trans('no_permission_message')]);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        $hideActions = request('hide_actions', false);

        $user = Auth::user();
        $is_restricted_teacher = $user->hasRole('Teacher') && ! $user->can('student-create');

        $sql = StudentSessions::where('session_year_id', $session_year_id)
            ->whereHas('student.user', function ($query) {
                $query->where('status', 1);
            })
            ->with([
                'student.user',
                'student.category',
                'student.father',
                'student.mother',
                'student.guardian',
                'class_section.class.streams',
                'class_section.section',
            ])

            // Restrict to class teacher's class sections for this session year
            ->when($is_restricted_teacher, function ($q) use ($user, $session_year_id) {
                $class_section_ids = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                    ->where('session_year_id', $session_year_id)
                    ->pluck('class_section_id')
                    ->toArray();
                $q->whereIn('class_section_id', $class_section_ids);
            })

            /* ---------- SEARCH (on Students) ---------- */
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('admission_no', 'LIKE', "%$search%")
                            ->orWhere('roll_number', 'LIKE', "%$search%")
                            ->orWhere('caste', 'LIKE', "%$search%")
                            ->orWhere('religion', 'LIKE', "%$search%")
                            ->orWhereHas('user', function ($q) use ($search) {
                                $q->where('first_name', 'LIKE', "%$search%")
                                    ->orWhere('last_name', 'LIKE', "%$search%")
                                    ->orWhereRaw("concat(first_name,' ',last_name) LIKE ?", ["%$search%"])
                                    ->orWhere('email', 'LIKE', "%$search%");
                            });
                    });
                });
            })

            /* ---------- CLASS FILTER ---------- */
            ->when(request('class_id'), function ($q) {
                $q->where('class_section_id', request('class_id'));
            })

            ->when(request('class_section_id'), function ($q) {
                $q->where('class_section_id', request('class_section_id'));
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;

        $rows = [];
        $no = 1;
        $data = getSettings('date_formate');

        // Batch load related data existence to avoid N+1 queries
        $studentIds = $res->pluck('student_id')->unique()->toArray();
        $studentsWithSubmissions = AssignmentSubmission::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithAttendance = Attendance::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithExamMarks = ExamMarks::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithExamResults = ExamResult::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithOnlineAnswers = OnlineExamStudentAnswer::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithOnlineStatus = StudentOnlineExamStatus::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithSessions = StudentSessions::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();
        $studentsWithSubjects = StudentSubject::whereIn('student_id', $studentIds)->pluck('student_id')->unique()->toArray();

        foreach ($res as $sessionRow) {

            $student = $sessionRow->student;

            if (! $student) {
                continue;
            }
            $operate = '';
            if (! $hideActions) {
                if (Auth::user()->can('profile-person-view') || Auth::user()->hasRole('Teacher')) {
                    $operate .= '<a class="m3-button m3-button--text" href="'.e(route('people-profiles.index', ['person' => $student->user_id, 'profile_type' => 'learner'])).'">'.e(__('Person profile')).'</a>';
                }
                if (Auth::user()->can('student-edit')) {
                    $operate .= '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata"
            data-id='.$student->id.'
            data-url='.url('students').'
            title="Edit"
            data-toggle="modal"
            data-target="#editModal">
            <i class="fa fa-edit"></i>
        </a>&nbsp;&nbsp;';
                }

                if (Auth::user()->can('student-delete')) {
                    $relatedData = [];
                    if (in_array($student->id, $studentsWithSubmissions)) {
                        $relatedData[] = 'assignment submissions';
                    }
                    if (in_array($student->id, $studentsWithAttendance)) {
                        $relatedData[] = 'attendances';
                    }
                    if (in_array($student->id, $studentsWithExamMarks)) {
                        $relatedData[] = 'exam marks';
                    }
                    if (in_array($student->id, $studentsWithExamResults)) {
                        $relatedData[] = 'exam result';
                    }
                    if (in_array($student->id, $studentsWithOnlineAnswers)) {
                        $relatedData[] = 'online exam answers';
                    }
                    if (in_array($student->id, $studentsWithOnlineStatus)) {
                        $relatedData[] = 'online exam status';
                    }
                    if (in_array($student->id, $studentsWithSessions)) {
                        $relatedData[] = 'student sessions';
                    }
                    if (in_array($student->id, $studentsWithSubjects)) {
                        $relatedData[] = 'student subjects';
                    }

                    $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata"
            data-id='.$student->id.'
            data-user_id='.$student->user_id.'
            data-url='.url('students', $student->user_id).'
            data-warning-text="'.trans('student_deleted_permanently_warning').'"'
                        .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').'
            title="Delete">
            <i class="fa fa-trash"></i>
        </a>&nbsp;&nbsp;';
                }

                // Sign-in set-up: email the guardians a link, or print a one-time code.
                if (Auth::user()->canAny(['student-create', 'student-edit', 'student-reset-password'])) {
                    $operate .= '<div class="dropdown">
                <button class="btn btn-xs btn-gradient-info btn-rounded btn-icon dropdown-toggle"
                    type="button" data-toggle="dropdown" title="'.e(trans('learner_sign_in')).'">
                    <i class="fa fa-key"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item learner-invite" data-url="'.route('students.invite-guardians', $student->user_id).'">
                        <i class="fa fa-envelope mr-2"></i>'.e(trans('email_guardians_sign_in_link')).'</a>
                    <a href="#" class="dropdown-item learner-sheet" data-url="'.route('students.sign-in-sheet', $student->user_id).'">
                        <i class="fa fa-print mr-2"></i>'.e(trans('print_sign_in_sheet')).'</a>
                </div>
            </div>&nbsp;&nbsp;';
                }

                // Show certificate dropdown on Student Detail view only
                if (Auth::user()->can('generate-document')) {
                    $operate .= '<div class="dropdown">
                <button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle"
                    type="button" data-toggle="dropdown" title="Generate Document">
                    <i class="fa fa-file-pdf-o"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="'.route('bonafide.certificate.index', $student->id).'"
                        class="compulsory-data dropdown-item"
                        data-id='.$student->id.'>
                        <i class="fa fa-file-text text-success mr-2"></i>'.
                        trans('bonafide').' '.trans('certificate').
                        '</a>
                    <div class="dropdown-divider"></div>
                    <a href="'.route('leaving.certificate.index', $student->id).'"
                        class="optional-data dropdown-item"
                        data-id='.$student->id.'>
                        <i class="fa fa-file-text text-success mr-2"></i>'.
                        trans('leaving').' '.trans('certificate').
                        '</a>
                </div>
            </div>&nbsp;&nbsp;';
                }
            } else {
                // Show View Report eye icon on Student Report view only
                $operate .= '<a class="btn btn-xs btn-gradient-success btn-rounded btn-icon" href="'.route('student.report', $student->id).'" title="'.trans('student_report').'">
             <i class="fa fa-file-pdf-o"></i>
        </a>&nbsp;&nbsp;';
            }

            $tempRow = [];

            $tempRow['id'] = $student->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $student->user_id;
            $tempRow['full_name'] = $student->user->full_name;
            $tempRow['first_name'] = $student->user->first_name;
            $tempRow['last_name'] = $student->user->last_name;
            $tempRow['gender'] = $student->user->gender;
            $tempRow['email'] = $student->user->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime(
                is_object($student->user->dob)
                    ? $student->user->dob->toDateString()
                    : $student->user->dob
            ));
            $tempRow['mobile'] = $student->user->mobile;
            $tempRow['image'] = $student->user->image;
            $tempRow['image_link'] = $student->user->image;

            // From StudentSession
            $tempRow['class_section_id'] = $sessionRow->class_section_id ?? '';
            $tempRow['class_section_name'] =
                ($sessionRow->class_section->class->name ?? '').'-'.
                ($sessionRow->class_section->section->name ?? '');

            $tempRow['stream_name'] =
                $sessionRow->class_section->class->streams->name ?? '';

            // From Student
            $tempRow['category_id'] = $student->category_id;
            $tempRow['category_name'] = $student->category->name ?? '';
            $tempRow['admission_no'] = $student->admission_no;
            $tempRow['roll_number'] = $student->roll_number;
            $tempRow['caste'] = $student->caste;
            $tempRow['religion'] = $student->religion;
            $tempRow['admission_date'] = date(
                $data['date_formate'],
                strtotime($student->admission_date->toDateString())
            );
            $tempRow['blood_group'] = $student->blood_group;
            $tempRow['height'] = $student->height;
            $tempRow['weight'] = $student->weight;
            $tempRow['current_address'] = $student->user->current_address;
            $tempRow['permanent_address'] = $student->user->permanent_address;
            $tempRow['is_new_admission'] = $student->is_new_admission;
            $tempRow['dynamic_data_field'] =
                json_decode($student->dynamic_fields ?? '[]', true);

            // Father
            $tempRow['father_id'] = $student->father->id ?? '';
            $tempRow['father_email'] = $student->father->email ?? '';
            $tempRow['father_full_name'] = $student->father->full_name ?? '-';
            $tempRow['father_first_name'] = $student->father->first_name ?? '';
            $tempRow['father_last_name'] = $student->father->last_name ?? '';
            $tempRow['father_mobile'] = $student->father->mobile ?? '-';
            $tempRow['father_dob'] = $student->father?->dob ? date($data['date_formate'], strtotime($student->father->dob)) : '';
            $tempRow['father_occupation'] = $student->father->occupation ?? '';
            $tempRow['father_image'] = $student->father->image ?? '';
            $tempRow['father_image_link'] = $student->father->image ?? '';

            // Mother
            $tempRow['mother_id'] = $student->mother->id ?? '';
            $tempRow['mother_email'] = $student->mother->email ?? '';
            $tempRow['mother_full_name'] = $student->mother->full_name ?? '-';
            $tempRow['mother_first_name'] = $student->mother->first_name ?? '';
            $tempRow['mother_last_name'] = $student->mother->last_name ?? '';
            $tempRow['mother_mobile'] = $student->mother->mobile ?? '';
            $tempRow['mother_dob'] = $student->mother?->dob ? date($data['date_formate'], strtotime($student->mother->dob)) : '';
            $tempRow['mother_occupation'] = $student->mother->occupation ?? '';
            $tempRow['mother_image'] = $student->mother->image ?? '';
            $tempRow['mother_image_link'] = $student->mother->image ?? '';

            // Guardian
            $tempRow['guardian_id'] = $student->guardian->id ?? '';
            $tempRow['guardian_email'] = $student->guardian->email ?? '';
            $tempRow['guardian_full_name'] = $student->guardian->full_name ?? '-';
            $tempRow['guardian_first_name'] = $student->guardian->first_name ?? '';
            $tempRow['guardian_last_name'] = $student->guardian->last_name ?? '';
            $tempRow['guardian_mobile'] = $student->guardian->mobile ?? '-';
            $tempRow['guardian_gender'] = $student->guardian->gender ?? '';
            $tempRow['guardian_dob'] = $student->guardian?->dob ? date($data['date_formate'], strtotime($student->guardian->dob)) : '';
            $tempRow['guardian_occupation'] = $student->guardian->occupation ?? '';
            $tempRow['guardian_image'] = $student->guardian->image ?? '';
            $tempRow['guardian_image_link'] = $student->guardian->image ?? '';

            $tempRow['operate'] = $operate;

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function update(Request $request)
    {
        if (! Auth::user()->can('student-create') || ! Auth::user()->can('student-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $request->validate(
            [
                'first_name' => 'required',
                'last_name' => 'required',
                // Optional. Digits only (leading zeros allowed), 7 to 16 digits. Rejects negatives, decimals & '+'.
                'mobile' => 'nullable|numeric|regex:/^[0-9]{7,16}$/',
                'image' => 'mimes:jpeg,png,jpg|image|max:2048',
                'dob' => 'required',
                'class_section_id' => 'required',
                'category_id' => 'required',
                'admission_no' => 'required|unique:students,admission_no,'.$request->edit_id.',user_id',
                'roll_number' => 'required',
                'admission_date' => 'required|date_format:d-m-Y',
                'current_address' => 'required',
                'permanent_address' => 'required',
                'height' => 'required|numeric|min:0',
                'weight' => 'required|numeric|min:0',
                'edit_parent_guardian_type' => 'required|in:parent,guardian',
            ],
            [
                'mobile.regex' => trans('valid_mobile_number_between_7_and_16_digits'),
                'height.required' => trans('height_required'),
                'height.numeric' => trans('height_must_be_a_number'),
                'height.min' => trans('height_must_be_greater_than_0'),
                'weight.required' => trans('weight_required'),
                'weight.numeric' => trans('weight_must_be_a_number'),
                'weight.min' => trans('weight_must_be_greater_than_0'),
            ]
        );
        try {
            // Add Father in User and Parent table data
            if ($request->edit_parent_guardian_type == 'parent') {
                if (! intval($request->father_email)) {
                    $request->validate([
                        'father_email' => 'required|email|unique:users,email,'.$request->father_email.'|unique:parents,email,'.$request->father_email,
                        'father_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }

                if (! intval($request->mother_email)) {
                    $request->validate([
                        'mother_email' => 'required|email|unique:users,email,'.$request->mother_email.'|unique:parents,email,'.$request->mother_email,
                        'mother_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }
                if (! intval($request->father_email)) {
                    $father_user = new User;
                    $father_user->image = $request->file('father_image')->store('parents', 'public');
                    $father_user->password = Hash::make(str_replace('/', '', $request->father_dob));
                    $father_user->first_name = $request->father_first_name;
                    $father_user->last_name = $request->father_last_name;
                    $father_user->email = $request->father_email;
                    $father_user->mobile = $request->father_mobile;
                    $father_user->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_user->gender = 'Male';
                    $father_user->save();

                    $father_parent = new Parents;
                    $father_parent->user_id = $father_user->id;
                    $father_parent->first_name = $request->father_first_name;
                    $father_parent->last_name = $request->father_last_name;
                    $father_parent->image = $father_user->getRawOriginal('image');
                    $father_parent->occupation = $request->father_occupation;
                    $father_parent->mobile = $request->father_mobile;
                    $father_parent->email = $request->father_email;
                    $father_parent->dob = date('Y-m-d', strtotime($request->father_dob ?? '1990-01-01'));
                    $father_parent->gender = 'Male';
                    $father_parent->dynamic_fields = '';
                    $father_parent->save();
                    $father_parent_id = $father_parent->id;
                } else {
                    $father_parent_id = $request->father_email;
                }

                // Add Mother in User and Parent table data
                if (! intval($request->mother_email)) {
                    $mother_user = new User;
                    $mother_user->image = $request->file('mother_image')->store('parents', 'public');
                    $mother_user->password = Hash::make(str_replace('/', '', $request->mother_dob));
                    $mother_user->first_name = $request->mother_first_name;
                    $mother_user->last_name = $request->mother_last_name;
                    $mother_user->email = $request->mother_email;
                    $mother_user->mobile = $request->mother_mobile;
                    $mother_user->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_user->gender = 'Female';
                    $mother_user->save();

                    $mother_parent = new Parents;
                    $mother_parent->user_id = 0;
                    $mother_parent->first_name = $request->mother_first_name;
                    $mother_parent->last_name = $request->mother_last_name;
                    $mother_parent->image = $mother_user->getRawOriginal('image');
                    $mother_parent->occupation = $request->mother_occupation;
                    $mother_parent->mobile = $request->mother_mobile;
                    $mother_parent->email = $request->mother_email;
                    $mother_parent->dob = date('Y-m-d', strtotime($request->mother_dob ?? '1990-01-01'));
                    $mother_parent->gender = 'Female';
                    $mother_parent->dynamic_fields = '';
                    $mother_parent->save();
                    $mother_parent_id = $mother_parent->id;
                } else {
                    $mother_parent_id = $request->mother_email;
                }
            } elseif ($request->edit_parent_guardian_type == 'guardian') {
                if (isset($request->guardian_email) && ! intval($request->guardian_email)) {
                    $request->validate([
                        'guardian_email' => 'required|email|unique:parents,email,'.$request->guardian_email,
                        'guardian_image' => 'required|mimes:jpeg,png,jpg|image|max:2048',
                    ]);
                }
                if (isset($request->guardian_email)) {
                    if (! intval($request->guardian_email)) {
                        $guardian_email = $request->guardian_email;
                        $guardian_user = new User;

                        $guardian_image = $request->file('guardian_image');
                        // made file name with combination of current time
                        $file_name = time().'-'.FileNames::forUpload($guardian_image);
                        // made file path to store in database
                        $file_path = 'parents/'.$file_name;
                        // resized image
                        resizeImage($guardian_image);
                        // stored image to storage/public/parents folder
                        $destinationPath = storage_path('app/public/parents');
                        $guardian_image->move($destinationPath, $file_name);

                        $guardian_user->image = $file_path;
                        $guardian_user->password = Hash::make(str_replace('/', '', $request->guardian_dob));
                        $guardian_user->first_name = $request->guardian_first_name;
                        $guardian_user->last_name = $request->guardian_last_name;
                        $guardian_user->email = $guardian_email;
                        $guardian_user->mobile = $request->guardian_mobile;
                        $guardian_user->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_user->gender = $request->guardian_gender;
                        $guardian_user->save();

                        $guardian_parent = new Parents;
                        $guardian_parent->user_id = $guardian_user->id;
                        $guardian_parent->first_name = $request->guardian_first_name;
                        $guardian_parent->last_name = $request->guardian_last_name;
                        $guardian_parent->image = $guardian_user->getRawOriginal('image');
                        $guardian_parent->occupation = $request->guardian_occupation;
                        $guardian_parent->mobile = $request->guardian_mobile;
                        $guardian_parent->email = $request->guardian_email;
                        $guardian_parent->dob = date('Y-m-d', strtotime($request->guardian_dob ?? '1990-01-01'));
                        $guardian_parent->gender = $request->guardian_gender;
                        $guardian_parent->dynamic_fields = '';
                        $guardian_parent->save();
                        $guardian_parent_id = $guardian_parent->id;
                    } else {
                        $guardian_parent_id = $request->guardian_email;
                    }
                } else {
                    $guardian_parent_id = 0;
                }
            }

            // Create Student User First
            $user = User::find($request->edit_id);
            // $user->password = Hash::make(str_replace('/', '', $request->dob));
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            // $user->email = (isset($request->email)) ? $request->email : "";
            // $user->email = $request->admission_no;
            $user->mobile = (isset($request->mobile)) ? $request->mobile : '';
            $user->dob = date('Y-m-d', strtotime($request->dob ?? '2000-01-01'));
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->gender = $request->gender;

            // If Image exists then upload new image and delete the old image
            if ($request->hasFile('image')) {
                if (Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }

                $student_image = $request->file('image');
                // made file name with combination of current time
                $file_name = time().'-'.FileNames::forUpload($student_image);
                // made file path to store in database
                $file_path = 'students/'.$file_name;
                // resized image
                resizeImage($student_image);
                // stored image to storage/public/students folder
                $destinationPath = storage_path('app/public/students');
                $student_image->move($destinationPath, $file_name);
                $user->image = $file_path;
            }
            $user->save();

            $student = Students::where('user_id', $user->id)->firstOrFail();
            // Student dynamic fields
            $formFields = FormField::where('for', 1)->orderBy('rank', 'ASC')->get();
            $data = [];
            $status = 0;
            $dynamic_data = json_decode($student->dynamic_fields ?? '[]', true);

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
                            str_replace(' ', '_', $form_field->name) => $request->file($field)->store('students', 'public'),
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
            // End student dynamic field
            $student->class_section_id = $request->class_section_id;
            $student->category_id = $request->category_id;

            $student->roll_number = $request->roll_number;
            $student->caste = $request->caste;
            $student->religion = $request->religion;
            $student->admission_date = date('Y-m-d', strtotime($request->admission_date ?? '2000-01-01'));
            $student->blood_group = $request->blood_group;
            $student->height = $request->height;
            $student->weight = $request->weight;
            $student->father_id = $father_parent_id ?? 0;
            $student->mother_id = $mother_parent_id ?? 0;
            $student->guardian_id = $guardian_parent_id ?? 0;
            $student->dynamic_fields = json_encode($data);
            $student->update();

            // The apps, attendance, marks and fees read the class from this year's session
            // row, which a class change here used to leave on the old class.
            StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', getSettings('session_year')['session_year'])
                ->whereNotNull('class_section_id')
                ->get()->each(fn (StudentSessions $session) => $session->update(['class_section_id' => $student->class_section_id]));

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Exception $e) {
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
        if (! Auth::user()->can('student-delete')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            DB::transaction(function () use ($id) {

                $student = Students::where('user_id', $id)->firstOrFail();
                $user = User::findOrFail($id);

                // Collect parent IDs before deleting the student
                $parentIds = collect([$student->father_id, $student->mother_id, $student->guardian_id])
                    ->filter()
                    ->unique()
                    ->values();

                // Delete user image
                if ($user->image && Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }

                // Delete parent images
                if ($student->father_image && Storage::disk('public')->exists($student->father_image)) {
                    Storage::disk('public')->delete($student->father_image);
                }

                if ($student->mother_image && Storage::disk('public')->exists($student->mother_image)) {
                    Storage::disk('public')->delete($student->mother_image);
                }

                /**
                 * IMPORTANT:
                 * Delete student first → triggers model cascade
                 */
                $student->delete();

                /**
                 * Then delete user
                 */
                $user->delete();

                /**
                 * Clean up orphaned parents.
                 * If no other student references this parent (as father, mother, or guardian),
                 * delete the parent and their associated user.
                 */
                foreach ($parentIds as $parentId) {
                    $hasOtherChildren = Students::where(function ($query) use ($parentId) {
                        $query->where('father_id', $parentId)
                            ->orWhere('mother_id', $parentId)
                            ->orWhere('guardian_id', $parentId);
                    })->exists();

                    if (! $hasOtherChildren) {
                        $parent = Parents::find($parentId);
                        if ($parent) {
                            // Delete parent's image
                            if ($parent->getRawOriginal('image') && Storage::disk('public')->exists($parent->getRawOriginal('image'))) {
                                Storage::disk('public')->delete($parent->getRawOriginal('image'));
                            }

                            // Delete parent's user
                            if ($parent->user_id) {
                                $parentUser = User::find($parent->user_id);
                                if ($parentUser) {
                                    if ($parentUser->image && Storage::disk('public')->exists($parentUser->image)) {
                                        Storage::disk('public')->delete($parentUser->image);
                                    }
                                    $parentUser->delete();
                                }
                            }

                            $parent->delete();
                        }
                    }
                }
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

    public function permanentDelete($id)
    {
        if (! Auth::user()->can('student-delete')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        try {
            $student = Students::with('user')->where('id', $id)->first();

            if ($student) {
                app(RemoveLearner::class)->permanently($student);
            }

            $response = [
                'error' => false,
                'message' => trans('user_delete_successfully'),
            ];
        } catch (ValidationException $e) {
            return response()->json(['error' => true, 'message' => $e->validator->errors()->first()]);
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
