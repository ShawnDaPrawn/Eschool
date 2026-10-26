<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** Explicit personas cannot borrow the other purpose of a known app credential. */
class GroupChatPurpose
{
    public function authorize(Request $request, string $persona): void
    {
        $token = $request->user()->currentAccessToken();
        $profile = $token instanceof PersonalAccessToken ? $token->name
            : (PortalSession::isPortal($request) ? $request->session()->get('portal_app') : null);
        $expected = match ($profile) {
            AppTokens::STUDENT => 'student', AppTokens::PARENT => 'parent',
            AppTokens::TEACHER, AppTokens::FACILITATOR => 'staff', default => null,
        };
        abort_if($expected !== null && $expected !== $persona, 403);
    }
}
