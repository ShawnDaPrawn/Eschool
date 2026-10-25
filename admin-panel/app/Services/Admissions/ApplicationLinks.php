<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Mail\Admissions\ResumeApplicationMail;
use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationLink;
use App\Models\Admissions\Intake;
use App\Services\MailService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class ApplicationLinks
{
    public function emailHash(string $email): string
    {
        return hash_hmac('sha256', strtolower(trim($email)), (string) config('app.key'));
    }

    public function issue(Application $application, bool $officeUpdate = false): void
    {
        app(IntakeAuthority::class)->enabled();
        $fresh = Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at', 'parent_contact', 'email_hash'])->find($application->id);
        if ($fresh === null || app(IntakeRetention::class)->due($fresh)) {
            return;
        }
        $address = (string) ($fresh->parent_contact['email'] ?? '');
        abort_unless(filter_var($address, FILTER_VALIDATE_EMAIL) !== false, 422);
        $limit = 'application-link:'.app(CurrentSchool::class)->id().':'.$this->emailHash($address);
        if (! $officeUpdate && RateLimiter::tooManyAttempts($limit, 6)) {
            return;
        }
        if (! $officeUpdate) {
            RateLimiter::hit($limit, 3600);
        }
        DB::transaction(function () use ($application, $address): void {
            $row = Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at', 'parent_contact', 'email_hash'])->lockForUpdate()->findOrFail($application->id);
            if (app(IntakeRetention::class)->due($row)) {
                return;
            }
            abort_unless(hash_equals($row->email_hash, $this->emailHash($address)), 409);
            ApplicationLink::where('application_id', $row->id)->whereNull('used_at')->update(['used_at' => now()]);
            $token = bin2hex(random_bytes(32));
            $link = ApplicationLink::create(['application_id' => $row->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $row->email_hash, 'expires_at' => now()->addMinutes((int) config('admissions.magic_minutes'))]);
            $school = app(CurrentSchool::class)->school();
            $intake = Intake::findOrFail($row->intake_id);
            // Fragments are never sent in HTTP URLs, referrers or access logs.
            $url = rtrim((string) config('app.url'), '/').'/apply/'.$school->slug.'/'.$intake->public_id.'/resume#'.$token;
            DB::afterCommit(fn () => app(CurrentSchool::class)->run((int) $school->id, fn () => MailService::queue(new ResumeApplicationMail($address, $url, (int) $link->id, (int) $school->id))));
        });
    }

    public function requestResume(Intake $intake, string $email): void
    {
        app(IntakeAuthority::class)->enabled();
        foreach (Application::where('intake_id', $intake->id)->where('email_hash', $this->emailHash($email))->orderByDesc('id')->limit(3)->get(['id']) as $row) {
            $this->issue($row);
        }
    }

    /** Exchanged by a deliberate CSRF-protected POST, not an email prefetch GET. */
    public function consume(Intake $intake, string $token): array
    {
        app(IntakeAuthority::class)->enabled();
        abort_unless(strlen($token) === 64 && ctype_xdigit($token), 404);

        return DB::transaction(function () use ($intake, $token): array {
            $link = ApplicationLink::where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            abort_if($link->used_at !== null || $link->expires_at->lessThanOrEqualTo(now()), 404);
            $application = Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at', 'email_hash'])->where('intake_id', $intake->id)->lockForUpdate()->findOrFail($link->application_id);
            abort_if(app(IntakeRetention::class)->due($application), 404);
            abort_unless(hash_equals($application->email_hash, $link->email_hash), 404);
            $key = bin2hex(random_bytes(32));
            $link->update(['used_at' => now()]);
            $application->update(['draft_key_hash' => hash('sha256', $key), 'email_verified_at' => now()]);

            return ['id' => (int) $application->id, 'key' => $key];
        }, 3);
    }

    public function deliverable(int $linkId, int $schoolId, string $address): bool
    {
        return app(CurrentSchool::class)->run($schoolId, function () use ($linkId, $address): bool {
            app(IntakeAuthority::class)->enabled();
            $link = ApplicationLink::whereNull('used_at')->where('expires_at', '>', now()->utc())->find($linkId);
            $row = $link === null ? null : Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at', 'email_hash'])->find($link->application_id);

            return $link !== null && $row !== null && ! app(IntakeRetention::class)->due($row) && hash_equals($row->email_hash, $this->emailHash($address)) && hash_equals($link->email_hash, $row->email_hash);
        });
    }
}
