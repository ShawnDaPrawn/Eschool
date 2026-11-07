<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Passport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Passport\PassportPanelRequest;
use App\Services\Passport\LearnerPassport;
use App\Services\Passport\PassportAuthority;
use Illuminate\Http\Response;

/** Staff read a learner's passport: what this school holds and received (Action Plan 4.10). */
class PassportPanelController extends Controller
{
    public function index(PassportPanelRequest $request): Response
    {
        $search = (string) ($request->validated('q') ?? '');

        return response()->view('passport.index', ['search' => $search, 'learners' => app(LearnerPassport::class)->learners($request->user(), $search)]);
    }

    public function show(PassportPanelRequest $request, int $learner): Response
    {
        $access = app(PassportAuthority::class)->resolve($request->user(), $learner);
        abort_unless($access['viewer'] === 'staff', 403);

        return response()->view('passport.show', ['passport' => app(LearnerPassport::class)->build($access), 'admissionNo' => (string) $access['learner']->admission_no]);
    }
}
