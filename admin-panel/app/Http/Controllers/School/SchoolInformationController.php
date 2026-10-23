<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolInformationRequest;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use App\Services\School\SchoolInformation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SchoolInformationController extends Controller
{
    public function index(SchoolInformationRequest $request, SchoolInformation $service): View
    {
        $info = $service->info($request->user(), app(CurrentSchool::class)->id());
        $settings = $service->settings();

        return view('school.information', compact('info', 'settings'));
    }

    public function update(SchoolInformationRequest $request, SchoolInformation $service): JsonResponse
    {
        $service->update($request->user(), $request->validated(), $request->file('crest'));

        return ResponseService::successResponse('data_update_successfully');
    }

    public function publish(SchoolInformationRequest $request, SchoolInformation $service): RedirectResponse
    {
        $service->publish($request->user(), $request->validated());

        return redirect()->route('school-information.index')->with('success', __('School document published.'));
    }

    public function unpublish(SchoolInformationRequest $request, SchoolInformation $service, string $document): RedirectResponse
    {
        $service->unpublish($request->user(), $document);

        return redirect()->route('school-information.index')->with('success', __('School document unlisted.'));
    }
}
