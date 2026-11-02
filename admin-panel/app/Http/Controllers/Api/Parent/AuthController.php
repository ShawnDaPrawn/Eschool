<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\StudentSessions;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Parent app: sign-in.
 */
class AuthController extends Controller
{
    use ParentAccess;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];

        $identity = app(StudentNumbers::class)->resolveLogin((string) $request->email);
        if ($identity !== null && Auth::attempt(['id' => $identity->id, 'password' => $request->password])) {
            $auth = Auth::user();
            if (! $auth->hasRole('Parent')) {
                return ResponseService::errorResponse('Invalid Login Credentials', null, 101);
            }

            if ($request->fcm_id) {
                $auth->fcm_id = $request->fcm_id;
                $auth->save();
            }
            if ($request->device_type) {
                $auth->device_type = $request->device_type;
                $auth->save();
            }

            $user = $auth->load(['parent']);

            $children = $user->parent->children()->with('class_section')
                ->whereHas('studentSessions', function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                })->get();

            $parentDynamicFields = null;
            $dynamicField = $user->parent->dynamic_fields;

            if (! empty($dynamicField)) {
                $data = json_decode($dynamicField ?? '[]', true);
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if ($item != null) {
                            foreach ($item as $key => $value) {
                                $parentDynamicFields[$key] = $value;
                            }
                        }
                    }
                } else {
                    $parentDynamicFields = $data;
                }
            } else {
                $parentDynamicFields = null;
            }
            $user = flattenMyModel($user);

            $hasAnyValidSession = false;
            $hasAnyActiveStudent = false;

            foreach ($children as $child) {
                $child->first_name = $child->user->first_name;
                $child->last_name = $child->user->last_name;
                $child->image = $child->user->image;
                $child->dob = $child->user->dob;
                $child->current_address = $child->user->current_address;
                $child->permanent_address = $child->user->permanent_address;

                $studentSession = StudentSessions::where('session_year_id', $session_year_id)->where('student_id', $child->id)->first();

                // if new session is not activated for student
                if ($studentSession) {
                    $hasAnyValidSession = true;

                    // Active / studying check; a leaver reads until leaver_until (Action Plan 1.21).
                    if ($studentSession->status == 1 || app(LeaverState::class)->until($child) !== null) {
                        $hasAnyActiveStudent = true;
                    }

                }
                $child->is_fee_payment_due = 0; // Retired v1 hint; statements are authoritative.

                $dynamicFields = null;
                $dynamicField = $child->dynamic_fields;

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
                $child->dynamic_fields = ! empty($dynamicFields) ? $dynamicFields : null;

                unset($child->user);

                // A leaver's row this year has no homeroom: their last one is on the learner.
                if ($studentSession?->class_section) {
                    $classSectionName = $studentSession->class_section->class->name.' '.$studentSession->class_section->section->name;

                    $child->is_term_on_in_class = $studentSession->class_section->class->getIncludeTermsForSession($session_year_id);
                    // Set Stream name
                    $streamName = $studentSession->class_section->class->streams->name ?? null;
                    if ($streamName !== null) {
                        $child->class_section_name = $classSectionName.' '.$streamName;
                    } else {
                        $child->class_section_name = $classSectionName;
                    }

                    // Set Medium name
                    $child->medium_name = $studentSession->class_section->class->medium->name;

                    // Set Shift name
                    $child->shift_id = $studentSession->class_section->class->shifts->id ?? '';
                    $child->shift = Shift::find($child->shift_id);
                    if ($child->shift) {
                        $child->shift->id;
                        $child->shift->title;
                        $child->shift->start_time;
                    }

                    $child->class_id = $studentSession->class_section->class_id;
                    $child->class_section_id = $studentSession->class_section_id;
                } else {
                    // Fallback to old behavior if no student session (though it might fail the valid session check later)
                    $classSectionName = $child->class_section->class->name.' '.$child->class_section->section->name;

                    $child->is_term_on_in_class = $child->class_section->class->getIncludeTermsForSession($session_year_id);
                    // Set Stream name
                    $streamName = $child->class_section->class->streams->name ?? null;
                    if ($streamName !== null) {
                        $child->class_section_name = $classSectionName.' '.$streamName;
                    } else {
                        $child->class_section_name = $classSectionName;
                    }

                    // Set Medium name
                    $child->medium_name = $child->class_section->class->medium->name;

                    // Set Shift name
                    $child->shift_id = $child->class_section->class->shifts->id ?? '';
                    $child->shift = Shift::find($child->shift_id);
                    if ($child->shift) {
                        $child->shift->id;
                        $child->shift->title;
                        $child->shift->start_time;
                    }
                    $child->class_id = $child->class_section->class_id;
                    $child->class_section_id = $child->class_section->id;
                }

                unset($child->class_section);

                // Set Category
                $child->category_name = $child->category->name;
                unset($child->category);
            }

            // If no child has a valid session
            if (! $hasAnyValidSession) {
                return ResponseService::errorResponse(
                    'Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.',
                    null,
                    null
                );
            }

            // If all children are inactive
            if (! $hasAnyActiveStudent) {
                return ResponseService::errorResponse(
                    'Your account is deactivated. Please contact admin for further help.',
                    null,
                    null
                );
            }

            $data = array_merge($user, ['dynamic_fields' => $parentDynamicFields ?? null, 'children' => $children->toArray()]);

            // Only now that a child's session is active (no token or session for a refused sign-in).
            $token = PortalSession::signIn($request, $auth, AppTokens::PARENT);

            // Added for v1 clients: true when the account must choose a new password.
            return ResponseService::successResponse('User logged-in!', $data, [
                'token' => $token,
                'password_change_required' => DateOfBirthPasswords::changeRequiredAtSignIn($auth, (string) $request->password),
            ], 100);
        } else {
            return ResponseService::errorResponse('Invalid Login Credentials', null, 101);
        }
    }
}
