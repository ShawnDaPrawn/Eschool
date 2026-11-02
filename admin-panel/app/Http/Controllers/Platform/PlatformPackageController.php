<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\PackageRequest;
use App\Services\Platform\Saas\PlatformPackages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: the package and price editor (Action Plan SA.7). */
class PlatformPackageController extends Controller
{
    public function __construct(private readonly PlatformPackages $packages) {}

    public function store(PackageRequest $request): RedirectResponse
    {
        $id = $this->packages->create($request->validated(), $request->user());

        return redirect()->route('saas.packages.show', $id)->with('success', __('Draft package created.'));
    }

    public function show(Request $request, int $package): View
    {
        return view('saas.package', $this->packages->show($package, $request->query('on') === null ? null : (string) $request->query('on')));
    }

    public function update(PackageRequest $request, int $package): RedirectResponse
    {
        $this->packages->update($package, $request->validated(), $request->user());

        return redirect()->route('saas.packages.show', $package)->with('success', __('Package saved.'));
    }

    public function modules(PackageRequest $request, int $package): RedirectResponse
    {
        $this->packages->setModules($package, $request->validated(), $request->user());

        return redirect()->route('saas.packages.show', $package)->with('success', __('Modules saved.'));
    }

    public function status(PackageRequest $request, int $package): RedirectResponse
    {
        $this->packages->changeStatus($package, $request->validated(), $request->user());

        return redirect()->route('saas.packages.show', $package)->with('success', $request->validated('status') === 'active' ? __('Package activated.') : __('Package retired.'));
    }

    public function price(PackageRequest $request, int $package): RedirectResponse
    {
        $this->packages->addPrice($package, $request->validated(), $request->user());

        return redirect()->route('saas.packages.show', $package)->with('success', __('Price added.'));
    }

    public function withdraw(PackageRequest $request, int $package, int $price): RedirectResponse
    {
        $this->packages->withdrawPrice($package, $price, $request->user());

        return redirect()->route('saas.packages.show', $package)->with('success', __('Price withdrawn.'));
    }

    public function reapply(PackageRequest $request, int $package): RedirectResponse
    {
        $count = $this->packages->reapply($package, $request->validated(), $request->user());

        return redirect()->route('saas.packages.show', $package)->with('success', trans_choice('{0} No school needed the current modules.|{1} The current modules now apply to one school.|[2,*] The current modules now apply to :count schools.', $count));
    }
}
