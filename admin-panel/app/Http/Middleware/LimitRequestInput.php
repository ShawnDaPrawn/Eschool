<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Keeps a single request from costing far more than a normal one:
 *
 * - JSON bodies are limited to 1 MB. Only payment webhooks and two small admin
 *   fetches send JSON (the apps and forms send form data, whose field count PHP
 *   caps with max_input_vars), and a large JSON array makes validation run out
 *   of memory before any controller code runs.
 * - Page sizes (limit, per_page) are kept to 1-1000 and offsets to 0 or more.
 *   List endpoints pass them straight to the query, so limit=100000000 or a
 *   negative limit (no LIMIT at all) loaded whole tables. No table in the panel
 *   offers more than 500 rows a page.
 */
class LimitRequestInput
{
    public const MAX_JSON_BYTES = 1024 * 1024;

    public const MAX_PAGE_SIZE = 1000;

    private const RANGES = ['limit' => [1, self::MAX_PAGE_SIZE], 'per_page' => [1, self::MAX_PAGE_SIZE], 'offset' => [0, PHP_INT_MAX]];

    public function handle(Request $request, Closure $next)
    {
        if ($request->isJson() && (int) $request->header('Content-Length') > self::MAX_JSON_BYTES) {
            return response()->json(['error' => true, 'message' => trans('The request is too large.'), 'code' => 102], 413);
        }

        foreach (self::RANGES as $key => [$min, $max]) {
            $this->clamp($request->query, $key, $min, $max);
            $this->clamp($request->request, $key, $min, $max);
            // Many list endpoints read $_GET directly.
            $this->mirror($_GET, $request->query, $key);
            $this->mirror($_POST, $request->request, $key);
        }

        return $next($request);
    }

    /** A number is clamped to the range; anything else (e.g. an array) is removed. */
    private function clamp(InputBag $bag, string $key, int $min, int $max): void
    {
        if (! $bag->has($key)) {
            return;
        }
        $value = $bag->all()[$key];
        if (is_scalar($value)) {
            $bag->set($key, (string) min($max, max($min, (int) $value)));
        } else {
            $bag->remove($key);
        }
    }

    private function mirror(array &$global, InputBag $bag, string $key): void
    {
        if (! array_key_exists($key, $global)) {
            return;
        }
        if ($bag->has($key)) {
            $global[$key] = $bag->all()[$key];
        } else {
            unset($global[$key]);
        }
    }
}
