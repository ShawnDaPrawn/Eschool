<?php

declare(strict_types=1);

namespace App\Services\Platform\Audit;

use App\Models\Platform\AuditLog;
use App\Services\Platform\RequestContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes audit_log rows: who (actor), did what (action), to which record
 * (subject), the values before and after, from which address and request.
 *
 * Models with the Auditable trait are recorded on create, update, delete
 * and restore. Call Audit::record() for an action that is not a plain model
 * write (a password reset, an export, a module switched on), naming it like
 * "settings.mail_verified".
 *
 * An audit row that cannot be written is logged and never fails the change
 * itself.
 */
class Audit
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function record(string $action, ?Model $subject = null, array $before = [], array $after = []): ?AuditLog
    {
        // Only a user the request has already authenticated: asking the guard to
        // authenticate here (Auth::user()) would do it as a side effect of a
        // model write, from whatever request is bound, and cache the result.
        $user = Auth::hasUser() ? Auth::user() : null;
        $http = RequestContext::inHttpRequest();

        try {
            return AuditLog::create([
                'school_id' => self::schoolId($subject),
                'actor_id' => $user?->getAuthIdentifier(),
                'actor_type' => $user ? 'user' : ($http ? 'guest' : 'system'),
                'action' => Str::limit($action, 100, ''),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => is_numeric($subject?->getKey()) ? (int) $subject->getKey() : null,
                'before' => $before === [] ? null : $before,
                'after' => $after === [] ? null : $after,
                'ip' => $http ? request()->ip() : null,
                'request_id' => RequestContext::requestId(),
            ]);
        } catch (QueryException $e) {
            // Before the audit_log migration has run (earlier migrations and
            // the installer write models too) there is nothing to record into.
            if (($e->errorInfo[0] ?? null) !== '42S02') {
                self::failed($action, $subject, $e);
            }
        } catch (Throwable $e) {
            self::failed($action, $subject, $e);
        }

        return null;
    }

    private static function schoolId(?Model $subject): ?int
    {
        $schoolId = $subject?->getAttribute('school_id');

        return is_numeric($schoolId) ? (int) $schoolId : null;
    }

    private static function failed(string $action, ?Model $subject, Throwable $e): void
    {
        Log::error('audit_record_failed', [
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'exception' => get_class($e),
        ]);
    }
}
