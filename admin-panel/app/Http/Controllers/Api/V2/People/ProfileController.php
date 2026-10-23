<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\ProfileRequest;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\People\Profiles\ProfileForms;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $response = $next($request);
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        });
    }

    public function show(ProfileRequest $request, ProfileForms $forms, ?int $person = null): JsonResponse
    {
        $subject = $person === null ? $request->user() : User::query()->findOrFail($person);
        $type = $request->validated('profile_type') ?? (app(ProfilePolicy::class)->types($subject)[0] ?? 'staff');
        Gate::authorize('profile.view', [$subject, $type]);

        return ResponseService::successResponse('data_fetch_successfully', $forms->schema($request->user(), $subject, $type));
    }

    public function update(ProfileRequest $request, ProfileForms $forms, ?int $person = null): JsonResponse
    {
        $subject = $person === null ? $request->user() : User::query()->findOrFail($person);
        $type = $request->validated('profile_type') ?? (app(ProfilePolicy::class)->types($subject)[0] ?? 'staff');
        Gate::authorize('profile.update', [$subject, $type]);

        $forms->save($request->user(), $subject, $type, $request->profileValues());

        // A write acknowledgement contains no decrypted fields for the
        // idempotency cache. Clients refresh through the authenticated GET.
        return ResponseService::successResponse('data_update_successfully');
    }
}
