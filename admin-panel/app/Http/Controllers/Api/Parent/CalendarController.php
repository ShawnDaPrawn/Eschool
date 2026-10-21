<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\StudentSessions;
use App\Services\AcademicCalendarService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Throwable;

/**
 * Parent app: the academic calendar PDF.
 */
class CalendarController extends Controller
{
    use ParentAccess;

    /**
     * Get academic calendar PDF combining holidays, events, and exams.
     */
    public function getAcademicCalendarPdf(Request $request, AcademicCalendarService $service)
    {
        try {
            $student = $this->resolveChild($request);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $classIds = [StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)
                ->with('class_section')
                ->first()->class_section->class_id];

            $pdf = $service->generate($classIds);

            return ResponseService::successResponse(
                'Academic Calendar PDF fetched successfully',
                null,
                ['pdf' => $pdf]
            );
        } catch (Throwable $e) {
            return ResponseService::errorResponse(
                'Error occurred while generating academic calendar PDF',
                null,
                103,
                $e
            );
        }
    }
}
