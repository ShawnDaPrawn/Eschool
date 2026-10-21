<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin panel: the grade ranges (A, B, ... Fail) results are graded with.
 */
class GradeController extends Controller
{
    public function indexGrades()
    {
        if (! Auth::user()->can('grade-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
          $session_years = SessionYear::orderBy('id', 'ASC')->get();
        
         
          $grades = collect();

        return response(view('exams.exam-grade', compact('grades', 'session_years')));
    }

    public function getGradesBySessionYear($sessionYearId)
    {
        // Used by the grades page, which is gated on grade-create.
        if (! Auth::user()->can('grade-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        $grades = Grade::where('session_year_id', $sessionYearId)
            ->orderBy('starting_range')
            ->get([
                'id',
                'starting_range',
                'ending_range',
                'grade',
            ]);

        return response()->json([
            'success' => true,
            'grades' => $grades,
        ]);
    }

    public function createGrades(Request $request)
    {
        if (! Auth::user()->can('grade-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $customAttributes = [];
        foreach (array_keys($request->input('grade', [])) as $index) {
            $row = $index + 1;
            $customAttributes["grade.{$index}.starting_range"] = __('starting_range')." ({$row})";
            $customAttributes["grade.{$index}.ending_range"] = __('ending_range')." ({$row})";
            $customAttributes["grade.{$index}.grades"] = __('grade')." ({$row})";
        }

        $validator = Validator::make($request->all(), [
            'grade.*.starting_range' => 'required|numeric|between:0,100',
            'grade.*.ending_range' => 'required|numeric|between:0,100|gt:grade.*.starting_range',
            'grade.*.grades' => 'required',
            'session_year_id' => 'required|numeric',
        ], [
            'grade.*.starting_range.required' => __('validation_field_required'),
            'grade.*.starting_range.numeric' => __('validation_field_numeric'),
            'grade.*.starting_range.between' => __('validation_between_numeric'),
            'grade.*.ending_range.required' => __('validation_field_required'),
            'grade.*.ending_range.numeric' => __('validation_field_numeric'),
            'grade.*.ending_range.between' => __('validation_between_numeric'),
            'grade.*.ending_range.gt' => __('grade_ending_range_gt'),
            'grade.*.grades.required' => __('validation_field_required'),
        ], $customAttributes);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            foreach ($request->grade as $grade) {
                Grade::updateOrInsert(
                    ['id' => isset($grade['id']) ? $grade['id'] : null],
                    [
                        'starting_range' => $grade['starting_range'],
                        'ending_range' => $grade['ending_range'],
                        'grade' => $grade['grades'],
                        'session_year_id' => $request->session_year_id,
                    ]
                );
            }

            
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function destroyGrades($id)
    {
        if (! Auth::user()->can('grade-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $grade = Grade::find($id);
            $grade->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
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
