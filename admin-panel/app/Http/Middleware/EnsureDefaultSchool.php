<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * school.default: pages that change what every school shares (the mail
 * server, the push project, app versions, interface languages) open only
 * while working in the default school, the install's own, until the
 * platform admin area takes them over. Another school's admin is sent back
 * with a message (403 for JSON).
 */
class EnsureDefaultSchool
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->currentSchool->idOrNull() === CurrentSchool::defaultId()) {
            return $next($request);
        }

        $message = trans('platform_setting_home_school_only');
        if ($request->expectsJson()) {
            return response()->json(['error' => true, 'message' => $message], 403);
        }

        return redirect()->route('home')->withErrors(['message' => $message]);
    }
}
