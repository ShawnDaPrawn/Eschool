<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Admissions\ApplicantPage;
use App\Services\Admissions\ApplicantSession;
use App\Services\Admissions\IntakeAuthority;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Stop validation before it can flash applicant answers/contact to a plain session. */
class AdmissionsExceptionRenderer
{
    public static function render(Throwable $error, Request $r): ?Response
    {
        if (! $r->routeIs('admissions.public.*')) {
            return null;
        }
        $status = $error instanceof ValidationException ? 422 : ($error instanceof HttpExceptionInterface ? $error->getStatusCode() : ($error instanceof ModelNotFoundException ? 404 : 500));
        if (in_array($status, [409, 422, 503], true)) {
            try {
                $page = app(ApplicantPage::class);
                $intake = $page->intake($r);
                $screen = 'form';
                if ($r->routeIs('admissions.public.begin')) {
                    app(IntakeAuthority::class)->open($intake);
                } elseif (in_array($r->route()->getActionMethod(), ['save', 'submit', 'upload', 'remove', 'decide'], true)) {
                    app(ApplicantSession::class)->owner($r, $intake);
                    $screen = 'application';
                } else {
                    $screen = 'resume';
                }
                $messages = $error instanceof ValidationException ? $error->errors() : [[$status === 503 ? 'Document checks are temporarily unavailable. Your saved application remains available; choose the file again when retrying.' : ($status === 409 ? 'The saved application changed. Compare the current version before trying again.' : 'Check the form and try again.')]];

                return $page->render($r, $screen, array_merge(...array_values($messages)), $status, true);
            } catch (Throwable) {
                // No draft reflection after module withdrawal, lost ownership or a failed boundary.
            }
        }

        $restartUrl = null;
        try {
            $intake = app(ApplicantPage::class)->intake($r);
            app(IntakeAuthority::class)->open($intake);
            $restartUrl = app(ApplicantPage::class)->url($r, 'another');
        } catch (Throwable) {
            // No public restart when the source intake is unavailable.
        }
        $response = response()->view('admissions.public.unavailable', ['status' => $status, 'restartUrl' => $restartUrl], $status)->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
        if ($error instanceof HttpExceptionInterface) {
            foreach ($error->getHeaders() as $name => $value) {
                if (in_array(strtolower($name), ['retry-after', 'x-ratelimit-limit', 'x-ratelimit-remaining', 'x-ratelimit-reset'], true)) {
                    $response->headers->set($name, $value);
                }
            }
        }

        return $response;
    }
}
