<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Teacher;
use App\Services\AcademicCalendarService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Teacher app: the academic calendar PDF.
 */
class CalendarController extends Controller
{
    use TeacherAccess;

    /**
     * Get academic calendar PDF combining holidays, events, and exams.
     */
    public function getAcademicCalendarPdf(AcademicCalendarService $service)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher) {
                throw new \Exception('Teacher record not found.');
            }

            $classIds = ClassSection::whereIn(
                'id',
                ClassTeacher::where('class_teacher_id', $teacher->id)->pluck('class_section_id')
            )
                ->pluck('class_id')
                ->toArray();

            if (empty($classIds)) {
                throw new \Exception('No classes found for this teacher.');
            }

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
