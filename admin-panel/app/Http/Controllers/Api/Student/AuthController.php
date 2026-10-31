<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Shift;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\ResponseService;
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

        $identity = app(StudentNumbers::class)->resolveLogin((string) $request->gr_number);
        if ($identity !== null && Auth::attempt(['id' => $identity->id, 'password' => $request->password])) {
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

            // A leaver (Action Plan 1.21) signs in, read-only, until leaver_until; their
            // row this year has no homeroom, so the answer names their last one.
            $leaver = (int) $studentSession->status === 0 && app(LeaverState::class)->until($user->student) !== null;
            $room = $studentSession->class_section ?? ($leaver ? ClassSection::query()->find($user->student->class_section_id) : null);
            if (((int) $studentSession->status === 0 && ! $leaver) || $room === null) {
                return ResponseService::errorResponse('Your Account is Deactivate Please contact Admin for Further Help.', null, null);
            } else {
                // Only now that the account and its session are active.
                $token = PortalSession::signIn($request, $auth, AppTokens::STUDENT);

                if ($request->fcm_id) {
                    $auth->fcm_id = $request->fcm_id;
                    $auth->save();
                }

                if ($request->device_type) {
                    $auth->device_type = $request->device_type;
                    $auth->save();
                }

                $classSectionName = $room->class->name.' '.$room->section->name;

                // Set Class Section name
                $streamName = $room->class->streams->name ?? null;
                if ($streamName !== null) {
                    $user->class_section_name = $classSectionName.' '.$streamName;
                } else {
                    $user->class_section_name = $classSectionName;
                }
                $user->is_term_on_in_class = $room->class->getIncludeTermsForSession($session_year_id);
                // Set Medium name
                $user->medium_name = $room->class->medium->name;

                // Set Shift name
                $user->shift_id = $room->class->shifts->id ?? '';
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
                $class_id = $room->class_id;

                $user->is_fee_payment_due = 0; // Retired v1 hint; statements are authoritative.

                $dynamicFields = null;
                $dynamicField = $user->student->dynamic_fields;
                $user = flattenMyModel($user);

                // Override class_id and class_section_id using student sessions
                $user['class_id'] = $class_id;
                $user['class_section_id'] = $room->id;

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
