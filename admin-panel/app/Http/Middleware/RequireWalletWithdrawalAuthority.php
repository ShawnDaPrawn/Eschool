<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Money\WalletWithdrawal;
use App\Services\Money\WalletWithdrawals;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWalletWithdrawalAuthority
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null, 401);
        app(WalletWithdrawals::class)->adult($request->user(), true);
        if ($request->routeIs('api.v2.wallets.withdrawals.cancel')) {
            abort_unless(WalletWithdrawal::findOrFail((int) $request->route('withdrawal'))->holder_user_id === (int) $request->user()->id, 404);
        }

        return $next($request);
    }
}
