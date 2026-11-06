<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use App\Models\OpenStandards\OpenBadgeCredential;
use App\Models\Platform\School;
use Carbon\CarbonImmutable;

/**
 * Builds and signs the Open Badges 3.0 credential (a W3C Verifiable
 * Credential, JSON-LD) for an issued credential row and its claim. The
 * recipient is identified by a salted hash of their name, never in plain
 * text, and the LURITS number is never part of a credential.
 */
class BadgeDocuments
{
    public const CONTEXT = ['https://www.w3.org/ns/credentials/v2', 'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json'];

    public const STATUS_TYPE = '1EdTechRevocationList';

    public function __construct(private readonly BadgeSigningKeys $keys, private readonly DataIntegrityProof $proof) {}

    /** @return array<string, mixed> the signed credential */
    public function signed(OpenBadgeCredential $row, AchievementClaim $claim, School $school): array
    {
        $key = $this->keys->signing((int) $school->id);
        try {
            return $this->proof->sign($this->unsigned($row, $claim, $school), $key['secret'], $key['verification_method'], $this->instant($row->issued_at));
        } finally {
            sodium_memzero($key['secret']);
        }
    }

    /** @return array<string, mixed> */
    public function unsigned(OpenBadgeCredential $row, AchievementClaim $claim, School $school): array
    {
        return [
            '@context' => self::CONTEXT,
            'id' => 'urn:uuid:'.$row->credential_id,
            'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
            'issuer' => ['id' => self::issuerId($school), 'type' => ['Profile'], 'name' => (string) $school->name],
            'validFrom' => $this->instant($row->issued_at),
            'name' => $claim->name,
            'credentialSubject' => [
                'type' => ['AchievementSubject'],
                'identifier' => [[
                    'type' => 'IdentityObject', 'identityType' => 'name', 'hashed' => true,
                    'identityHash' => self::recipientHash($claim->recipientName, (string) $row->recipient_salt), 'salt' => (string) $row->recipient_salt,
                ]],
                'achievement' => [
                    'id' => $claim->achievementId, 'type' => ['Achievement'], 'achievementType' => $claim->achievementType,
                    'name' => $claim->name, 'description' => $claim->description, 'criteria' => ['narrative' => $claim->criteria],
                ],
            ],
            'awardedDate' => $this->instant($claim->awardedOn->setTimezone((string) ($school->timezone ?: 'Africa/Johannesburg'))->startOfDay()),
            'credentialStatus' => ['id' => route('open-badges.status', ['credential' => $row->credential_id]), 'type' => self::STATUS_TYPE],
        ];
    }

    public static function issuerId(School $school): string
    {
        return route('open-badges.issuer', ['schoolSlug' => $school->slug]);
    }

    /** The Open Badges salted SHA-256 of a recipient's name, trimmed and in lower case. */
    public static function recipientHash(string $name, string $salt): string
    {
        return 'sha256$'.hash('sha256', mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name)).$salt);
    }

    private function instant(\DateTimeInterface $at): string
    {
        return CarbonImmutable::instance($at)->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
