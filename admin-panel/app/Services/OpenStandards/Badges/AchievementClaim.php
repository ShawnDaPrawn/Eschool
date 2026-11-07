<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use Carbon\CarbonImmutable;

/**
 * What one Open Badges credential says a learner achieved, independent of
 * where it was recorded. Recognition awards are the first source
 * (BadgeCredentials::claim()); the learner passport (#449, not yet merged)
 * is meant to describe its achievements the same way and pass them to
 * BadgeDocuments, so every credential NavigateEd issues has one shape.
 */
final readonly class AchievementClaim
{
    /**
     * @param  string  $achievementId  a stable URI for the achievement (the award, not this learner's copy)
     * @param  string  $achievementType  an Open Badges AchievementType, such as Award or Badge
     */
    public function __construct(
        public string $achievementId,
        public string $achievementType,
        public string $name,
        public string $description,
        public string $criteria,
        public CarbonImmutable $awardedOn,
        public string $recipientName,
    ) {}
}
