<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\EmailTemplate;
use App\Models\FormField;
use App\Models\Parents;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Admin panel: review and accept or reject online student registrations.
 */
class OnlineRegistrationController extends Controller
{
    public function onlineRegistrationIndex()
    {
        if (! Auth::user()->can('online-registration-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $classSchools = ClassSchool::with('medium', 'streams')->get();
        $category = Category::where('status', 1)->get();
        $formFields = FormField::where('for', 4)->orderBy('rank', 'ASC')->get();

        return view('students.online_registration', compact('classSchools', 'category', 'formFields'));
    }

    public function onlineRegistrationList()
    {
        if (! Auth::user()->can('online-registration-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $session_year_id = request('session_year_id');

        $sessionYear = null;
        if ($session_year_id) {
             $sessionYear = SessionYear::find($session_year_id);
           
            if ($sessionYear) {
                $startDate = $sessionYear->start_date;
                $endDate = $sessionYear->end_date;

            }
        }

        $sql = Students::with('user', 'class', 'category', 'father', 'mother', 'guardian')->where('application_type', 'online')
            // Search query
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_id', 'LIKE', "%$search%")
                        ->orWhere('class_id', 'LIKE', "%$search%")
                        ->orWhere('category_id', 'LIKE', "%$search%")
                        ->orWhere('admission_no', 'LIKE', "%$search%")
                        ->orWhere('roll_number', 'LIKE', "%$search%")
                        ->orWhere('caste', 'LIKE', "%$search%")
                        ->orWhere('religion', 'LIKE', "%$search%")
                        ->orWhere('blood_group', 'LIKE', "%$search%")
                        ->orWhere('height', 'LIKE', "%$search%")
                        ->orWhere('weight', 'LIKE', "%$search%")
                        ->orWhere('is_new_admission', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('father', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('mobile', 'LIKE', "%$search%")
                                ->orWhere('occupation', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('mother', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('mobile', 'LIKE', "%$search%")
                                ->orWhere('occupation', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        })
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        });
                });
            })
            // Class filter data
            ->when(request('class_id') != null, function ($query) {
                $classId = request('class_id');
                $query->where(function ($query) use ($classId) {
                    $query->where('class_id', $classId);
                });
            })
            // Filter by user status (0 = pending, 2 = rejected)
            ->whereHas('user', function ($query) {
                $query->whereIn('status', [0, 2]);
            });
        if ($sessionYear) {
            $sql->whereBetween('created_at', [
                $sessionYear->start_date,
                $sessionYear->end_date,
            ]);
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        // dd($res->toArray());
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        $data = getSettings('date_formate');
        foreach ($res as $row) {
            $operate = '';
            $userStatus = $row->user->status ?? 0;

            if (Auth::user()->can('student-edit')) {
                if ($userStatus == 0) {
                    // Pending application — open the modal in edit mode to review/accept/reject it
                     $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' data-url='.url('students').' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                } else {
                    // Already processed (accepted/rejected) — open the same modal in read-only/view mode
                     $operate = '<a class="btn btn-xs btn-gradient-info btn-rounded btn-icon viewdata" data-id='.$row->id.' data-url='.url('students').' title="View" data-toggle="modal" data-target="#editModal"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
                }
            }

            if (Auth::user()->can('student-delete')) {
                 $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletepermanentdata" data-id='.$row->id.' data-user_id='.$row->user_id.' data-url='.url('permanent-delete', $row->id).' title="Permanent Delete"><i class="fa fa-trash"></i></a>&nbsp;&nbsp;';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['full_name'] = $row->user->full_name;
            $tempRow['first_name'] = $row->user->first_name;
            $tempRow['last_name'] = $row->user->last_name;
            $tempRow['gender'] = $row->user->gender;
            $tempRow['email'] = $row->user->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime(is_object($row->user->dob) ? $row->user->dob->toDateString() : $row->user->dob));
            $tempRow['mobile'] = $row->user->mobile;
            $tempRow['image'] = $row->user->image;
            $tempRow['image_link'] = $row->user->image;
            $tempRow['class_id'] = $row->class_id;
            $tempRow['class_name'] = $row->class->name.'-'.$row->class->medium->name.' '.($row->class->streams->name ?? '');
            $tempRow['category_id'] = $row->category_id;
            $tempRow['category_name'] = $row->category->name;
            $tempRow['admission_no'] = $row->admission_no;
            $tempRow['caste'] = $row->caste;
            $tempRow['religion'] = $row->religion;
            $tempRow['admission_date'] = date($data['date_formate'], strtotime(is_object($row->admission_date) ? $row->admission_date->toDateString() : $row->admission_date));
            $tempRow['blood_group'] = $row->blood_group;
            $tempRow['height'] = $row->height;
            $tempRow['weight'] = $row->weight;
            $tempRow['current_address'] = $row->user->current_address;
            $tempRow['permanent_address'] = $row->user->permanent_address;
            $tempRow['is_new_admission'] = $row->is_new_admission;
            $tempRow['user_status'] = $userStatus;
            $tempRow['dynamic_data_field'] = json_decode($row->dynamic_fields ?? '[]', true);

            // Father Data
            $tempRow['father_id'] = ! empty($row->father) ? $row->father->id : '';
            $tempRow['father_email'] = ! empty($row->father) ? $row->father->email : '';
            $tempRow['father_full_name'] = ! empty($row->father) ? $row->father->full_name : '-';
            $tempRow['father_first_name'] = ! empty($row->father) ? $row->father->first_name : '-';
            $tempRow['father_last_name'] = ! empty($row->father) ? $row->father->last_name : '';
            $tempRow['father_mobile'] = ! empty($row->father) ? $row->father->mobile : '-';
            $tempRow['father_dob'] = ! empty($row->father) && $row->father->dob ? date($data['date_formate'], strtotime($row->father->dob)) : '';
            $tempRow['father_occupation'] = ! empty($row->father) ? $row->father->occupation : '';
            $tempRow['father_image'] = ! empty($row->father) ? $row->father->image : '';
            $tempRow['father_image_link'] = ! empty($row->father) ? $row->father->image : '';

            // Mother Data
            $tempRow['mother_id'] = ! empty($row->mother) ? $row->mother->id : '';
            $tempRow['mother_email'] = ! empty($row->mother) ? $row->mother->email : '';
            $tempRow['mother_first_name'] = ! empty($row->mother) ? $row->mother->first_name : '-';
            $tempRow['mother_last_name'] = ! empty($row->mother) ? $row->mother->last_name : '';
            $tempRow['mother_mobile'] = ! empty($row->mother) ? $row->mother->mobile : '';
            $tempRow['mother_dob'] = ! empty($row->mother) && $row->mother->dob ? date($data['date_formate'], strtotime($row->mother->dob)) : '';
            $tempRow['mother_occupation'] = ! empty($row->mother) ? $row->mother->occupation : '';
            $tempRow['mother_image'] = ! empty($row->mother) ? $row->mother->image : '';
            $tempRow['mother_image_link'] = ! empty($row->mother) ? $row->mother->image : '';

            // Guardian Data
            $tempRow['guardian_id'] = ! empty($row->guardian) ? $row->guardian->id : '';
            $tempRow['guardian_email'] = ! empty($row->guardian) ? $row->guardian->email : '';
            $tempRow['guardian_full_name'] = ! empty($row->guardian) ? $row->guardian->full_name : '-';
            $tempRow['guardian_first_name'] = ! empty($row->guardian) ? $row->guardian->first_name : '-';
            $tempRow['guardian_last_name'] = ! empty($row->guardian) ? $row->guardian->last_name : '';
            $tempRow['guardian_mobile'] = ! empty($row->guardian) ? $row->guardian->mobile : '-';
            $tempRow['guardian_gender'] = ! empty($row->guardian) ? $row->guardian->gender : '';
            $tempRow['guardian_dob'] = ! empty($row->guardian) && $row->guardian->dob ? date($data['date_formate'], strtotime($row->guardian->dob)) : '';
            $tempRow['guardian_occupation'] = ! empty($row->guardian) ? $row->guardian->occupation : '';
            $tempRow['guardian_image'] = ! empty($row->guardian) ? $row->guardian->image : '';
            $tempRow['guardian_image_link'] = ! empty($row->guardian) ? $row->guardian->image : '';

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function updateStatus(Request $request)
    {
        if (! Auth::user()->can('student-edit')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $request->validate([
    'status' => 'required',
]);
        try {
            $user = User::with('student')->where('id', $request->edit_id)->first();

            // // Prevent re-processing of already rejected students
            // if ($user->status == 2) {
            //     return response()->json([
            //         'error' => true,
            //         'message' => trans('student_already_rejected'),
            //     ]);
            // }

            $class = ClassSchool::with('medium', 'streams')->where('id', $request->class_id)->first();
            $class_name = $class->name.' - '.$class->medium->name.' '.($class->streams->name ?? '');

            $child_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($user->dob)));
            $father_id = $user->student->father_id ?? null;
            $mother_id = $user->student->mother_id ?? null;
            $guardian_id = $user->student->guardian_id ?? null;
            $parent_id = [$father_id, $mother_id, $guardian_id];

            $parents = Parents::with('user')->whereIn('id', $parent_id)->get();
            $admin_mail = config('mail.from.address');

            $settings = getSettings();
            $school_name = $settings['school_name'];
            $school_email = $settings['school_email'];
            $school_contact = $settings['school_phone'];

            if ($request->status == 1) {
                $user->student->class_section_id = $request->class_section_id;
                $user->status = $request->status;
                $user->student->update();
                $user->update();

                // -------------------------------------------------
                // Ensure student session entry exists for current session
                // -------------------------------------------------
                $currentSessionId = getSettings('session_year')['session_year'];
                StudentSessions::updateOrCreate(
                    [
                        'student_id' => $user->student->id,
                        'session_year_id' => $currentSessionId,
                    ],
                    [
                        'previous_session_year_id' => null,
                        'class_section_id' => $request->class_section_id,
                        'status' => 1,
                        'result' => 1,
                    ]
                );
                $class_section_name = '';

                if ($request->class_section_id) {
                    $classSection = ClassSection::where('id', $request->class_section_id)->with('class.medium', 'class.streams', 'section')->first();
                    $class_section_name = $classSection->class->name.' - '.$classSection->section->name.' '.$classSection->class->medium->name.'  '.($classSection->class->streams->name ?? '');
                }
                // Send User Credentials via Email
                $AcceptTemplate = EmailTemplate::where('type', 'student_registration')->value('content');
                $applink = Settings::whereIn('type', ['teacher_app_link', 'teacher_ios_app_link'])
                    ->pluck('message', 'type')
                    ->toArray();

                $android_app = $applink['teacher_app_link'] ?? '';
                $ios_app = $applink['teacher_ios_app_link'] ?? '';

                foreach ($parents as $parent) {
                    $parent->user->status = 1;
                    $parent->user->update();

                    // Template content is required to render the credentials email.
                    if (empty($AcceptTemplate)) {
                        continue;
                    }

                    $parent_plaintext_password = str_replace('-', '', date('d-m-Y', strtotime($parent->dob)));

                    $parent_data = [
                        'subject' => 'Welcome to '.$school_name,
                        'email' => (string) $parent->email,
                        'name' => (string) ' '.$parent->first_name.' '.$parent->last_name,
                        'username' => (string) ' '.$parent->email,
                        'password' => (string) ' '.$parent_plaintext_password,
                        'child_name' => (string) ' '.$user->first_name.' '.$user->last_name,
                        'child_grnumber' => (string) ' '.$user->email,
                        'child_password' => (string) ' '.$child_plaintext_password,
                        'class_name' => (string) $class_section_name,
                        'type' => 'application_accept',
                        'school_name' => (string) $school_name,
                        'school_email' => (string) $school_email,
                        'school_contact' => (string) $school_contact,
                        'android_app' => (string) $android_app,
                        'ios_app' => (string) $ios_app,
                        'url' => url('/'),
                    ];
                    $htmlcontent = EmailTemplateService::render($AcceptTemplate, $parent_data);

                    $emailPayload = [
                        'body' => $htmlcontent,
                        'email' => $parent_data['email'],
                        'subject' => $parent_data['subject'],
                        'type'    => $parent_data['type'],
                        'name'            => $parent_data['name'],
                        'child_name'      => $parent_data['child_name'],
                        'class_name'      => $parent_data['class_name'],
                        'school_name'     => $parent_data['school_name'],
                        'school_contact'  => $parent_data['school_contact'],
                        'school_email'    => $parent_data['school_email'],
                        'url'             => $parent_data['url'],
                        'username'        => $parent_data['username'],
                        'password'        => $parent_data['password'],
                        'child_grnumber'  => $parent_data['child_grnumber'],
                        'child_password'  => $parent_data['child_password']
                    ];
                    MailService::sendWithFallback('students.email', $emailPayload, function ($message) use ($emailPayload, $admin_mail, $school_name) {
                        $message->to($emailPayload['email'])->subject($emailPayload['subject']);
                        $message->from($admin_mail, $school_name);
                    });

                    // MailService::sendWithFallback('students.email', $parent_data, function ($message) use ($parent_data) {
                    //     $message->to($parent_data['email'])->subject($parent_data['subject']);
                    // });
                }

                $response = [
                    'error' => false,
                    'message' => trans('user_activate_successfully'),
                ];
            } else {
                $user->status = 2;
                $user->update();
                // dd($user);

                $rejectionTemplate = EmailTemplate::where('type', 'student_application_regect')->value('content');

                if (empty($rejectionTemplate)) {
                    \Log::warning('Skipping student rejection email: "student_application_regect" email template has no content.');
                }

                foreach ($parents as $parent) {
                    // $ad = Parents::with('user')->where('id', $parent->id)->get();
                    // dd($ad->toArray());
                    $parent->user->status = 2;
                    // dd($parent);
                    $parent->user->update();

                    // Template content is required to render the rejection email.
                    if (empty($rejectionTemplate)) {
                        continue;
                    }

                    $gaurdian_data = [
                        'subject' => 'Response To Online Registration',
                        'email' => (string) $parent->email,
                        'name' => (string) $parent->first_name.' '.$parent->last_name,
                        'child_name' => (string) ' '.$user->first_name.' '.$user->last_name,
                        'class_name' => (string) $class_name,
                        'type' => 'application_reject',
                        'school_name' => (string) $school_name,
                        'school_email' => (string) $school_email,
                        'school_contact' => (string) $school_contact,
                        'url' => url('/'),
                    ];
                    $htmlcontent = EmailTemplateService::render($rejectionTemplate, $gaurdian_data);

                    $emailPayload = [
                        'body' => $htmlcontent,
                        'email' => $gaurdian_data['email'],
                        'subject' => $gaurdian_data['subject'],
                        'type' => $gaurdian_data['type'],
                    ];

                    MailService::sendWithFallback('students.email', $emailPayload, function ($message) use ($emailPayload, $admin_mail, $school_name) {
                        $message->to($emailPayload['email'])->subject($emailPayload['subject']);
                        $message->from($admin_mail, $school_name);
                    });

                    // $data = [
                    //     'subject' => 'Response To Online Registration',
                    //     'email' => $parent->email,
                    //     'name' => $parent->first_name.' '.$parent->last_name,
                    //     'child_name' => ' '.$user->first_name.' '.$user->last_name,
                    //     'class_name' => $class_name,
                    //     'type' => 'application_reject',
                    //     'school_name' => $school_name,
                    //     'school_email' => $school_email,
                    //     'school_contact' => $school_contact,
                    // ];

                    // MailService::sendWithFallback('students.email', $data, function ($message) use ($data, $admin_mail, $school_name) {
                    //     $message->to($data['email'])->subject($data['subject']);
                    //     $message->from($admin_mail, $school_name);
                    // });
                }

                $response = [
                    'error' => false,
                    'message' => trans('email_sent_successfully'),
                ];
            }
            //     } catch (\Throwable $e) {
            //         // dd($e->getMessage());
            //         if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
            //             $response = [
            //                 'error' => false,
            //                 'message' => 'Message send successfully. But Email not sent.',
            //             ];
            //          } else {
            //             $response = [
            //                 'error' => true,
            //                 'message' => trans('error_occurred'),
            //                 'data' => $e,
            //             ];
            //         }
            //     }

            //     return response()->json($response);
        } catch (\Throwable $e) {
            // 1. Log the exact error to storage/logs/laravel.log so you can review it
            \Log::error('Registration Error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            // 2. Catch native Symfony/Laravel Mail exceptions directly
            if (
                $e instanceof \Symfony\Component\Mailer\Exception\ExceptionInterface ||
                Str::contains(strtolower($e->getMessage()), ['mail', 'failed', 'smtp', 'connection'])
            ) {

                $response = [
                    'error' => false, // Keep false if you want the UI to consider this a success
                    'message' => 'Status updated successfully, but email notification failed to send.',
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => trans('error_occurred'),
                    // Avoid sending raw $e to production UI for security reasons
                    'data' => config('app.debug') ? $e->getMessage() : null,
                ];
            }
        }

        return response()->json($response);
    }
}
