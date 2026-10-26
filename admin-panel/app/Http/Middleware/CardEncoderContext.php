<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Services\Cards\CardReaders;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Dedicated machine scope: encoder credentials never become gate or User auth. */
class CardEncoderContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $scope = app(CurrentSchool::class);
        abort_if($scope->isAcrossSchools() || $scope->isImpersonating(), 403);
        $credential = $request->bearerToken() ?? '';
        abort_unless(strlen($credential) < 160 && preg_match('/^NED1\.([1-9][0-9]*)\.([a-f0-9-]{36})\.([a-f0-9]{64})$/D', $credential, $parts) === 1, 401);
        $row = DB::table('card_reader_devices')->where('school_id', $parts[1])->where('id', $parts[2])->first(['secret_hash']);
        abort_unless($row !== null && hash_equals($row->secret_hash, hash('sha256', $parts[3])), 401);
        abort_if($request->hasHeader('X-School-Id') && $request->header('X-School-Id') !== $parts[1], 403);

        return $scope->run(School::findOrFail((int) $parts[1]), function () use ($request, $next, $credential) {
            app(CardReaders::class)->authenticate($credential, 'encoder', true);

            return $next($request);
        });
    }
}
