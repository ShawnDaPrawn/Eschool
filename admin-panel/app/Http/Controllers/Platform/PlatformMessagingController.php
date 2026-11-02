<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\MessagingCostsRequest;
use App\Services\Platform\Saas\PlatformMessagingCosts;
use Illuminate\Contracts\View\View;

/** HQ: SMS and WhatsApp messages and their pass-through cost per school (Action Plan 2.7). */
class PlatformMessagingController extends Controller
{
    public function index(MessagingCostsRequest $request, PlatformMessagingCosts $costs): View
    {
        return view('saas.messaging', $costs->month($request->validated('month')));
    }
}
