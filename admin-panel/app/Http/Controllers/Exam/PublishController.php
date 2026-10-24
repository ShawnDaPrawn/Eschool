<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Services\Academics\LegacyMarks;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Admin panel: publish an exam's results.
 */
class PublishController extends Controller
{
    public function publishExamResult($id)
    {
        // The publish button is on the exam list, which is gated on exam-create.
        if (! Auth::user()->can('exam-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        try {
            app(LegacyMarks::class)->publish(Auth::user(), (int) $id);
            $response = ['error' => false, 'message' => trans('data_store_successfully')];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    // public function uploadMarks()
    // {
    //     if (! Auth::user()->can('exam-upload-marks')) {
    //         $response = [
    //             'message' => trans('no_permission_message'),
    //         ];

    //         return redirect(route('home'))->withErrors($response);
    //     }

    //     $session_year_id = getSettings('session_year')['session_year'];
    //     $currentTermId = Term::where('session_year_id', $session_year_id)->get()->first(function ($term) {
    //         return $term->current;
    //     })->id;

    //     $teacher_id = Auth::user()->teacher->id;

    //     $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)
    //         ->where('session_year_id', $session_year_id)
    //         ->where(function ($query) use ($currentTermId) {

    //             $query->whereNull('term_id');

    //             if ($currentTermId) {
    //                 $query->orWhere('term_id', $currentTermId);
    //             }
    //         })
    //         ->pluck('class_section_id');

    //     $class_ids = ClassSection::whereIn('id', $class_section_id)
    //         ->pluck('class_id');

    //     $classes = ClassSection::with(
    //         'class',
    //         'section',
    //         'class.medium',
    //         'streams'
    //     )
    //         ->whereIn('id', $class_section_id)
    //         ->whereIn('class_id', $class_ids)
    //         ->get();

    //     return response(view('exams.upload-marks', compact('classes')));
    // }

    public function getPublishExam(Request $request, $class_section_id)
    {

        try {
            $class_id = ClassSection::where('id', $class_section_id)->value('class_id');
            $term_id = $request->query('term_id');

            $exam_data = ExamClass::with('exam')
                ->where('class_id', $class_id)
                ->whereHas('exam', function ($query) use ($term_id) {
                    $query->where('publish', 1)
                        ->when($term_id, function ($q) use ($term_id) {
                            $q->where('term_id', $term_id);
                        });
                })->get();

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exam_data,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
