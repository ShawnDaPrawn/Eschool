<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTeacherActive;
use App\Models\Teacher;
use App\Services\IdCardService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use App\Services\Platform\Identity\PortalSession;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher app: sign-in, the teacher's profile and ID card.
 */
class AuthController extends Controller
{
    use TeacherAccess;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $auth = Auth::user();

            if (! $auth->hasRole('Teacher')) {
                return ResponseService::errorResponse('Invalid Login Credentials', null, 101);
            }
            // A deactivated teacher gets no token (EnsureTeacherActive refuses
            // the tokens they already had).
            if (! EnsureTeacherActive::isActive($auth)) {
                return ResponseService::errorResponse(EnsureTeacherActive::message(), null, null);
            }
            $token = PortalSession::signIn($request, $auth, AppTokens::TEACHER);
            $user = $auth->load(['teacher']);

            $dynamicFields = null;
            $dynamicField = $user->teacher->dynamic_fields;
            $user = flattenMyModel($user);
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

            $user = array_merge($user, ['dynamic_fields' => $dynamicFields]);

            if ($request->fcm_id) {
                $auth->fcm_id = $request->fcm_id;
                $auth->save();
            }
            if ($request->device_type) {
                $auth->device_type = $request->device_type;
                $auth->save();
            }

            // Added for v1 clients: true when the account must choose a new password.
            return ResponseService::successResponse('User logged-in!', $user, [
                'token' => $token,
                'password_change_required' => DateOfBirthPasswords::changeRequiredAtSignIn($auth, (string) $request->password),
            ], 100);
        } else {
            return ResponseService::errorResponse('Invalid Login Credentials', null, 101);
        }
    }

    public function getProfileDetails()
    {
        try {
            $user = Auth::user()->load(['teacher']);
            $dynamicFields = null;
            $dynamicField = $user->teacher->dynamic_fields;

            $user = flattenMyModel($user);

            $data = json_decode($dynamicField ?? '[]', true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (! empty($item)) {
                        foreach ($item as $key => $value) {
                            $dynamicFields[$key] = $value;
                        }
                    }
                }
            } else {
                $dynamicFields = $data;
            }

            $user = array_merge($user, ['dynamic_fields' => $dynamicFields ?? null]);

            return ResponseService::successResponse('Data Fetched Successfully', $user);
        } catch (\Exception $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getIdCardPdf(Request $request, IdCardService $idCardService)
    {
        try {
            $output = $idCardService->buildStaffPdf([$request->user()->id])->output();

            return ResponseService::successResponse('Teacher ID Card PDF Fetched Successfully', null, ['pdf' => base64_encode($output)]);
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
