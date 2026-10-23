<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\School;

use App\Http\Controllers\Controller;
use App\Http\Resources\School\MySchoolResource;
use App\Services\ResponseService;
use App\Services\School\SchoolInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MySchoolController extends Controller
{
    public function show(Request $request, SchoolInformation $service, int $school): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', (new MySchoolResource($service->info($request->user(), $school)))->resolve($request));
    }
}
