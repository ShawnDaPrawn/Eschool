<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Pastoral\CoverAssignment;
use App\Services\Pastoral\CoverAuthority;
use App\Services\Pastoral\CoverQueries;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeCover
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $a = app(CoverAuthority::class);
        $actor = $a->actor($request->user(), ! $request->isMethod('GET'));
        if ($request->route('absence') !== null) {
            $a->absence($actor, (int) $request->route('absence'), ! $request->isMethod('GET'));
        } elseif ($request->route('occurrence') !== null) {
            $id = (int) $request->route('occurrence');
            app(CoverQueries::class)->occurrence($actor, $id);
            if (in_array($operation, ['candidates', 'assign'], true)) {
                $cover = CoverAssignment::findOrFail($id);
                $absence = $a->absence($actor, (int) $cover->cover_absence_id, $operation === 'assign');
                abort_unless($a->manages($actor, (int) $absence->session_year_id), 403);
            }
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
