<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Mail\Platform\LearnerInvitationMail;
use App\Models\EmailTemplate;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerInvitation;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\MailService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * New learners no longer get their date of birth as password. They get a long
 * random password nobody sees, and each guardian with an email address gets a
 * link (signed, expiring, single use) to choose the learner's password. The
 * school can also print a one-time set-up code, used on /learner-setup with
 * the GR number.
 *
 * Setting the password through any link or code closes the learner's other
 * open links and codes and signs the learner out of the app.
 */
class LearnerInvitations
{
    public const EMAIL_TEMPLATE = 'learner_invitation';

    /** No 0/O, 1/I/L: the code is read off paper. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 10;

    public function __construct(private readonly CurrentSchool $school) {}

    /** A password nobody knows, for a learner whose password is set by invitation. */
    public static function unguessablePassword(): string
    {
        return Str::password(48);
    }

    /**
     * The learner's father, mother and guardian accounts that have an email address.
     *
     * @return Collection<int, User>
     */
    public function guardiansOf(User $learner): Collection
    {
        $student = $learner->student;
        if ($student === null) {
            return collect();
        }

        $parentIds = array_filter([$student->father_id, $student->mother_id, $student->guardian_id]);
        $legacy = Parents::query()->whereIn('id', $parentIds)->whereNotExists(function ($query) use ($student) {
            $query->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $student->school_id)
                ->where('guardian_links.learner_id', $student->id)->whereColumn('guardian_links.guardian_user_id', 'parents.user_id');
        })->pluck('user_id');
        $linked = GuardianLink::query()->where('learner_id', $student->id)->where('status', 'active')->where('can_approve', true)->pluck('guardian_user_id');

        return User::query()->whereIn('id', $legacy->merge($linked)->unique())->where('status', 1)->get()
            ->filter(fn (User $user) => filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false)->values();
    }

    /**
     * Emails each guardian a link to set the learner's password (through
     * MailService) and closes the links sent before. Returns how many guardians
     * were invited.
     */
    public function inviteGuardians(User $learner, ?User $by = null): int
    {
        $guardians = $this->guardiansOf($learner);
        if ($guardians->isEmpty()) {
            return 0;
        }

        $this->revoke($learner, LearnerInvitation::LINK);
        foreach ($guardians as $guardian) {
            [$invitation, $url] = $this->issueLink($learner, $guardian, $by);
            $this->mail($learner, $guardian, $url, $invitation);
        }

        return $guardians->count();
    }

    /**
     * @return array{0: LearnerInvitation, 1: string} the invitation and its link
     */
    public function issueLink(User $learner, User $guardian, ?User $by = null): array
    {
        abort_unless($this->guardiansOf($learner)->contains('id', $guardian->id), 403);
        $token = Str::random(64);
        $invitation = LearnerInvitation::create([
            'school_id' => $this->school->id(),
            'learner_user_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
            'channel' => LearnerInvitation::LINK,
            'token_hash' => hash('sha256', $token),
            'email_hash' => hash('sha256', strtolower(trim($guardian->email))),
            'expires_at' => now()->addDays((int) config('auth.learner_invitations.link_days', 7)),
            'created_by' => $by?->id,
        ]);

        return [$invitation, $this->linkUrl($invitation, $token)];
    }

    /**
     * A new set-up code for the school to print. Codes printed before stop working.
     *
     * @return array{0: LearnerInvitation, 1: string} the invitation and the code (shown once)
     */
    public function issueCode(User $learner, ?User $by = null): array
    {
        $this->revoke($learner, LearnerInvitation::CODE);

        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }
        $invitation = LearnerInvitation::create([
            'school_id' => $this->school->id(),
            'learner_user_id' => $learner->id,
            'channel' => LearnerInvitation::CODE,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addDays((int) config('auth.learner_invitations.code_days', 30)),
            'created_by' => $by?->id,
        ]);

        return [$invitation, implode('-', str_split($code, self::CODE_LENGTH / 2))];
    }

    /** The link invitation the URL names, whatever its state (null when the token is wrong). */
    public function findByLink(int $id, string $token): ?LearnerInvitation
    {
        $invitation = LearnerInvitation::where('channel', LearnerInvitation::LINK)->find($id);

        return $invitation && hash_equals((string) $invitation->token_hash, hash('sha256', $token)) && $this->currentAccess($invitation) ? $invitation : null;
    }

    /** The learner's open set-up code matching what was typed. */
    public function findOpenByCode(string $grNumber, string $code): ?LearnerInvitation
    {
        $learner = app(StudentNumbers::class)->resolveLogin($grNumber);
        if ($learner === null) {
            return null;
        }

        $typed = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
        $candidates = LearnerInvitation::open()
            ->where('learner_user_id', $learner->id)
            ->where('channel', LearnerInvitation::CODE)
            ->get();

        return $candidates->first(fn (LearnerInvitation $invitation) => Hash::check($typed, (string) $invitation->code_hash) && $this->currentAccess($invitation));
    }

    /**
     * Sets the learner's password through an open invitation. Returns false when
     * it was used, revoked or expired in the meantime.
     */
    public function complete(LearnerInvitation $invitation, string $password): bool
    {
        return DB::transaction(function () use ($invitation, $password) {
            $current = LearnerInvitation::whereKey($invitation->id)->lockForUpdate()->first();
            if ($current === null || ! $current->isOpen()) {
                return false;
            }

            return $this->school->run((int) $current->school_id, function () use ($current, $password) {
                if (! $this->currentAccess($current, lock: true)) {
                    return false;
                }
                $learner = $current->learner;
                $learner->forceFill([
                    'password' => Hash::make($password), 'password_change_required' => false, 'reset_request' => 0,
                ])->save();
                $current->forceFill(['used_at' => now()])->save();
                LearnerInvitation::open()->where('learner_user_id', $learner->id)->whereKeyNot($current->id)->update(['revoked_at' => now()]);
                AppTokens::revokeAll($learner);

                return true;
            });
        }, 5);
    }

    public function canDeliver(LearnerInvitation $invitation): bool
    {
        return $invitation->isOpen() && $this->currentAccess($invitation);
    }

    /** A secret never retains access after the mailbox or guardian rights change. */
    private function currentAccess(LearnerInvitation $invitation, bool $lock = false): bool
    {
        return $this->school->run((int) $invitation->school_id, function () use ($invitation, $lock) {
            if (! School::query()->active()->whereKey($invitation->school_id)->exists()) {
                return false;
            }
            $learner = User::query()->where('status', 1)->when($lock, fn ($query) => $query->lockForUpdate())->find($invitation->learner_user_id);
            if (! $learner || ! SchoolMembership::query()->active()->where('school_id', $invitation->school_id)
                ->where('user_id', $learner->id)->where('role', 'Student')->exists()) {
                return false;
            }
            $invitation->setRelation('learner', $learner);
            if ($invitation->channel === LearnerInvitation::CODE) {
                return true;
            }
            $guardian = User::query()->where('status', 1)->when($lock, fn ($query) => $query->lockForUpdate())->find($invitation->guardian_user_id);

            return $guardian !== null && is_string($invitation->email_hash)
                && hash_equals($invitation->email_hash, hash('sha256', strtolower(trim($guardian->email))))
                && SchoolMembership::query()->active()->where('school_id', $invitation->school_id)->where('user_id', $guardian->id)->where('role', 'Parent')->exists()
                && $this->guardiansOf($learner)->contains('id', $guardian->id);
        });
    }

    /** Closes the learner's open links and codes (or one channel's); returns how many. */
    public function revoke(User $learner, ?string $channel = null): int
    {
        return LearnerInvitation::open()
            ->where('learner_user_id', $learner->id)
            ->when($channel !== null, fn ($query) => $query->where('channel', $channel))
            ->update(['revoked_at' => now()]);
    }

    /**
     * Relative signature, so the link can start with APP_URL (like password
     * reset links) whatever host the panel was opened on.
     */
    private function linkUrl(LearnerInvitation $invitation, string $token): string
    {
        $path = URL::temporarySignedRoute('learner-setup.link', $invitation->expires_at, [
            'invitation' => $invitation->id,
            'token' => $token,
        ], false);

        return rtrim((string) config('app.url'), '/').$path;
    }

    private function mail(User $learner, User $guardian, string $url, LearnerInvitation $invitation): void
    {
        $settings = getSettings();
        $schoolName = (string) ($settings['school_name'] ?? config('app.name'));
        $template = EmailTemplate::where('type', self::EMAIL_TEMPLATE)->first();
        $values = [
            'name' => $guardian->full_name,
            'child_name' => $learner->full_name,
            'child_grnumber' => (string) $learner->email,
            'setup_link' => $url,
            'expires_on' => $invitation->expires_at->toFormattedDateString(),
            'school_name' => $schoolName,
            'school_email' => (string) ($settings['school_email'] ?? ''),
            'school_contact' => (string) ($settings['school_phone'] ?? ''),
        ];
        $payload = [
            'body' => EmailTemplateService::render((string) ($template->content ?? self::defaultTemplate()), $values),
            'email' => (string) $guardian->email,
            'subject' => EmailTemplateService::render((string) ($template->subject ?? trans('learner_invitation_subject')), $values),
            'school_name' => $schoolName,
        ];
        $from = config('mail.from.address');

        MailService::queue(new LearnerInvitationMail((int) $invitation->id, $payload, $from, $schoolName), (int) $guardian->id);
    }

    /** Used when the email_tempalates row is missing (EmailSeeder seeds it). */
    public static function defaultTemplate(): string
    {
        return <<<'HTML'
<p>Hello {$name},</p>
<p>{$child_name} (GR number {$child_grnumber}) can now use the {$school_name} app.
Choose {$child_name}'s password with this link:</p>
<p><a href="{$setup_link}">Set {$child_name}'s password</a></p>
<p>The link works once and until {$expires_on}. If it has expired, ask the school for a new one.</p>
<p>Regards,<br>{$school_name}<br>{$school_contact}<br>{$school_email}</p>
HTML;
    }
}
