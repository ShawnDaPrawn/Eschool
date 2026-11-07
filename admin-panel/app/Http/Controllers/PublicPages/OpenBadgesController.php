<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPages;

use App\Http\Controllers\Controller;
use App\Services\OpenStandards\Badges\BadgeVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public Open Badges endpoints (Action Plan 5.9): check a credential, read
 * its revocation status, and read the issuing school's profile. No session,
 * no sign-in; each answer says only what the credential already says.
 */
class OpenBadgesController extends Controller
{
    private const MAX_BYTES = 65536;

    public function __construct(private readonly BadgeVerifier $verifier) {}

    public function verify(Request $request): JsonResponse
    {
        $body = (string) $request->getContent();
        abort_if(strlen($body) > self::MAX_BYTES, 413);
        $input = json_decode($body, false, 32);
        $credential = is_object($input) && isset($input->credential) ? $input->credential : $input;
        $name = is_object($input) && is_string($input->recipient_name ?? null) ? mb_substr($input->recipient_name, 0, 200) : null;

        return $this->json($this->verifier->verify($credential, $name), 'no-store');
    }

    public function status(string $credential): JsonResponse
    {
        $status = $this->verifier->status($credential);
        abort_if($status === null, 404);

        return $this->json($status, 'public, max-age=60');
    }

    public function issuer(string $schoolSlug): JsonResponse
    {
        $profile = $this->verifier->issuer($schoolSlug);
        abort_if($profile === null, 404);

        return $this->json($profile, 'public, max-age=3600');
    }

    /** @param array<string, mixed> $data */
    private function json(array $data, string $cache): JsonResponse
    {
        return response()->json($data, 200, ['Cache-Control' => $cache, 'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
