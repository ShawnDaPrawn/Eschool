<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\EmailTemplate;
use App\Models\Parents;
use App\Models\Platform\LearnerInvitation;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\MailService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Carbon;
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

        return Parents::with('user')->whereIn('id', $parentIds)->get()
            ->map(fn (Parents $parent) => $parent->user)
            ->filter(fn (?User $user) => $user !== null && filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique('id')
            ->values();
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
            $this->mail($learner, $guardian, $url, $invitation->expires_at);
        }

        return $guardians->count();
    }

    /**
     * @return array{0: LearnerInvitation, 1: string} the invitation and its link
     */
    public function issueLink(User $learner, User $guardian, ?User $by = null): array
    {
        $token = Str::random(64);
        $invitation = LearnerInvitation::create([
            'school_id' => $this->school->id(),
            'learner_user_id' => $learner->id,
            'guardian_user_id' => $guardian->id,
            'channel' => LearnerInvitation::LINK,
            'token_hash' => hash('sha256', $token),
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

        return $invitation && hash_equals((string) $invitation->token_hash, hash('sha256', $token)) ? $invitation : null;
    }

    /** The learner's open set-up code matching what was typed. */
    public function findOpenByCode(string $grNumber, string $code): ?LearnerInvitation
    {
        $learner = User::learners()->where('email', trim($grNumber))->first();
        if ($learner === null) {
            return null;
        }

        $typed = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
        $candidates = LearnerInvitation::open()
            ->where('learner_user_id', $learner->id)
            ->where('channel', LearnerInvitation::CODE)
            ->get();

        return $candidates->first(fn (LearnerInvitation $invitation) => Hash::check($typed, (string) $invitation->code_hash));
    }

    /**
     * Sets the learner's password through an open invitation. Returns false when
     * it was used, revoked or expired in the meantime.
     */
    public function complete(LearnerInvitation $invitation, string $password): bool
    {
        return DB::transaction(function () use ($invitation, $password) {
            $current = LearnerInvitation::whereKey($invitation->id)->lockForUpdate()->first();
            $learner = $current?->learner;
            if ($current === null || ! $current->isOpen() || $learner === null) {
                return false;
            }

            $learner->forceFill([
                'password' => Hash::make($password),
                'password_change_required' => false,
                'reset_request' => 0,
            ])->save();
            $current->forceFill(['used_at' => now()])->save();
            LearnerInvitation::open()
                ->where('learner_user_id', $learner->id)
                ->whereKeyNot($current->id)
                ->update(['revoked_at' => now()]);
            AppTokens::revokeAll($learner);

            return true;
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

    private function mail(User $learner, User $guardian, string $url, Carbon $expiresAt): void
    {
        $settings = getSettings();
        $schoolName = (string) ($settings['school_name'] ?? config('app.name'));
        $template = EmailTemplate::where('type', self::EMAIL_TEMPLATE)->first();
        $values = [
            'name' => $guardian->full_name,
            'child_name' => $learner->full_name,
            'child_grnumber' => (string) $learner->email,
            'setup_link' => $url,
            'expires_on' => $expiresAt->toFormattedDateString(),
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

        MailService::sendWithFallback('emails.learner-invitation', $payload, function ($message) use ($payload, $from, $schoolName) {
            $message->to($payload['email'])->subject(html_entity_decode($payload['subject'], ENT_QUOTES));
            if ($from) {
                $message->from($from, $schoolName);
            }
        });
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
