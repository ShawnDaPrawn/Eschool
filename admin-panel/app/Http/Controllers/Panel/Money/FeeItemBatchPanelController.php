<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeItemBatchPanelRequest;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemBatch;
use App\Models\SessionYear;
use App\Services\Money\FeeItemBatches;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeItemBatchPanelController extends Controller
{
    public function index(FeeItemBatchPanelRequest $request): Response
    {
        $batches = FeeItemBatch::orderByDesc('id')->paginate(20);

        return response()->view('money.item-batches.index', compact('batches'));
    }

    public function form(FeeItemBatchPanelRequest $request): Response
    {
        $items = FeeItem::where('state', 'active')->orderBy('name')->get();
        $selected = $request->has('fee_item_id') ? FeeItem::findOrFail($request->integer('fee_item_id')) : null;
        $years = SessionYear::where('default', 1)->orderByDesc('id')->get();
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.item-batches.form', compact('items', 'selected', 'years', 'canWrite'));
    }

    public function review(FeeItemBatchPanelRequest $request): Response
    {
        $input = $request->validated();
        $preview = app(FeeItemBatches::class)->preview($request->user(), $input);
        $canWrite = app(ModuleGate::class)->writable('fees');

        return response()->view('money.item-batches.review', compact('input', 'preview', 'canWrite'));
    }

    public function post(FeeItemBatchPanelRequest $request): Response
    {
        $batch = app(FeeItemBatches::class)->post($request->user(), $request->validated());

        return redirect()->route('fee-item-batches.show', ['batch' => $batch->id], 303)->with('success', __('Automatic item batch posted once.'));
    }

    public function show(FeeItemBatchPanelRequest $request): Response
    {
        $batch = FeeItemBatch::findOrFail((int) $request->route('batch'));
        $preview = $batch->snapshot;

        return response()->view('money.item-batches.show', compact('batch', 'preview'));
    }
}
