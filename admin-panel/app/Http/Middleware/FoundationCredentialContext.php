<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Services\Cards\CardReaders;
use App\Services\Classroom\ClassroomSessions;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** These credentials never become a User or a general application session. */
class FoundationCredentialContext
{
    public function handle(Request $request, Closure $next, string $purpose = 'device'): Response
    {
        $scope = app(CurrentSchool::class);
        abort_if($scope->isAcrossSchools() || $scope->isImpersonating(), 403);
        $header = (string) $request->header('Authorization', '');
        abort_unless(strlen($header) < 340 && preg_match('/^Bearer ([^\s,]+)$/Di', $header, $bearer) === 1, 401);
        $parts = explode('~', $bearer[1]);
        if ($purpose === 'learner') {
            abort_unless(count($parts) === 2 && str_starts_with($parts[0], 'NEF1.'), 401);
            [$learner, $device] = $parts;
        } else {
            abort_unless($purpose === 'device' && count($parts) === 1, 401);
            $learner = null;
            $device = $parts[0];
        }
        abort_unless(strlen($device) < 160 && preg_match('/^NED1\.([1-9][0-9]*)\.([a-f0-9-]{36})\.([a-f0-9]{64})$/D', $device, $ref) === 1, 401);
        // Only credential hash/kind metadata may be read before validated context.
        $metadata = DB::table('card_reader_devices')->where('school_id', $ref[1])->where('id', $ref[2])->first(['secret_hash', 'kind']);
        abort_unless($metadata !== null && $metadata->kind === 'classroom'
            && hash_equals($metadata->secret_hash, hash('sha256', $ref[3])), 401);
        abort_if($request->hasHeader('X-School-Id') && $request->header('X-School-Id') !== $ref[1], 403);
        $school = School::findOrFail((int) $ref[1]);

        return $scope->run($school, function () use ($request, $next, $device, $learner): Response {
            app(CardReaders::class)->authenticate($device, 'classroom', false);
            if ($learner !== null) {
                app(ClassroomSessions::class)->authenticate($device, $learner);
            }
            $request->attributes->set('foundation_device_credential', $device);
            $request->attributes->set('foundation_learner_credential', $learner);

            return $next($request);
        });
    }
}
