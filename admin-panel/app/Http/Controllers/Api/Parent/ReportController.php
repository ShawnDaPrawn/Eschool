<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Services\AssignmentReportService;
use App\Services\ExamReportService;
use App\Services\FeesReportService;
use App\Services\LeaveReportService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: the statistics screens (fees, leave, assignments, exams).
 */
class ReportController extends Controller
{
    use ParentAccess;

    public function getFeesReport(Request $request, FeesReportService $feesReportService)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = $feesReportService->getStatistics((int) $this->resolveChild($request)->id, (int) $request->session_year_id);
            if (empty($data)) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }

            return ResponseService::successResponse('Data fetched successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function leaveReport(Request $request, LeaveReportService $leaveReportService)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'child_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = $leaveReportService->getStatistics((int) $this->resolveChild($request)->id, (int) $request->session_year_id);
            if (empty($data)) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }

            return ResponseService::successResponse('Data fetched successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function assignmentReport(Request $request, AssignmentReportService $assignmentReportService)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'subject_id' => 'nullable|integer',
            'child_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = $assignmentReportService->getStatistics(
                (int) $this->resolveChild($request)->id,
                (int) $request->session_year_id,
                $request->subject_id ? (int) $request->subject_id : null
            );
            if (empty($data)) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }

            return ResponseService::successResponse('Data fetched successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function offlineExamReport(Request $request, ExamReportService $examReportService)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = $examReportService->getOfflineStatistics((int) $this->resolveChild($request)->id, (int) $request->session_year_id);
            if (empty($data)) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }

            return ResponseService::successResponse('Data fetched successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function onlineExamReport(Request $request, ExamReportService $examReportService)
    {
        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer',
            'subject_id' => 'nullable|integer',
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = $examReportService->getOnlineStatistics(
                (int) $this->resolveChild($request)->id,
                (int) $request->session_year_id,
                $request->filled('subject_id') ? (int) $request->subject_id : null
            );
            if (empty($data)) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }

            return ResponseService::successResponse('Data fetched successfully.', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
