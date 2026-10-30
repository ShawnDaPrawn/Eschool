<?php

declare(strict_types=1);

namespace App\Services\Platform\Mail;

use App\Models\Platform\SchoolSendingDomain;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * School sending domains (Action Plan 2.2): a school connects its own domain,
 * adds the DNS records Amazon SES needs (Easy DKIM, a custom MAIL FROM and a
 * recommended DMARC record), and once SES has verified it the school's
 * notices are sent From "<school name> <notices@domain>". Until then, and for
 * account mail always, the platform's From address is used.
 *
 * One domain per school; a domain (with its subdomains and parents) belongs
 * to one school across the platform; the platform's own domains are refused.
 * Every connect, verification change and disconnect is audited.
 * engineering/specs/platform/sending-domains.md.
 */
class SendingDomains
{
    /** Shared second-level suffixes nobody owns outright. */
    private const PUBLIC_SUFFIXES = ['co.za', 'org.za', 'net.za', 'edu.za', 'ac.za', 'gov.za', 'school.za', 'web.za', 'nom.za', 'mil.za',
        'co.uk', 'org.uk', 'ac.uk', 'gov.uk', 'com.au', 'edu.au', 'co.nz', 'co.zw', 'co.bw', 'co.na', 'co.ls', 'co.sz'];

    public function __construct(private readonly SesIdentities $ses, private readonly CurrentSchool $currentSchool) {}

    public static function enabled(): bool
    {
        return (bool) config('mail.sending_domains.enabled');
    }

    /** The school's Super Admin, or office staff with the school settings permission. */
    public function authorise(?User $actor): void
    {
        abort_unless($actor !== null && ($actor->hasRole('Super Admin')
            || (! $actor->hasAnyRole(['Parent', 'Student', 'Teacher']) && $actor->can('setting-create'))), 403);
    }

    /** The current school's domain, if it has one. */
    public function current(User $actor): ?SchoolSendingDomain
    {
        $this->authorise($actor);

        return SchoolSendingDomain::query()->first();
    }

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['domain' => ['required', 'string', 'max:253']];
    }

    public function connect(User $actor, string $input): SchoolSendingDomain
    {
        $this->authorise($actor);
        $this->requireEnabled();
        $domain = self::normalise($input);
        $this->validateDomain($domain);
        $schoolId = $this->currentSchool->id();
        if (SchoolSendingDomain::query()->exists()) {
            throw ValidationException::withMessages(['domain' => __('This school already has a domain. Disconnect it before connecting another.')]);
        }
        if ($this->claimedElsewhere($domain, $schoolId)) {
            throw $this->unavailable();
        }

        try {
            $row = DB::transaction(fn () => SchoolSendingDomain::query()->create([
                'domain' => $domain,
                'ses_identity' => $domain,
                'mail_from_domain' => self::mailFromDomain($domain),
                'status' => SchoolSendingDomain::PENDING,
                'connected_by' => $actor->id,
            ]));
        } catch (QueryException $e) {
            // Another request took the school's place or the domain first.
            if (($e->errorInfo[0] ?? null) !== '23000') {
                throw $e;
            }

            throw SchoolSendingDomain::query()->exists()
                ? ValidationException::withMessages(['domain' => __('This school already has a domain. Disconnect it before connecting another.')])
                : $this->unavailable();
        }

        $created = false;
        try {
            $summary = $this->ses->create($domain, $schoolId);
            $created = true;
            $this->ses->setMailFrom($domain, $row->mail_from_domain);
        } catch (SesIdentityExists) {
            // Never adopt an identity SES already has: its DNS proof may be someone else's.
            $row->delete();
            Log::warning('sending_domain_identity_exists', ['school_id' => $schoolId]);

            throw $this->unavailable();
        } catch (Throwable $e) {
            if ($created) {
                try {
                    $this->ses->delete($domain);
                } catch (Throwable) {
                    // Reported below; support removes a leftover identity.
                }
            }
            $row->delete();
            Log::warning('sending_domain_connect_failed', ['school_id' => $schoolId, 'exception' => $e::class]);

            throw ValidationException::withMessages(['domain' => __('The mail service could not be reached just now. Try again in a few minutes.')]);
        }

        $this->apply($row, $summary);
        Audit::record('sending_domain.connected', $row, [], ['domain' => $row->domain, 'status' => $row->status]);
        if ($row->status === SchoolSendingDomain::VERIFIED) {
            Audit::record('sending_domain.verified', $row, ['status' => SchoolSendingDomain::PENDING], ['domain' => $row->domain, 'status' => $row->status]);
        }

        return $row;
    }

    /** "Check now": asks SES again about the current school's domain. */
    public function check(User $actor, int $id): SchoolSendingDomain
    {
        $this->authorise($actor);
        $this->requireEnabled();

        return $this->refresh(SchoolSendingDomain::query()->findOrFail($id));
    }

    public function disconnect(User $actor, int $id): void
    {
        $this->authorise($actor);
        $this->requireEnabled();
        $row = SchoolSendingDomain::query()->findOrFail($id);
        try {
            $this->ses->delete($row->ses_identity);
        } catch (Throwable $e) {
            Log::warning('sending_domain_disconnect_failed', ['school_id' => (int) $row->school_id, 'exception' => $e::class]);

            throw ValidationException::withMessages(['domain' => __('The mail service could not be reached just now. Try again in a few minutes.')]);
        }
        $row->delete();
        Audit::record('sending_domain.disconnected', $row, ['domain' => $row->domain, 'status' => $row->status], []);
    }

    /**
     * Asks SES about a domain and records the answer. Called by "Check now"
     * and by the scheduled poll, inside the domain's school.
     */
    public function refresh(SchoolSendingDomain $row): SchoolSendingDomain
    {
        $before = $row->status;
        try {
            $summary = $this->ses->status($row->ses_identity);
        } catch (Throwable $e) {
            Log::warning('sending_domain_check_failed', ['school_id' => (int) $row->school_id, 'exception' => $e::class]);
            $row->forceFill(['last_checked_at' => now(), 'last_error' => 'ses_unreachable'])->save();

            return $row;
        }

        if ($summary === null) {
            $row->forceFill(['status' => SchoolSendingDomain::FAILED, 'verified_at' => null, 'last_checked_at' => now(), 'last_error' => 'identity_missing'])->save();
        } else {
            $this->apply($row, $summary);
        }

        if ($before !== $row->status && ($before === SchoolSendingDomain::VERIFIED || $row->status === SchoolSendingDomain::VERIFIED)) {
            Audit::record($row->status === SchoolSendingDomain::VERIFIED ? 'sending_domain.verified' : 'sending_domain.unverified', $row,
                ['status' => $before], ['domain' => $row->domain, 'status' => $row->status, 'last_error' => $row->last_error]);
        }

        return $row;
    }

    /**
     * The scheduled poll: every school's waiting domains (at most every ten
     * minutes each) and verified ones once a day. Returns how many it checked.
     */
    public function poll(): int
    {
        if (! self::enabled()) {
            return 0;
        }
        // Platform work across schools: each domain is then refreshed inside its own school.
        $due = SchoolSendingDomain::acrossSchools()
            ->where(fn ($q) => $q->where('status', SchoolSendingDomain::PENDING)
                ->where(fn ($q) => $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subMinutes(10))))
            ->orWhere(fn ($q) => $q->where('status', SchoolSendingDomain::VERIFIED)
                ->where(fn ($q) => $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subDay())))
            ->orderBy('id')->limit(500)->get(['id', 'school_id']);

        foreach ($due as $row) {
            $this->currentSchool->run((int) $row->school_id, fn () => $this->refresh(SchoolSendingDomain::query()->findOrFail($row->id)));
        }

        return $due->count();
    }

    /**
     * The From address for a school's notices: notices@ its verified domain,
     * or null for the platform address. Only the delivery's own school's
     * domain is ever used.
     */
    public function noticeAddress(?int $schoolId): ?string
    {
        if (! self::enabled() || $schoolId === null) {
            return null;
        }
        // Explicit school, not the ambient one: the delivery row names it.
        $domain = SchoolSendingDomain::acrossSchools()->where('school_id', $schoolId)->where('status', SchoolSendingDomain::VERIFIED)->value('domain');

        return is_string($domain) && $domain !== '' ? config('mail.sending_domains.local_part', 'notices').'@'.$domain : null;
    }

    /**
     * The DNS records the school adds: three DKIM CNAMEs, the MAIL FROM MX and
     * SPF TXT, and a recommended DMARC TXT.
     *
     * @return list<array{type: string, name: string, value: string, purpose: string}>
     */
    public function records(SchoolSendingDomain $row): array
    {
        $records = [];
        foreach ((array) $row->dkim_tokens as $token) {
            $records[] = ['type' => 'CNAME', 'name' => $token.'._domainkey.'.$row->domain, 'value' => $token.'.dkim.amazonses.com', 'purpose' => 'dkim'];
        }
        $region = (string) config('services.ses.region', 'af-south-1');
        $records[] = ['type' => 'MX', 'name' => $row->mail_from_domain, 'value' => '10 feedback-smtp.'.$region.'.amazonses.com', 'purpose' => 'mail_from'];
        $records[] = ['type' => 'TXT', 'name' => $row->mail_from_domain, 'value' => '"v=spf1 include:amazonses.com ~all"', 'purpose' => 'spf'];
        $records[] = ['type' => 'TXT', 'name' => '_dmarc.'.$row->domain, 'value' => '"v=DMARC1; p=none;"', 'purpose' => 'dmarc'];

        return $records;
    }

    public static function normalise(string $input): string
    {
        return rtrim(mb_strtolower(trim($input)), '.');
    }

    public static function mailFromDomain(string $domain): string
    {
        return config('mail.sending_domains.mail_from_label', 'bounce').'.'.$domain;
    }

    /** Refuses anything but a plain domain the school could own. */
    public function validateDomain(string $domain): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages(['domain' => $message]);
        if (filter_var(trim($domain, '[]'), FILTER_VALIDATE_IP) !== false) {
            $fail(__('Enter a domain name, not an IP address.'));
        }
        if ($domain === '' || preg_match('/[@\/:\s]/u', $domain) === 1) {
            $fail(__('Enter the domain only, for example school.co.za.'));
        }
        $labels = explode('.', $domain);
        $label = '/^(?!-)[a-z0-9-]{1,63}(?<!-)$/';
        if (strlen($domain) > 253 || strlen(self::mailFromDomain($domain)) > 253 || count($labels) < 2
            || array_filter($labels, fn (string $part) => preg_match($label, $part) !== 1) !== []
            || preg_match('/^([a-z]{2,63}|xn--[a-z0-9-]{1,59})$/', (string) end($labels)) !== 1
            || in_array($domain, self::PUBLIC_SUFFIXES, true)) {
            $fail(__('This is not a valid domain name.'));
        }
        foreach ($this->reserved() as $reserved) {
            if ($domain === $reserved || str_ends_with($domain, '.'.$reserved) || str_ends_with($reserved, '.'.$domain)) {
                $fail(__('This domain belongs to NavigateEd and cannot be used.'));
            }
        }
    }

    /** @return list<string> the platform's own domains */
    private function reserved(): array
    {
        $hosts = (array) config('mail.sending_domains.reserved', []);
        $hosts[] = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $hosts[] = (string) parse_url((string) config('identity.portal_url'), PHP_URL_HOST);
        $hosts[] = (string) substr((string) strrchr((string) config('mail.from.address'), '@'), 1);

        return array_values(array_unique(array_filter(array_map(fn ($host) => self::normalise((string) $host), $hosts),
            fn (string $host) => str_contains($host, '.') && filter_var($host, FILTER_VALIDATE_IP) === false)));
    }

    /** Whether another school has this domain, one of its subdomains or one of its parents. */
    private function claimedElsewhere(string $domain, int $schoolId): bool
    {
        $labels = explode('.', $domain);
        $parents = [];
        for ($i = 1; $i < count($labels) - 1; $i++) {
            $parents[] = implode('.', array_slice($labels, $i));
        }

        // Platform check across schools: says only yes or no.
        return SchoolSendingDomain::acrossSchools()->where('school_id', '!=', $schoolId)
            ->where(fn ($q) => $q->where('domain', $domain)->orWhere('domain', 'like', '%.'.$domain)->orWhereIn('domain', $parents ?: ['']))
            ->exists();
    }

    /**
     * @param  array{verified: bool, dkim_status: string|null, tokens: list<string>, mail_from_status: string|null, error: string|null}  $summary
     */
    private function apply(SchoolSendingDomain $row, array $summary): void
    {
        $verified = $summary['verified'] && $summary['dkim_status'] === 'SUCCESS';
        $status = $verified ? SchoolSendingDomain::VERIFIED : ($summary['dkim_status'] === 'FAILED' ? SchoolSendingDomain::FAILED : SchoolSendingDomain::PENDING);
        $error = $verified ? null : match ($summary['dkim_status']) {
            'FAILED' => 'dkim_failed',
            'TEMPORARY_FAILURE' => 'dkim_temporary_failure',
            default => $summary['error'] !== null ? substr(strtolower($summary['error']), 0, 64) : null,
        };
        $row->forceFill([
            'status' => $status,
            'dkim_status' => $summary['dkim_status'],
            'mail_from_status' => $summary['mail_from_status'] ?? $row->mail_from_status,
            'dkim_tokens' => $summary['tokens'] !== [] ? $summary['tokens'] : $row->dkim_tokens,
            'verified_at' => $verified ? ($row->verified_at ?? now()) : null,
            'last_checked_at' => now(),
            'last_error' => $error,
        ])->save();
    }

    private function requireEnabled(): void
    {
        if (! self::enabled()) {
            throw ValidationException::withMessages(['domain' => __('Sending from your own domain is not switched on yet.')]);
        }
    }

    private function unavailable(): ValidationException
    {
        return ValidationException::withMessages(['domain' => __('This domain cannot be connected. If it is your school\'s, ask Navigate Ed support to check it.')]);
    }
}
