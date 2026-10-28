<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Money\WalletAccount;
use App\Policies\Money\WalletPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Fresh wallet/guardian authority runs before the HTTP replay cache. */
class RequireWalletAuthority
{
    public function handle(Request $request, Closure $next, string $purpose): Response
    {
        $actor = $request->user();
        abort_unless($actor !== null, 401);
        if ($purpose === 'open') {
            $owner = filter_var($request->input('owner_user_id'), FILTER_VALIDATE_INT);
            abort_unless($owner !== false && $owner > 0, 422);
            app(WalletPolicy::class)->owner($actor, $owner, true);
        } else {
            $wallet = WalletAccount::findOrFail((int) $request->route('wallet'));
            app(WalletPolicy::class)->limits($actor, $wallet, true);
        }

        return $next($request);
    }
}
