<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\SchoolBusiness;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolBusiness\ReenrolmentPanelRequest;
use App\Models\SessionYear;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\ReenrolmentContracts;
use App\Services\SchoolBusiness\ReenrolmentPanelQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReenrolmentPanelController extends Controller
{
    private function context(): array
    {
        return ['canWrite' => app(ModuleGate::class)->writable('reenrolment') && ! app(SessionYearContext::class)->isReadOnly(),
            'account' => (int) auth()->id(), 'school' => app(CurrentSchool::class)->id()];
    }

    public function index(ReenrolmentPanelRequest $request): Response
    {
        $listing = app(ReenrolmentContracts::class)->index($request->user());

        return response()->view('reenrolment.index', ['listing' => $listing] + $this->context());
    }

    public function create(ReenrolmentPanelRequest $request): Response
    {
        $current = SessionYear::findOrFail((int) schoolSetting('session_year'));
        $years = SessionYear::where('start_date', '>', $current->getRawOriginal('end_date'))->orderBy('start_date')->get();
        $listing = app(ReenrolmentContracts::class)->index($request->user());

        return response()->view('reenrolment.create', compact('years', 'listing') + $this->context());
    }

    public function show(ReenrolmentPanelRequest $request, int $contract): Response
    {
        $detail = app(ReenrolmentPanelQueries::class)->detail($request->user(), $contract, (int) $request->validated('page', 1));
        $outstanding = app(ReenrolmentContracts::class)->outstanding($request->user(), $contract, (int) $request->validated('page', 1));
        $receipt = $request->session()->get('reenrolment_receipt');
        if (! is_array($receipt) || ($receipt['school'] ?? null) !== app(CurrentSchool::class)->id() || ($receipt['account'] ?? null) !== (int) $request->user()->id || ($receipt['contract'] ?? null) !== $contract) {
            $receipt = null;
        }

        return response()->view('reenrolment.show', compact('detail', 'outstanding', 'receipt') + $this->context());
    }

    public function write(ReenrolmentPanelRequest $request): JsonResponse
    {
        $service = app(ReenrolmentContracts::class);
        $input = $request->safe()->except(['expected_account_id', 'expected_school_id', 'confirm_publication', 'confirm_withdrawal', 'confirm_reminders']);
        $id = (int) $request->route('contract');
        $operation = $request->route('operation');
        $result = match ($operation) {
            'publish' => $service->publish($request->user(), $input),
            'withdraw' => $service->withdraw($request->user(), $id, $input),
            'remind' => $service->remind($request->user(), $id, $input),
            default => abort(404),
        };
        if ($operation === 'publish') {
            $id = $result['id'];
        }
        $request->session()->flash('reenrolment_receipt', ['school' => app(CurrentSchool::class)->id(), 'account' => (int) $request->user()->id, 'contract' => $id, 'operation' => $operation, 'result' => $result]);

        return response()->json(['error' => false, 'message' => __('Saved.'), 'data' => $result,
            'redirect' => route('reenrolment-panel.show', ['contract' => $id, 'view' => $operation === 'remind' ? 'outstanding' : 'overview'])]);
    }
}
