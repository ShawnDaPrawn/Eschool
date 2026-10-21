<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\FeesPaid;
use App\Models\InstallmentFee;
use App\Models\PaidInstallmentFee;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\IdCardService;
use App\Services\ResponseService;
use Carbon\Carbon;
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

            $compulsory_fees_mode = getSettings('compulsory_fee_payment_mode');
            $compulsory_fees_mode = $compulsory_fees_mode['compulsory_fee_payment_mode'] ?? 0;

             $session_year = SessionYear::where('id', $session_year_id)->first();
            
            $isInstallment = $session_year->include_fee_installments;

            $due_date = $session_year->fee_due_date;
            $free_app_use_date = $session_year->free_app_use_date;

            $current_date = Carbon::now()->toDateString();

            $user = Auth::user();
            $children = Students::where('father_id', $user->parent->id)->orWhere('mother_id', $user->parent->id)->orWhere('guardian_id', $user->parent->id)->with('class_section')
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

                if ($compulsory_fees_mode == 1) {
                    if (isset($free_app_use_date)) {
                        if ($current_date >= $free_app_use_date) {
                            $fees_paid = FeesPaid::where('student_id', $child->id)->where('session_year_id', $session_year_id)->first();
                            if ($isInstallment == 0) {
                                if ($fees_paid) {
                                    if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                        $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                    } else {
                                        $child->is_fee_payment_due = 0;
                                    }
                                } else {
                                    $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            } else {

                                if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                    $child->is_fee_payment_due = 0;
                                } else {
                                    // Installment case
                                    $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                    if ($installment_db->count()) {
                                        $installment_db_data = $installment_db->get();
                                        foreach ($installment_db_data as $data) {
                                            $paid_installment_data = PaidInstallmentFee::where(['student_id' => $child->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
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
                                    $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                                }
                            }
                        } else {
                            $child->is_fee_payment_due = 0;
                        }
                    } else {
                        $fees_paid = FeesPaid::where('student_id', $child->id)->where('session_year_id', $session_year_id)->first();

                        if ($isInstallment == 0) {
                            // Non-installment case
                            if (isset($fees_paid) && $fees_paid->is_fully_paid == 0) {
                                $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                            } else {
                                $child->is_fee_payment_due = 0;
                            }
                        } else {

                            if (isset($fees_paid) && $fees_paid->is_fully_paid == 1) {
                                $child->is_fee_payment_due = 0;
                            } else {
                                // Installment case
                                $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
                                if ($installment_db->count()) {
                                    $installment_db_data = $installment_db->get();
                                    foreach ($installment_db_data as $data) {
                                        $paid_installment_data = PaidInstallmentFee::where(['student_id' => $child->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
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
                                $child->is_fee_payment_due = ($current_date >= $due_date) ? 1 : 0;
                            }
                        }
                    }
                } else {
                    $child->is_fee_payment_due = 0;
                }

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
                    $child->is_semester_on_in_class = $studentSession->class_section->class->getIncludeSemestersForSession($session_year_id);
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
                    $child->is_semester_on_in_class = $child->class_section->class->getIncludeSemestersForSession($session_year_id);
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
