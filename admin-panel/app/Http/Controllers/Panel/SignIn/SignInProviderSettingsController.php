<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\SignIn;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignIn\ProviderSettingsRequest;
use App\Services\Platform\SignIn\ProviderCatalogue;
use App\Services\Platform\SignIn\ProviderLinks;
use App\Services\Platform\SignIn\ProviderSettings;
use App\Services\Platform\SignIn\ProviderSignIn;
use App\Services\Platform\Stages\SchoolStages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The school's Google and Microsoft sign-in settings and links (Action Plan 5.9): the school's Super Admin only. */
class SignInProviderSettingsController extends Controller
{
    public function __construct(
        private readonly ProviderSettings $settings,
        private readonly ProviderLinks $links,
        private readonly ProviderCatalogue $catalogue,
    ) {}

    public function index(Request $request, ProviderSignIn $signIn): View
    {
        abort_unless($this->links->isSchoolAdmin($request->user()), 403);
        $providers = [];
        foreach (ProviderCatalogue::ALL as $provider) {
            $setting = $this->settings->for($provider);
            $providers[] = $this->settings->describe($setting) + [
                'label' => $this->catalogue->label($provider),
                'configured' => $this->catalogue->configured($provider),
                'redirect_uri' => $signIn->redirectUri($provider),
            ];
        }

        return view('sign-in.settings', [
            'providers' => $providers,
            'staff_roles' => $this->settings->staffRoles(),
            'stages' => array_intersect_key(SchoolStages::STAGES, array_flip(ProviderSettings::LEARNER_STAGES)),
            'links' => array_map(fn (array $link) => $link + ['label' => $this->catalogue->label($link['provider'])], $this->links->atSchool()),
        ]);
    }

    public function update(ProviderSettingsRequest $request, string $provider): RedirectResponse
    {
        abort_unless($this->catalogue->exists($provider), 404);
        $this->settings->update($request->user(), $provider, $request->choices());

        return redirect()->route('sign-in-providers.index', ['view' => $provider])->with('success', __(':provider sign-in settings are saved.', ['provider' => $this->catalogue->label($provider)]));
    }

    public function removeLink(Request $request, int $link): RedirectResponse
    {
        $this->links->removeForSchool($request->user(), $link);

        return redirect()->route('sign-in-providers.index', ['view' => 'links'])->with('success', __('The link is removed. That account can no longer sign this person in here.'));
    }
}
