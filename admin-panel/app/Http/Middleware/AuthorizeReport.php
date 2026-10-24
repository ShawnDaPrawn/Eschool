<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Academics\ReportCard;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Academics\Reports\ReportBatches;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeReport
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $actor = $request->user();
        $policy = app(ReportPolicy::class);
        if ($operation === 'family') {
            app(ReleasedMarks::class)->learner($actor, (int) $request->route('learner'));
        } elseif ($operation === 'family-card') {
            $policy->family($actor, (int) $request->route('card'));
        } elseif ($operation === 'download') {
            abort_unless($request->hasValidSignature() && (int) $request->query('school') === app(CurrentSchool::class)->id() && (int) $request->query('reader') === (int) $actor->id && in_array((string) $request->query('staff'), ['0', '1'], true), 403);
            if ((int) $request->query('staff') === 1) {
                $policy->batch($actor, (int) ReportCard::findOrFail((int) $request->route('card'))->report_batch_id);
            } else {
                $policy->family($actor, (int) $request->route('card'));
            }
        } elseif ($operation === 'manage') {
            app(AcademicAuthority::class)->manage($actor);
        } elseif ($operation === 'selection') {
            app(ReportBatches::class)->selection($actor, $request->all());
        } elseif ($operation === 'comment') {
            $policy->comment($actor, ReportCard::with('batch')->findOrFail((int) $request->route('card')), $request->input('subject_id') === null ? null : (int) $request->input('subject_id'));
        } elseif ($request->route('batch') !== null) {
            $batch = $policy->batch($actor, (int) $request->route('batch'));
            if (in_array($operation, ['grade_head', 'principal'], true)) {
                $policy->sign($actor, $batch, $operation);
            }
        } else {
            app(AcademicAuthority::class)->staff($actor);
        }

        return $next($request);
    }
}
