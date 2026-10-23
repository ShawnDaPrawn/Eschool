<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\People\Profiles\ProfileFiles;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Authenticated assets: separate from the JSON v2 API and public storage. */
class ProfileAssetController extends Controller
{
    public function show(Request $request, ProfileFiles $files, string $file): Response
    {
        return $this->showSubject($request, $files, $request->user(), $file);
    }

    public function showPerson(Request $request, ProfileFiles $files, int $person, string $file): Response
    {
        return $this->showSubject($request, $files, User::query()->findOrFail($person), $file);
    }

    private function showSubject(Request $request, ProfileFiles $files, User $subject, string $file): Response
    {
        $content = $files->read($request->user(), $subject, $file);

        return response($content['bytes'], 200, ['Content-Type' => $content['mime'],
            'Content-Disposition' => 'attachment; filename="'.$content['token'].'"; filename*=UTF-8\'\''.rawurlencode($content['name']),
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
    }
}
