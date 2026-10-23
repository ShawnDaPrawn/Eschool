<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ModuleRequest;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ModuleController extends Controller
{
    public function index(ModuleRequest $request, ModuleGate $gate): View
    {
        $modules = [];
        foreach (config('modules') as $key => $definition) {
            $modules[$key] = $definition + $gate->state($key);
        }

        return view('platform.modules', compact('modules'));
    }

    public function update(ModuleRequest $request, string $module, ModuleGate $gate): RedirectResponse
    {
        $gate->change($module, $request->boolean('enabled'), $request->user());

        return redirect()->route('school-modules.index')->with('success', __('Module availability updated.'));
    }
}
