<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Consultations\Consultation;
use App\Services\Consultations\ConsultationAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeConsultation
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $access = app(ConsultationAccess::class);
        $actor = $request->user();
        $write = ! $request->isMethodSafe();
        if (in_array($action, ['audiences', 'managed', 'configuration', 'manager-results', 'responses', 'history', 'history-entry', 'create', 'edit', 'publish', 'close', 'cancel'], true)) {
            $access->manager($actor, $write);
        } else {
            $access->actor($actor, $write);
        }
        if ($request->route('consultation') !== null) {
            $row = Consultation::whereKey((int) $request->route('consultation'))->firstOrFail(['id', 'school_id', 'session_year_id', 'status', 'audience']);
            if (in_array($action, ['ballot', 'results', 'vote'], true)) {
                $access->voter($actor, $row, $write);
            } elseif ($write) {
                $access->year((int) $row->session_year_id, true);
            }
        }

        return $next($request);
    }
}
