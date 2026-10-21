<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\AcademicCalendarService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Student app: the academic calendar PDF.
 */
class CalendarController extends Controller
{
    use StudentAccess;

    /**
     * Get academic calendar PDF combining holidays, events, and exams.
     */
    public function getAcademicCalendarPdf(AcademicCalendarService $service)
    {
        try {
            $student = Students::where('user_id', Auth::id())
                ->with('class_section')
                ->firstOrFail();

            $session_year_id = getSettings('session_year')['session_year'];

            if (! $student->class_section) {
                throw new \Exception('Class section not found for this student.');
            }

            $classIds = [StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id];

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
