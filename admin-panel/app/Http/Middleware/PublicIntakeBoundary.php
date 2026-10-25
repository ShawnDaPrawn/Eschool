<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admissions\Intake;
use App\Models\Platform\School;
use App\Services\Admissions\IntakeAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublicIntakeBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $school = School::where('slug', (string) $request->route('schoolSlug'))->active()->firstOrFail();
            app(CurrentSchool::class)->set($school);
            app(IntakeAuthority::class)->enabled(in_array($request->route()->getActionMethod(), ['begin', 'save', 'submit', 'decide', 'upload', 'remove'], true));
            $intake = Intake::where('public_id', (string) $request->route('intakeId'))->firstOrFail();
            abort_if($intake->state === 'draft', 404);
            $request->attributes->set('admissions.intake', $intake);
            $response = $next($request);
        } catch (Throwable $error) {
            $response = app(ExceptionHandler::class)->render($request, $error);
        }
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
