<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use InvalidArgumentException;

/**
 * FCM topic names for a school-wide push (the Build Bible, section 7, step 1):
 * school_{id}_{audience}_{platform}, for example school_2_parents_ios, so a
 * notice reaches only its own school's apps.
 *
 * School 1 also sends to the topics apps subscribed to before Phase 1
 * (allAndroid, parentsIOS and so on), so installed apps keep receiving its
 * notices. Those legacy names can be dropped once every app version still
 * in use subscribes to the per-school topics: when the apps that do (stream
 * T5) are the minimum version the API forces (force_app_update), and the
 * request logs show no older version for a full term.
 */
final class PushTopics
{
    public const AUDIENCES = ['all', 'students', 'parents', 'teachers'];

    public const PLATFORMS = ['android', 'ios'];

    /** The school whose notices also go to the pre-Phase 1 topic names. */
    public const LEGACY_SCHOOL_ID = 1;

    /**
     * Every topic a push to this audience at this school goes to, each with
     * its platform (Android gets data only, iOS a notification block).
     *
     * @return list<array{topic: string, platform: string}>
     */
    public static function for(int $schoolId, string $audience): array
    {
        if (! in_array($audience, self::AUDIENCES, true)) {
            throw new InvalidArgumentException("Unknown push audience '{$audience}'");
        }

        $topics = [];
        foreach (self::PLATFORMS as $platform) {
            $topics[] = ['topic' => self::name($schoolId, $audience, $platform), 'platform' => $platform];
        }
        if ($schoolId === self::LEGACY_SCHOOL_ID) {
            $topics[] = ['topic' => $audience.'Android', 'platform' => 'android'];
            $topics[] = ['topic' => $audience.'IOS', 'platform' => 'ios'];
        }

        return $topics;
    }

    public static function name(int $schoolId, string $audience, string $platform): string
    {
        return "school_{$schoolId}_{$audience}_{$platform}";
    }

    private function __construct() {}
}
