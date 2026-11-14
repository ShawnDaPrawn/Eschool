<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\TicketPortalRequest;
use App\Services\Commerce\TicketCounters;
use App\Services\Commerce\VendorOperatorAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            $operation = (string) $request->input('operation_id', '');
            $failure = null;
            if ($request->routeIs('vendor.tickets.lookup')) {
                $credentials = $request->safe()->only(['ticket_number', 'qr_token']);
                try {
                    $ticket = $service->lookupForPanel($actor, $vendor, $credentials);
                } catch (ModelNotFoundException|HttpExceptionInterface $error) {
                    if ($error instanceof HttpExceptionInterface && $error->getStatusCode() !== 404) {
                        throw $error;
                    }
                    $failure = __('This ticket is not available to this counter. Check its current number or QR and the assigned counter.');
                }
            }
            if ($request->routeIs('vendor.tickets.redeem')) {
                $outcome = $service->redeem($actor, $vendor, $request->safe()->except(['expected_school_id', 'expected_account_id']));
                $ticket = $outcome['receipt'];
            }

            if ($request->routeIs('vendor.tickets.resolve')) {
                $resolved = $service->resolve($actor, $vendor, $operation);
                $ticket = $resolved['receipt'];
                $outcome = ['replayed' => true, 'abandoned' => $resolved['status'] === 'abandoned'];
            }

            return response()->view('vendor.tickets', compact('profile', 'vendor', 'ticket', 'credentials', 'outcome', 'operation', 'failure'), $failure ? 404 : 200);
        });
    }
}
