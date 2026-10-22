<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\FeesPaid;
use App\Models\InstallmentFee;
use App\Models\PaidInstallmentFee;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: sign-in and password reset.
 */
class AuthController extends Controller
{
    use StudentAccess;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gr_number' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first(), null, 102);
        }

        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];

        $compulsory_fees_mode = getSettings('compulsory_fee_payment_mode');
        $compulsory_fees_mode = $compulsory_fees_mode['compulsory_fee_payment_mode'] ?? 0;

        $session_year = SessionYear::where('id', $session_year_id)->first();

        $isInstallment = $session_year->include_fee_installments;

        $due_date = $session_year->fee_due_date;
        $free_app_use_date = $session_year->free_app_use_date;

        $current_date = Carbon::now()->toDateString();

        if (Auth::attempt(['email' => $request->gr_number, 'password' => $request->password])) {
            //        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Here Email Field is referenced as a GR Number for Student
            $auth = Auth::user();
            if (! $auth->hasRole('Student') || ! $auth->student) {
                return ResponseService::errorResponse('Invalid Login Credentials', null, 101);
            }
            if ((int) $auth->status !== 1) {
                return ResponseService::errorResponse('Your Account is Deactivate Please contact Admin for Further Help.', null, null);
            }
            $user = $auth->load(['student.class_section', 'student.category']);

            // if new session is not activated for student
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)->where('student_id', $user->student->id)->first();

            if (! $studentSession) {
                return ResponseService::errorResponse('Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.', null, null);
            }

            if ($studentSession->status == 0) {
                return ResponseService::errorResponse('Your Account is Deactivate Please contact Admin for Further Help.', null, null);
            } else {
                // Only now that the account and its session are active.
                $token = AppTokens::issue($auth, AppTokens::STUDENT);

                if ($request->fcm_id) {
                    $auth->fcm_id = $request->fcm_id;
                    $auth->save();
                }

                if ($request->device_type) {
                    $auth->device_type = $request->device_type;
                    $auth->save();
                }

                $classSectionName = $studentSession->class_section->class->name.' '.$studentSession->class_section->section->name;

                // Set Class Section name
                $streamName = $studentSession->class_section->class->streams->name ?? null;
                if ($streamName !== null) {
                    $user->class_section_name = $classSectionName.' '.$streamName;
                } else {
                    $user->class_section_name = $classSectionName;
                }
                $user->is_semester_on_in_class = $studentSession->class_section->class->getIncludeSemestersForSession($session_year_id);
                // Set Medium name
                $user->medium_name = $studentSession->class_section->class->medium->name;

                // Set Shift name
                $user->shift_id = $studentSession->class_section->class->shifts->id ?? '';
                $user->shift = Shift::find($user->shift_id);
                if ($user->shift) {
                    $user->shift->id;
                    $user->shift->title;
                    $user->shift->start_time;
                }

                // $user->dynamic_field = $dynamicFields;
                unset($user->student->class_section);

                // Set Category name
                $user->category_name = $user->student->category->name;
                unset($user->student->category);
                $class_id = $studentSession->class_section->class_id;

                if ($compulsory_fees_mode == 1) {
                    if (isset($free_app_use_date)) {
                        if ($current_date >= $free_app_use_date) {
                            $fees_paid = FeesPaid::where('student_id', $user->student->id)->where('session_year_id', $session_year_id)->first();

                            if ($isInstallment == 0) {
                                if ($fees_paid) {
                                    if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                        $user->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                    } else {
                                        $user->is_fee_payment_due = 0;
                                    }
                                } else {
                                    $user->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            } else {

                                if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                    $user->is_fee_payment_due = 0;
                                } else {
                                    // Installment case
                                    $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                    if ($installment_db->count()) {
                                        $installment_db_data = $installment_db->get();
                                        foreach ($installment_db_data as $data) {
                                            $paid_installment_data = PaidInstallmentFee::where(['student_id' => $user->student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                                            $installment_data[] = [
                                                'id' => $data->id,
                                                'name' => $data->name,
                                                'due_date' => date('Y-m-d', strtotime($data->due_date)),
                                                'due_charges' => $data->due_charges,
                                                'is_paid' => $paid_installment_data->status ?? 0,
                                            ];
                                        }
                                    }
                                    // Find the first unpaid installment and set its due date
                                    foreach ($installment_data as $data) {
                                        if ($data['is_paid'] == 0) {
                                            $due_date = $data['due_date'];
                                            break; // Stop after the first unpaid installment
                                        }
                                    }
                                    $user->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            }
                            // $user->is_fee_payment_due = 1;
                        } else {

                            $user->is_fee_payment_due = 0;
                        }
                    } else {
                        $fees_paid = FeesPaid::where('student_id', $user->student->id)->where('session_year_id', $session_year_id)->first();

                        if ($isInstallment == 0) {
                            // Non-installment case
                            if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                $user->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                            } else {
                                $user->is_fee_payment_due = 0;
                            }
                        } else {

                            if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                $user->is_fee_payment_due = 0;
                            } else {
                                // Installment case
                                $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                if ($installment_db->count()) {
                                    $installment_db_data = $installment_db->get();
                                    foreach ($installment_db_data as $data) {
                                        $paid_installment_data = PaidInstallmentFee::where(['student_id' => $user->student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                                        $installment_data[] = [
                                            'id' => $data->id,
                                            'name' => $data->name,
                                            'due_date' => date('Y-m-d', strtotime($data->due_date)),
                                            'due_charges' => $data->due_charges,
                                            'is_paid' => $paid_installment_data->status ?? 0,
                                        ];
                                    }
                                }
                                // Find the first unpaid installment and set its due date
                                foreach ($installment_data as $data) {
                                    if ($data['is_paid'] == 0) {
                                        $due_date = $data['due_date'];
                                        break; // Stop after the first unpaid installment
                                    }
                                }
                                $user->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                            }
                        }
                    }
                } else {
                    $user->is_fee_payment_due = 0;
                }

                $dynamicFields = null;
                $dynamicField = $user->student->dynamic_fields;
                $user = flattenMyModel($user);

                // Override class_id and class_section_id using student sessions
                $user['class_id'] = $class_id;
                $user['class_section_id'] = $studentSession->class_section_id;

                if (! empty($dynamicField)) {
                    $data = json_decode($dynamicField ?? '[]', true);
                    if (is_array($data)) {
                        foreach ($data as $item) {
                            if ($item != null) {
                                foreach ($item as $key => $value) {
                                    $dynamicFields[$key] = $value;
                                }
                            }
                        }
                    } else {
                        $dynamicFields = $data;
                    }
                } else {
                    $dynamicFields = null;
                }

                $data = array_merge($user, ['dynamic_fields' => $dynamicFields]);

                // Added for v1 clients: true when the learner must choose a new password
                // (it is still their date of birth). Older apps ignore it.
                return ResponseService::successResponse('User logged-in!', $data, [
                    'token' => $token,
                    'password_change_required' => DateOfBirthPasswords::changeRequiredAtSignIn($auth, (string) $request->password),
                ]);
            }
        } else {
            return ResponseService::errorResponse('Invalid Login Credentials', null, 101);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gr_no' => 'required',
            'dob' => 'required|date',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $get_id = Students::select('user_id')->where('admission_no', $request->gr_no)->pluck('user_id')->first();
            if (isset($get_id) && ! empty($get_id)) {

                $user = User::where('id', $get_id)->whereDate('dob', '=', date('Y-m-d', strtotime($request->dob)))->first();
                if ($user) {
                    $user->reset_request = 1;
                    $user->save();
                }
            }

            // Same answer whether or not the details matched a student, so the
            // endpoint can't be used to find out GR numbers and birth dates.
            return ResponseService::successResponse('Request Send Successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
