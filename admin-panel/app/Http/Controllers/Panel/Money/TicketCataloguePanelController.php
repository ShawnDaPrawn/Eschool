<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\TicketCataloguePanelRequest;
use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketCatalogueRevision;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorProduct;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TicketCataloguePanelController extends Controller
{
    public function index(TicketCataloguePanelRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(TicketCatalogues::class)->finance($request->user());
            $events = TicketEvent::orderByDesc('id')->paginate(20);
            $event = $request->integer('event_id') ? TicketEvent::findOrFail($request->integer('event_id')) : null;
            $type = $request->integer('type_id') ? TicketType::where('event_id', $event?->id)->findOrFail($request->integer('type_id')) : null;
            $types = TicketType::where('event_id', $event?->id)->orderByDesc('id')->paginate(30, ['*'], 'types_page');
            $counts = Ticket::whereIn('type_id', $types->pluck('id'))->selectRaw('type_id,COUNT(*) AS issued')->groupBy('type_id')->pluck('issued', 'type_id');
            $products = VendorProduct::where('tracks_stock', false)->where('status', 'active')->whereNotIn('id', TicketType::select('product_id'))->whereNotIn('id', VendorOrderLine::select('product_id'))->orderBy('name')->take(500)->get();
            if ($type !== null) {
                $products->push(VendorProduct::findOrFail($type->product_id));
            }
            $productOptions = $products->mapWithKeys(fn ($p) => [(int) $p->id => $p->name.' · '.$p->sku])->all();
            $section = (string) $request->input('section', $request->boolean('new_event') ? 'event' : ($type ? 'type-details' : ($event ? 'event-details' : 'events')));
            abort_unless(in_array($section, ['events', 'event-details', 'event', 'type-details', 'type', 'cancel'], true), 404);
            abort_if(in_array($section, ['event-details', 'type-details', 'type', 'cancel'], true) && $event === null, 404);
            abort_if($section === 'type-details' && $type === null, 404);
            $issued = $type ? Ticket::where('type_id', $type->id)->count() : 0;
            $revisions = ($type || $event) ? TicketCatalogueRevision::where('resource_type', $type ? 'type' : 'event')->where('record_id', $type->id ?? $event?->id)->orderByDesc('revision')->paginate(20, ['*'], 'revisions_page') : null;
            $draft = $request->attributes->get('ticketDraft', []);
            $validationErrors = $request->attributes->get('ticketErrors');
            $readOnly = ! app(ModuleGate::class)->writable('ticketing') || ! app(ModuleGate::class)->writable('vendors');

            return response()->view('money.tickets.catalogue', compact('events', 'event', 'type', 'types', 'counts', 'productOptions', 'readOnly', 'section', 'issued', 'revisions', 'draft', 'validationErrors'));
        });
    }

    public function event(TicketCataloguePanelRequest $request): Response
    {
        try {
            $row = app(TicketCatalogues::class)->event($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));
        } catch (HttpExceptionInterface $error) {
            return $this->refusal($request, $error);
        }

        return redirect()->route('ticket-events.index', ['event_id' => $row->id])->with('success', __('Original event revision retained. Review ticket capacity and admission policy before sale.'));
    }

    public function type(TicketCataloguePanelRequest $request): Response
    {
        try {
            $row = app(TicketCatalogues::class)->type($request->user(), $request->safe()->except(['expected_school_id', 'expected_account_id']));
        } catch (HttpExceptionInterface $error) {
            return $this->refusal($request, $error);
        }

        return redirect()->route('ticket-events.index', ['event_id' => $row->event_id, 'type_id' => $row->id])->with('success', __('Original admission capacity and transfer rule retained.'));
    }

    private function refusal(TicketCataloguePanelRequest $request, HttpExceptionInterface $error): Response
    {
        if ($error->getStatusCode() === 422) {
            return $request->formFailure([$error->getMessage()]);
        }
        if ($error->getStatusCode() !== 409 || ! $request->integer('id')) {
            throw $error;
        }
        $type = $request->routeIs('ticket-events.type') ? TicketType::findOrFail($request->integer('id')) : null;
        $event = TicketEvent::findOrFail($type->event_id ?? $request->integer('id'));
        $saved = ($type ?? $event)->attributesToArray();
        if ($type === null) {
            foreach (['sales_from', 'sales_until', 'starts_at', 'ends_at'] as $field) {
                $saved[$field] = $event->getAttribute($field)->setTimezone(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->format('Y-m-d\TH:i');
            }
        }
        $attempt = $request->safe()->except(['expected_school_id', 'expected_account_id', 'private_reason', 'operation_id']);
        $returnUrl = route('ticket-events.index', ['event_id' => $event->id, 'type_id' => $type?->id]);
        $fields = $type ? ['revision' => 'Revision', 'capacity' => 'Explicit admission capacity', 'status' => 'Status', 'transfer_rule' => 'School ticket transfer rule'] : ['revision' => 'Revision', 'name' => 'Event name', 'status' => 'Status', 'sales_from' => 'Ticket sales start', 'sales_until' => 'Ticket sales end', 'starts_at' => 'Admission opens', 'ends_at' => 'Admission closes'];

        return response()->view('money.tickets.conflict', compact('saved', 'attempt', 'fields', 'returnUrl'), 409);
    }
}
