<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignIn\ProviderAppLinkRequest;
use App\Services\Platform\SignIn\ProviderCatalogue;
use App\Services\Platform\SignIn\ProviderLinks;
use App\Services\Platform\SignIn\ProviderSettings;
use App\Services\Platform\SignIn\ProviderSignIn;
use App\Services\Platform\SignIn\ProviderSignInRefused;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in person's own Google and Microsoft links at their current
 * school, from an app (Action Plan 5.9): list, link (with their password)
 * and remove. Only their own: another person's link is not found.
 */
class SignInLinkController extends Controller
{
    public function __construct(
        private readonly ProviderLinks $links,
        private readonly ProviderSettings $settings,
        private readonly ProviderCatalogue $catalogue,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $offered = [];
        foreach ($this->settings->offered() as $provider) {
            $setting = $this->settings->usable($provider['provider']);
            $offered[] = $provider + ['may_link' => $setting !== null && $this->settings->allowedRoles($setting, $user) !== []];
        }

        return ProviderSignInController::ok('Your linked accounts.', [
            'links' => array_map(fn (array $link) => $link + ['label' => $this->catalogue->label($link['provider'])], $this->links->mine($user)),
            'providers' => $offered,
        ]);
    }

    public function nonce(Request $request, string $provider, ProviderSignIn $signIn): JsonResponse
    {
        try {
            return ProviderSignInController::ok('Use this nonce once, for this link.', $signIn->appLinkNonce($request->user(), $provider));
        } catch (ProviderSignInRefused $refused) {
            return ProviderSignInController::refused($refused);
        }
    }

    public function store(ProviderAppLinkRequest $request, string $provider, ProviderSignIn $signIn): JsonResponse
    {
        try {
            $link = $signIn->appLink($request->user(), $provider, $request->validated());
        } catch (ProviderSignInRefused $refused) {
            return ProviderSignInController::refused($refused);
        }
        $mine = collect($this->links->mine($request->user()))->firstWhere('id', (int) $link->id);

        return ProviderSignInController::ok('Your account is linked. You can now sign in with it.', $mine + ['label' => $this->catalogue->label($link->provider)], 201);
    }

    public function destroy(Request $request, int $link): JsonResponse
    {
        $this->links->unlink($request->user(), $link);

        return ProviderSignInController::ok('The link is removed. That account can no longer sign you in here.', ['removed' => true]);
    }
}
