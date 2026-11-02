<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Pathways;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pathways\CareerGuidanceRequest;
use App\Services\Pathways\CareerGuidance;
use App\Services\Pathways\CareerProfiles;
use Illuminate\Http\Response;

class CareerGuidancePanelController extends Controller
{
    public function show(CareerGuidanceRequest $request): Response
    {
        $data = $request->validated();
        $guidance = app(CareerGuidance::class);
        $search = (string) ($data['q'] ?? '');
        $learners = $guidance->learners($request->user(), $search);
        $learner = isset($data['learner_id']) ? $guidance->learner($request->user(), (int) $data['learner_id']) : null;
        $view = $learner !== null && isset($data['profile_id']) ? $guidance->view($request->user(), (int) $learner->id, (string) $data['profile_id']) : null;

        return response()->view('pathways.guidance', ['search' => $search, 'learners' => $learners, 'profiles' => app(CareerProfiles::class)->choices($request->user()),
            'learner' => $learner === null ? null : ['id' => (int) $learner->id, 'name' => (string) $learner->user?->full_name],
            'profileId' => $data['profile_id'] ?? null, 'view' => $view]);
    }
}
