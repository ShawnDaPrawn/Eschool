<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\ProfileConfigurationPanelRequest;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Services\People\Profiles\ProfileDefinitions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileFieldController extends Controller
{
    public function index(ProfileConfigurationPanelRequest $request): View
    {
        $profile = $request->validated('profile_type', 'learner');
        $categories = ProfileFieldCategory::query()->where('profile_type', $profile)->with(['definitions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])->orderBy('sort_order')->orderBy('id')->get();
        $field = $request->filled('field') ? ProfileFieldDefinition::query()->where('profile_type', $profile)->findOrFail($request->integer('field')) : null;
        $category = $request->filled('category') ? ProfileFieldCategory::query()->where('profile_type', $profile)->findOrFail($request->integer('category')) : null;

        return view('people.profile-fields', compact('profile', 'categories', 'field', 'category'));
    }

    public function category(ProfileConfigurationPanelRequest $request, ProfileDefinitions $definitions, ?int $category = null): RedirectResponse
    {
        $saved = $definitions->category($request->user(), $request->validated(), $category);

        return redirect()->route('profile-configuration.index', ['profile_type' => $saved->profile_type])->with('success', __('Profile category saved.'));
    }

    public function field(ProfileConfigurationPanelRequest $request, ProfileDefinitions $definitions, ?int $definition = null): RedirectResponse
    {
        $saved = $definitions->field($request->user(), $request->validated(), $definition);

        return redirect()->route('profile-configuration.index', ['profile_type' => $saved->profile_type])->with('success', __('Profile field saved.'));
    }

    public function removeField(ProfileConfigurationPanelRequest $request, ProfileDefinitions $definitions, int $definition): RedirectResponse
    {
        $profile = ProfileFieldDefinition::query()->findOrFail($definition)->profile_type;
        $definitions->removeField($request->user(), $definition);

        return redirect()->route('profile-configuration.index', ['profile_type' => $profile])->with('success', __('Profile field removed.'));
    }

    public function removeCategory(ProfileConfigurationPanelRequest $request, ProfileDefinitions $definitions, int $category): RedirectResponse
    {
        $profile = ProfileFieldCategory::query()->findOrFail($category)->profile_type;
        $definitions->removeCategory($request->user(), $category);

        return redirect()->route('profile-configuration.index', ['profile_type' => $profile])->with('success', __('Profile category removed.'));
    }
}
