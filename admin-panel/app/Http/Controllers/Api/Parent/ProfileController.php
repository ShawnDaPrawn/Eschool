<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\StudentSessions;
use App\Services\IdCardService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: the parent's profile with their children, and a child's ID card.
 */
class ProfileController extends Controller
{
    use ParentAccess;

    public function getProfileDetails()
    {
        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $user = Auth::user();
            $children = $user->parent->children()->with('class_section')
                ->whereHas('studentSessions', function ($query) use ($session_year_id) {
                    $query->where('session_year_id', $session_year_id);
                })->get();

            $parentDynamicField = null;
            $dynamicField = $user->parent->dynamic_fields;
            $user = flattenMyModel($user);

            $data1 = ! empty($dynamicField) ? json_decode($dynamicField, true) : null;
            if (is_array($data1)) {
                foreach ($data1 as $item) {
                    if (! empty($item)) {
                        foreach ($item as $key => $value) {
                            $parentDynamicFields[$key] = $value;
                        }
                    }
                }
            } else {
                $parentDynamicFields = $data1;
            }

            foreach ($children as $child) {
                $child->first_name = $child->user->first_name;
                $child->last_name = $child->user->last_name;
                $child->image = $child->user->image;
                $child->dob = $child->user->dob;
                $child->current_address = $child->user->current_address;
                $child->permanent_address = $child->user->permanent_address;

                $studentSession = StudentSessions::where('session_year_id', $session_year_id)->where('student_id', $child->id)->first();
                $class_id = $studentSession ? $studentSession->class_section->class_id : $child->class_section->class_id;

                $child->is_fee_payment_due = 0; // Retired v1 hint; statements are authoritative.

                $dynamicFields = null;
                $dynamicField = $child->dynamic_fields;

                $childdata = json_decode($dynamicField ?? '[]', true);

                if (is_array($childdata)) {
                    foreach ($childdata as $item) {
                        foreach ($item as $key => $value) {
                            $dynamicFields[$key] = $value;
                        }
                    }
                } else {
                    $dynamicFields = $childdata;
                }

                $child->dynamic_fields = ! empty($dynamicFields) ? $dynamicFields : null;

                unset($child->user);

                if ($studentSession) {
                    // Set Class Section name
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
                    // Set Class Section name
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
                // Set Category name
                $child->category_name = $child->category->name;
                unset($child->category);
            }
            // Ensure proper data types for API response
            $childrenArray = $children->toArray();

            // Convert data types for each child as per API requirements
            foreach ($childrenArray as &$child) {
                // Convert is_new_admission from bool to int
                if (isset($child['is_new_admission'])) {
                    $child['is_new_admission'] = (int) $child['is_new_admission'];
                }

                // Convert height from double to string
                if (isset($child['height'])) {
                    $child['height'] = (string) $child['height'];
                }

                // Convert weight from double to string
                if (isset($child['weight'])) {
                    $child['weight'] = (string) $child['weight'];
                }
            }

            $data = array_merge($user, ['dynamic_fields' => $parentDynamicFields ?? null, 'children' => $childrenArray]);

            return ResponseService::successResponse('Data Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getIdCardPdf(Request $request, IdCardService $idCardService)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $child = $this->resolveChild($request);

            $output = $idCardService->buildPdf([$child->id])->output();

            return ResponseService::successResponse('Student ID Card PDF Fetched Successfully', null, ['pdf' => base64_encode($output)]);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
