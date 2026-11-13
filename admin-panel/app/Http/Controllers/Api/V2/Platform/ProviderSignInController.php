<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignIn\ProviderAppSignInRequest;
use App\Http\Requests\SignIn\ProviderNonceRequest;
use App\Services\Platform\SignIn\ProviderSignIn;
use App\Services\Platform\SignIn\ProviderSignInRefused;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Google and Microsoft sign-in for the apps (Action Plan 5.9): which
 * providers a school offers, a one-use nonce for the native sign-in, and the
 * sign-in with its ID token. The token is the same app token a password
 * sign-in gives, bound to that school.
 */
class ProviderSignInController extends Controller
{
    public function __construct(private readonly ProviderSignIn $signIn) {}

    public function providers(Request $request): JsonResponse
    {
        $slug = $request->validate(['school' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9-]*$/']])['school'];
        $options = $this->signIn->loginOptions((string) $slug);

        return self::ok('Sign-in options.', ['providers' => $options['school'] === $slug ? $options['providers'] : []]);
    }

    public function nonce(ProviderNonceRequest $request, string $provider): JsonResponse
    {
        try {
            return self::ok('Use this nonce once, for this sign-in.', $this->signIn->appNonce($provider, (string) $request->validated('school'), (string) $request->validated('app')));
        } catch (ProviderSignInRefused $refused) {
            return self::refused($refused);
        }
    }

    public function signIn(ProviderAppSignInRequest $request, string $provider): JsonResponse
    {
        try {
            return self::ok('Signed in.', $this->signIn->appSignIn($request, $provider, $request->validated()));
        } catch (ProviderSignInRefused $refused) {
            return self::refused($refused);
        }
    }

    /** @param array<string, mixed> $data */
    public static function ok(string $message, array $data, int $status = 200): JsonResponse
    {
        return response()->json(['error' => false, 'message' => __($message), 'data' => $data, 'code' => null], $status)
            ->header('Cache-Control', 'no-store, private');
    }

    public static function refused(ProviderSignInRefused $refused): JsonResponse
    {
        $message = __($refused->userMessage());
        $data = $refused->status() === 422 ? ['errors' => ['password' => [$message]]] : null;

        return ApiV2ExceptionRenderer::envelope($refused->status(), $message, $data, ['Cache-Control' => 'no-store, private']);
    }
}
