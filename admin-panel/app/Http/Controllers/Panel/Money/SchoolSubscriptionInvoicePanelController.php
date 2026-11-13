<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\SchoolBusinessPanelRequest;
use App\Services\Money\SchoolSubscriptionInvoices;
use Symfony\Component\HttpFoundation\Response;

class SchoolSubscriptionInvoicePanelController extends Controller
{
    public function index(SchoolBusinessPanelRequest $request): Response
    {
        abort_if($request->hasAny(['school_id', 'school']), 422);
        $records = app(SchoolSubscriptionInvoices::class)->index($request->user());

        return response()->view('money.subscription-invoices.index', compact('records'));
    }

    public function show(SchoolBusinessPanelRequest $request, int $invoice): Response
    {
        abort_if($request->hasAny(['school_id', 'school']), 422);
        $record = app(SchoolSubscriptionInvoices::class)->show($request->user(), $invoice);

        return response()->view('money.subscription-invoices.show', compact('record'));
    }
}
