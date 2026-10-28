<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\TicketCataloguePanelRequest;
use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorProduct;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TicketCataloguePanelController extends Controller
{
    public function index(TicketCataloguePanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(TicketCatalogues::class)->finance($request->user());
            $events = TicketEvent::orderByDesc('id')->paginate(20);
            $event = $request->boolean('new_event') ? null : ($request->integer('event_id') ? TicketEvent::findOrFail($request->integer('event_id')) : TicketEvent::orderByDesc('id')->first());
            $type = $request->integer('type_id') ? TicketType::where('event_id', $event?->id)->findOrFail($request->integer('type_id')) : null;
            $types = TicketType::where('event_id', $event?->id)->orderByDesc('id')->paginate(30, ['*'], 'types_page');
            $counts = Ticket::whereIn('type_id', $types->pluck('id'))->selectRaw('type_id,COUNT(*) AS issued')->groupBy('type_id')->pluck('issued', 'type_id');
            $products = VendorProduct::where('tracks_stock', false)->where('status', 'active')->whereNotIn('id', TicketType::select('product_id'))->whereNotIn('id', VendorOrderLine::select('product_id'))->orderBy('name')->take(500)->get();
            if ($type !== null) {
                $products->push(VendorProduct::findOrFail($type->product_id));
            }
            $productOptions = $products->mapWithKeys(fn ($p) => [(int) $p->id => $p->name.' · '.$p->sku])->all();
            $readOnly = ! app(ModuleGate::class)->writable('ticketing') || ! app(ModuleGate::class)->writable('vendors');

            return response()->view('money.tickets.catalogue', compact('events', 'event', 'type', 'types', 'counts', 'productOptions', 'readOnly'));
        });
    }

    public function event(TicketCataloguePanelRequest $request): Response
    {
        $row = app(TicketCatalogues::class)->event($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('ticket-events.index', ['event_id' => $row->id])->with('success', __('Original event revision retained. Review ticket capacity and admission policy before sale.'));
    }

    public function type(TicketCataloguePanelRequest $request): Response
    {
        $row = app(TicketCatalogues::class)->type($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));

        return redirect()->route('ticket-events.index', ['event_id' => $row->event_id, 'type_id' => $row->id])->with('success', __('Original admission capacity and transfer rule retained.'));
    }
}
