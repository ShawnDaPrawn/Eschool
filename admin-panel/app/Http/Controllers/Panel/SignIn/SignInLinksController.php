<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\SignIn;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignIn\ProviderLinkStartRequest;
use App\Services\Platform\SignIn\ProviderCatalogue;
use App\Services\Platform\SignIn\ProviderLinks;
use App\Services\Platform\SignIn\ProviderSettings;
use App\Services\Platform\SignIn\ProviderSignIn;
use App\Services\Platform\SignIn\ProviderSignInRefused;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The signed-in person's own linked Google and Microsoft accounts at the current school (Action Plan 5.9). */
class SignInLinksController extends Controller
{
    public function __construct(
        private readonly ProviderLinks $links,
        private readonly ProviderSettings $settings,
        private readonly ProviderCatalogue $catalogue,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $links = $this->links->mine($user);
        $offered = [];
        foreach ($this->settings->offered() as $provider) {
            $setting = $this->settings->usable($provider['provider']);
            $offered[] = $provider + ['allowed' => $setting !== null && $this->settings->allowedRoles($setting, $user) !== []];
        }

        return view('sign-in.links', [
            'links' => array_map(fn (array $link) => $link + ['label' => $this->catalogue->label($link['provider'])], $links),
            'offered' => $offered,
            'linked' => array_column($links, 'provider'),
        ]);
    }

    public function start(ProviderLinkStartRequest $request, string $provider, ProviderSignIn $signIn): RedirectResponse
    {
        try {
            return redirect()->away($signIn->startWebLink($request, $request->user(), $provider, (string) $request->validated('password')));
        } catch (ProviderSignInRefused $refused) {
            return redirect()->route('sign-in-links.index')->withErrors(['provider' => __($refused->userMessage())]);
        }
    }

    public function destroy(Request $request, int $link): RedirectResponse
    {
        $this->links->unlink($request->user(), $link);

        return redirect()->route('sign-in-links.index')->with('success', __('The link is removed. That account can no longer sign you in here.'));
    }
}
