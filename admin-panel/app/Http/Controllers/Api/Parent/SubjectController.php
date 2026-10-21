<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's subjects.
 */
class SubjectController extends Controller
{
    use ParentAccess;

    public function subjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $children = $this->resolveChild($request);
            $subjects = $children->subjects();

            return ResponseService::successResponse('Student Subject Fetched Successfully.', $subjects, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function classSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $children = $this->resolveChild($request);
            $subjects = $children->classSubjects();

            return ResponseService::successResponse('Class Subject Fetched Successfully.', $subjects, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
