<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every request starts with no school chosen (school.context chooses it), and
 * whatever was chosen before the request (a test, a long-running worker) is
 * put back once the response has been sent.
 */
class ResetSchoolContext
{
    private const PREVIOUS = 'tenancy.previous_school';

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::PREVIOUS, $this->currentSchool->snapshot());
        $this->currentSchool->forget();

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $previous = $request->attributes->get(self::PREVIOUS);
        if (is_array($previous)) {
            $this->currentSchool->restore($previous);
        }
    }
}
