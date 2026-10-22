<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Platform\Secrets\SecretSettings;
use Illuminate\Database\QueryException;
use PDOException;

/**
 * Applies the settings admins edit in the panel (school name, timezone, mail
 * server) on top of the config loaded from .env.
 *
 * Saving those settings used to copy them into .env as well. That goes stale
 * once config is cached (php artisan config:cache), so the settings table is
 * now the only place they are saved, and this runs on every boot.
 */
class SettingsConfigService
{
    /** Settings type => config key it overrides. */
    private const CONFIG_KEYS = [
        'school_name' => 'app.name',
        'mail_mailer' => 'mail.default',
        'mail_host' => 'mail.mailers.smtp.host',
        'mail_port' => 'mail.mailers.smtp.port',
        'mail_username' => 'mail.mailers.smtp.username',
        'mail_password' => 'mail.mailers.smtp.password',
        'mail_encryption' => 'mail.mailers.smtp.encryption',
        'mail_send_from' => 'mail.from.address',
    ];

    public static function apply(): void
    {
        try {
            $settings = getSettings();
        } catch (QueryException|PDOException) {
            // Not installed or not migrated yet: keep the .env values.
            return;
        }

        // .env.example sets MAIL_FROM_NAME="${APP_NAME}": keep the sender name
        // following the school name unless it was set to something else.
        $senderFollowsAppName = config('mail.from.name') === config('app.name');

        foreach (self::CONFIG_KEYS as $type => $key) {
            // A secret the environment provides (MAIL_PASSWORD) wins over its row.
            if (isset(SecretSettings::SECRETS[$type]) && SecretSettings::fromEnvironment($type)) {
                continue;
            }
            $value = trim((string) ($settings[$type] ?? ''));
            if ($value !== '') {
                config([$key => $type === 'mail_port' ? (int) $value : $value]);
            }
        }

        if ($senderFollowsAppName) {
            config(['mail.from.name' => config('app.name')]);
        }

        $timezone = trim((string) ($settings['time_zone'] ?? ''));
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }
}
