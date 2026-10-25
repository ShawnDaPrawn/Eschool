<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Panel\SessionYearContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Past (and not yet current) school years are read-only in the admin panel.
 *
 * While the header's year switcher shows a year other than the school's current
 * one:
 *
 * - every POST, PUT, PATCH and DELETE is refused with a message saying how to
 *   switch back, except the writes listed in config('panel.read_only_years.allow')
 *   (the switch itself, sign-out, the person's profile, settings that are the
 *   same in every year, and requests that only read or print);
 * - GET requests that don't name a year get the selected one, so list endpoints
 *   that fall back to the current year follow the switcher instead.
 *
 * The views get the context as $panelYear for the banner and the switcher.
 */
class EnforceReadOnlyYears
{
    public function __construct(private readonly SessionYearContext $years) {}

    public function handle(Request $request, Closure $next): Response
    {
        View::share('panelYear', $this->years);

        if (! $this->years->isReadOnly()) {
            return $next($request);
        }

        if ($request->isMethodSafe()) {
            return $this->followSelectedYear($request, $next);
        }

        if ($request->is(config('panel.read_only_years.allow', []))) {
            return $next($request);
        }

        return $this->refuse($request);
    }

    /** Lists read session_year_id from the query string or straight from $_GET. */
    private function followSelectedYear(Request $request, Closure $next): Response
    {
        if ($request->query->has('session_year_id') && $request->query('session_year_id') !== '') {
            return $next($request);
        }

        $before = $_GET;
        $selected = (string) $this->years->selectedId();
        $request->query->set('session_year_id', $selected);
        $_GET['session_year_id'] = $selected;

        try {
            return $next($request);
        } finally {
            // $_GET outlives the request in a long-running process (and in tests).
            $_GET = $before;
        }
    }

    private function refuse(Request $request): Response
    {
        $message = trans('read_only_year_refused', [
            'year' => $this->years->selected()?->name,
            'current' => $this->years->current()?->name,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => true, 'message' => $message, 'code' => Response::HTTP_LOCKED], Response::HTTP_LOCKED)->header('Cache-Control', 'no-store, private');
        }

        foreach (config('panel_private_forms', []) as $form) {
            if (! $request->is(...$form['paths'])) {
                continue;
            }
            $draft = [];
            foreach ($form['fields'] as $field => $definition) {
                $value = $request->input($field);
                if (is_string($value) && $value !== '') {
                    $draft[] = ['label' => $definition['label'], 'value' => mb_substr($value, 0, $definition['limit'])];
                }
            }
            $title = $form['title'];
            $returnUrl = Route::has($form['route']) ? route($form['route']) : route('home');

            return response()->view('errors.private-year-refusal', compact('message', 'draft', 'title', 'returnUrl'), Response::HTTP_LOCKED)->header('Cache-Control', 'no-store, private');
        }

        return redirect()->back()->withErrors(['read_only_year' => $message])->withInput();
    }
}
