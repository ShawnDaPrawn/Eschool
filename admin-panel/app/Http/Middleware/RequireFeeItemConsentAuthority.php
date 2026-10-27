<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Money\FeeItemConsents;
use App\Services\Money\ParentFeeItemScope;
use App\Services\Platform\IdempotencyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/** Holds fresh target authority across execution AND cached idempotent replay. */
class RequireFeeItemConsentAuthority
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header(HandleIdempotencyKey::HEADER, '');
        // Let the required-key middleware produce its standard 400 before body validation.
        if ($key === '' || ! app(IdempotencyService::class)->isValidKey($key)) {
            return $next($request);
        }
        $data = Validator::make($request->all(), FeeItemConsents::rules())->validate();

        return DB::transaction(function () use ($request, $next, $data): Response {
            app(ParentFeeItemScope::class)->mutation($request->user(), (int) $request->route('account'), (int) $request->route('item'), $data);

            return $next($request);
        }, 3);
    }
}
