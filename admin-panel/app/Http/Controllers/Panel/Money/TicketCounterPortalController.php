<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\TicketPortalRequest;
use App\Services\Commerce\TicketCounters;
use App\Services\Commerce\VendorOperatorAccess;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TicketCounterPortalController extends Controller
{
    public function handle(TicketPortalRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            $actor = $request->user();
            $vendor = (int) $request->route('vendor');
            $service = app(TicketCounters::class);
            $service->authority($actor, $vendor, $request->routeIs('vendor.tickets.redeem'));
            $profile = app(VendorOperatorAccess::class)->profile($actor);
            $ticket = null;
            $credentials = [];
            $outcome = null;
            if ($request->routeIs('vendor.tickets.lookup')) {
                $credentials = $request->safe()->only(['ticket_number', 'qr_token']);
                $ticket = $service->lookup($actor, $vendor, $credentials);
            }
            if ($request->routeIs('vendor.tickets.redeem')) {
                $outcome = $service->redeem($actor, $vendor, $request->safe()->except(['expected_school_id', 'expected_account_id']));
                $ticket = $outcome['receipt'];
            }

            return response()->view('vendor.tickets', compact('profile', 'vendor', 'ticket', 'credentials', 'outcome'));
        });
    }
}
