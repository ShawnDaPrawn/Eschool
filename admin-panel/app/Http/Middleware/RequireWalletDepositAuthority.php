<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Money\WalletDepositClaim;
use App\Services\Money\WalletDeposits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Runs before the HTTP replay cache as well as inside permanent operations. */
class RequireWalletDepositAuthority
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null, 401);
        app(WalletDeposits::class)->parent($request->user(), true);
        if ($request->routeIs('api.v2.wallets.deposits.cancel')) {
            abort_unless(WalletDepositClaim::findOrFail((int) $request->route('claim'))->actor_id === (int) $request->user()->id, 404);
        }

        return $next($request);
    }
}
