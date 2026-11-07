<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use App\Models\OpenStandards\OpenBadgeCredential;
use App\Models\Platform\School;
use App\Models\Recognition\AwardPresentation;
use App\Services\Platform\Tenancy\CurrentSchool;
use stdClass;

/**
 * The public side of Open Badges (Action Plan 5.9): anyone holding a
 * NavigateEd credential can check it, and read whether it was revoked. The
 * answer repeats only what the credential itself says, plus valid or revoked:
 * nothing about the learner, the school's other records or other credentials.
 */
class BadgeVerifier
{
    public const REVOKED_REASON = 'The school withdrew this award.';

    public function __construct(private readonly BadgeSigningKeys $keys, private readonly DataIntegrityProof $proof) {}

    /**
     * @return array{verified: bool, status: string, checks: array{proof: bool, issuer: bool, revoked: bool|null, dates: bool, recipient: bool|null}, credential: array<string, string|null>|null}
     */
    public function verify(mixed $document, ?string $recipientName = null): array
    {
        $fail = ['verified' => false, 'status' => 'not_verified', 'checks' => ['proof' => false, 'issuer' => false, 'revoked' => null, 'dates' => false, 'recipient' => null], 'credential' => null];
        if (! $document instanceof stdClass || ! is_string($document->id ?? null) || ! preg_match('/^urn:uuid:([0-9a-f-]{36})$/', $document->id, $m)) {
            return $fail;
        }
        // The credential's own id is the one cross-school lookup; it finds at most its own row.
        $row = OpenBadgeCredential::acrossSchools()->where('credential_id', $m[1])->first();
        if ($row === null) {
            return $fail;
        }

        return app(CurrentSchool::class)->run((int) $row->school_id, function () use ($row, $document, $recipientName, $fail): array {
            $school = app(CurrentSchool::class)->school();
            // Only the key recorded when this credential was issued may have signed it.
            $trusted = array_filter($this->keys->trusted((int) $row->school_id), fn (string $public): bool => hash_equals((string) $row->key_id, $this->keys->keyId($public)));
            $proof = $this->proof->verify($document, $trusted);
            // The issuer as recorded when the credential was issued, so a later school change cannot break it.
            $issued = json_decode((string) $row->document, false);
            $issuer = $school !== null && is_object($document->issuer ?? null) && is_object($issued->issuer ?? null) && ($document->issuer->id ?? null) === ($issued->issuer->id ?? false);
            if (! $proof || ! $issuer) {
                return array_replace_recursive($fail, ['checks' => ['proof' => $proof, 'issuer' => $issuer]]);
            }
            $revoked = $this->revoked($row);
            $dates = $this->current($document);
            $recipient = $recipientName === null ? null : $this->recipient($document, $recipientName);
            $verified = ! $revoked && $dates && $recipient !== false;

            return ['verified' => $verified, 'status' => $revoked ? 'revoked' : ($verified ? 'valid' : 'not_verified'),
                'checks' => ['proof' => true, 'issuer' => true, 'revoked' => $revoked, 'dates' => $dates, 'recipient' => $recipient],
                'credential' => $this->summary($document)];
        });
    }

    /**
     * The 1EdTech revocation list for one credential: empty unless it is revoked.
     *
     * @return array{id: string, issuer: string, revokedCredentials: list<array{id: string, revocationReason: string}>}|null
     */
    public function status(string $credentialId): ?array
    {
        $row = OpenBadgeCredential::acrossSchools()->where('credential_id', $credentialId)->first();
        if ($row === null) {
            return null;
        }

        return app(CurrentSchool::class)->run((int) $row->school_id, fn (): array => [
            'id' => route('open-badges.status', ['credential' => $row->credential_id]),
            'issuer' => BadgeDocuments::issuerId(app(CurrentSchool::class)->school()),
            'revokedCredentials' => $this->revoked($row) ? [['id' => 'urn:uuid:'.$row->credential_id, 'revocationReason' => self::REVOKED_REASON]] : [],
        ]);
    }

    /** @return array<string, mixed>|null */
    public function issuer(string $slug): ?array
    {
        $school = School::query()->where('slug', $slug)->first();
        if ($school === null || ! $school->isActive()) {
            return null;
        }

        return ['@context' => BadgeDocuments::CONTEXT, 'id' => BadgeDocuments::issuerId($school), 'type' => ['Profile'], 'name' => (string) $school->name];
    }

    private function revoked(OpenBadgeCredential $row): bool
    {
        // A withdrawn award revokes its credential even if the revocation row was never written.
        return $row->revoked_at !== null || ! AwardPresentation::whereKey($row->award_presentation_id)->where('state', 'active')->exists();
    }

    private function current(stdClass $document): bool
    {
        $now = time();
        $from = is_string($document->validFrom ?? null) ? strtotime($document->validFrom) : false;
        $until = isset($document->validUntil) ? (is_string($document->validUntil) ? strtotime($document->validUntil) : false) : null;

        return $from !== false && $from <= $now + 300 && ($until === null || ($until !== false && $until >= $now));
    }

    private function recipient(stdClass $document, string $name): bool
    {
        foreach ((array) ($document->credentialSubject->identifier ?? []) as $identity) {
            if (is_object($identity) && ($identity->identityType ?? null) === 'name' && ($identity->hashed ?? null) === true && is_string($identity->salt ?? null)
                && is_string($identity->identityHash ?? null) && hash_equals($identity->identityHash, BadgeDocuments::recipientHash($name, $identity->salt))) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string|null> */
    private function summary(stdClass $document): array
    {
        $text = fn (mixed $value): ?string => is_string($value) ? mb_substr($value, 0, 500) : null;
        $achievement = $document->credentialSubject->achievement ?? null;

        return ['id' => $text($document->id ?? null), 'name' => $text($document->name ?? null), 'issuer' => $text($document->issuer->name ?? null),
            'achievement' => $text(is_object($achievement) ? ($achievement->name ?? null) : null), 'awarded' => $text($document->awardedDate ?? null),
            'valid_from' => $text($document->validFrom ?? null)];
    }
}
