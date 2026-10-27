<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeTaxPanelRequest;
use App\Models\Money\FeeTaxSetting;
use App\Services\Money\FeeTaxSettings;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeTaxPanelController extends Controller
{
    public function index(FeeTaxPanelRequest $request): Response
    {
        $setting = FeeTaxSetting::first();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.items.tax-settings', compact('setting', 'canWrite'));
    }

    public function save(FeeTaxPanelRequest $request): Response
    {
        app(FeeTaxSettings::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-items.tax', [], 303)->with('success', __('School VAT details saved.'));
    }
}
