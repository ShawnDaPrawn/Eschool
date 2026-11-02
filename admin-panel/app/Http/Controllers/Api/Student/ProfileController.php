<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\StudentSessions;
use App\Services\IdCardService;
use App\Services\People\Leavers\LeaverState;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Student app: the student's profile, their parents' details and the ID card.
 */
class ProfileController extends Controller
{
    use StudentAccess;

    public function getProfileDetails()
    {
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $user = Auth::user()->load(['student.class_section', 'student.category']);

            // Check weather student status is valid or leaved the school
            if ($user->status !== 1 || $user->student->status === 0) {
                return ResponseService::successResponse('Your Account is Deactivate Please contact Admin for Further Help.');
            }

            // if new session is not activated for student
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)->where('student_id', $user->student->id)->first();

            if (! $studentSession) {
                return ResponseService::successResponse('Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.');
            }

            // A leaver (Action Plan 1.21) has no homeroom this year: the answer names their last one.
            $room = $studentSession->class_section;
            if ($studentSession->status == 0) {
                $room = app(LeaverState::class)->until($user->student) !== null ? $user->student->class_section : null;
                if ($room === null) {
                    return ResponseService::successResponse('Your Account is Deactivate Please contact Admin for Further Help.', null);
                }
            }

            // Set Class Section name
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

            // Set School Shift name
            $user->shift_id = $room->class->shifts->id ?? '';
            $user->shift = Shift::find($user->shift_id);
            if ($user->shift) {
                $user->shift->id;
                $user->shift->title;
                $user->shift->start_time;
            }
            unset($user->student->class_section);

            // Set Category
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

            // Ensure proper data types for API response
            $data = array_merge($user, ['dynamic_fields' => $dynamicFields ?? null]);

            // Convert data types as per API requirements
            if (isset($data['student'])) {
                // Convert is_new_admission from bool to int
                if (isset($data['student']['is_new_admission'])) {
                    $data['student']['is_new_admission'] = (int) $data['student']['is_new_admission'];
                }

                // Convert height from double to string
                if (isset($data['student']['height'])) {
                    $data['student']['height'] = (string) $data['student']['height'];
                }

                // Convert weight from double to string
                if (isset($data['student']['weight'])) {
                    $data['student']['weight'] = (string) $data['student']['weight'];
                }
            }

            return ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getParentDetails(Request $request)
    {
        try {
            $student = $request->user()->student->load(['father', 'mother', 'guardian']);
            $parentDynamicFields = [];
            if ($student->father != null) {
                // father data
                $data = json_decode($student->father->dynamic_fields ?? '[]', true);
                $father = $student->father->toArray();

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
                $dynamic_fields = (array) $parentDynamicFields;
                $father = array_merge($father, ['dynamic_fields' => ! empty($dynamic_fields) ? $dynamic_fields : null]);
            }

            if ($student->mother != null) {
                // mother data
                $data = json_decode($student->mother->dynamic_fields ?? '[]', true);
                $mother = $student->mother->toArray();

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
                $dynamic_fields = (array) $parentDynamicFields;
                $mother = array_merge($mother, ['dynamic_fields' => ! empty($dynamic_fields) ? $dynamic_fields : null]);
            }

            if ($student->guardian != null) {
                // guardian data
                $data = json_decode($student->guardian->dynamic_fields ?? '[]', true);
                $guardian = $student->guardian->toArray();

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

                $dynamic_fields = (array) $parentDynamicFields;
                $guardian = array_merge($guardian, ['dynamic_fields' => ! empty($dynamic_fields) ? $dynamic_fields : null]);
            }

            if ($student->father != null && $student->mother != null && $student->guardian != null) {
                $data = [
                    'father' => (! empty($student->father)) ? $father : (object) [],
                    'mother' => (! empty($student->mother)) ? $mother : (object) [],
                    'guardian' => (! empty($student->guardian)) ? $guardian : (object) [],
                ];
            } elseif ($student->father != null && $student->mother != null) {
                $data = [
                    'father' => (! empty($student->father)) ? $father : (object) [],
                    'mother' => (! empty($student->mother)) ? $mother : (object) [],
                ];
            } else {
                $data = [
                    'guardian' => (! empty($student->guardian)) ? $guardian : (object) [],
                ];
            }

            return ResponseService::successResponse('Parent Details Fetched Successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getIdCardPdf(Request $request, IdCardService $idCardService)
    {
        try {
            $student = $request->user()->student;

            $output = $idCardService->buildPdf([$student->id])->output();

            return ResponseService::successResponse('Student ID Card PDF Fetched Successfully', null, ['pdf' => base64_encode($output)]);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
