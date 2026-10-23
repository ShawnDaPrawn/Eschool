<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\ProfileFileRequest;
use App\Models\User;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class ProfileFileController extends Controller
{
    public function upload(ProfileFileRequest $request, ProfileFiles $files, string $field): JsonResponse
    {
        return $this->uploadSubject($request, $files, $request->user(), $field);
    }

    public function uploadPerson(ProfileFileRequest $request, ProfileFiles $files, int $person, string $field): JsonResponse
    {
        return $this->uploadSubject($request, $files, User::query()->findOrFail($person), $field);
    }

    private function uploadSubject(ProfileFileRequest $request, ProfileFiles $files, User $subject, string $field): JsonResponse
    {
        $files->upload($request->user(), $subject, $request->validated('profile_type'), $field, $request->file('document'));

        return ResponseService::successResponse('data_update_successfully');
    }
}
