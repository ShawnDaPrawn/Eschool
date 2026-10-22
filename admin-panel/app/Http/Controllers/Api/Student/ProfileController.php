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
use App\Models\StudentSessions;
use App\Services\IdCardService;
use App\Services\ResponseService;
use Carbon\Carbon;
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

            $compulsory_fees_mode = getSettings('compulsory_fee_payment_mode');
            $compulsory_fees_mode = $compulsory_fees_mode['compulsory_fee_payment_mode'] ?? 0;

            $session_year = SessionYear::where('id', $session_year_id)->first();

            $isInstallment = $session_year->include_fee_installments;

            $due_date = $session_year->fee_due_date;
            $free_app_use_date = $session_year->free_app_use_date;

            $current_date = Carbon::now()->toDateString();

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

            if ($studentSession->status == 0) {
                return ResponseService::successResponse('Your Account is Deactivate Please contact Admin for Further Help.', null);
            }

            // Set Class Section name
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

            // Set School Shift name
            $user->shift_id = $studentSession->class_section->class->shifts->id ?? '';
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
