<?php

declare(strict_types=1);

namespace App\Services\Platform\Secrets;

use App\Models\Settings;
use Illuminate\Support\Facades\Storage;

/**
 * Secrets the panel used to keep in `settings` rows, now read from the
 * environment first (config/secrets.php; Secret Manager on Cloud Run). The
 * settings row stays a fallback for local installs that have no variable.
 * When the variable is set, the settings pages neither show nor save the
 * value, and `secrets:audit-settings --purge` clears the row.
 */
final class SecretSettings
{
    /**
     * settings type => [config key, environment variable].
     *
     * service_account_file is a path to the uploaded Firebase JSON on the
     * private disk; FCM_SERVICE_ACCOUNT_JSON carries the JSON itself.
     */
    public const SECRETS = [
        'mail_password' => ['secrets.mail_password', 'MAIL_PASSWORD'],
        'stripe_secret_key' => ['secrets.stripe_secret_key', 'STRIPE_SECRET_KEY'],
        'stripe_webhook_secret' => ['secrets.stripe_webhook_secret', 'STRIPE_WEBHOOK_SECRET'],
        'recaptcha_secret_key' => ['secrets.recaptcha_secret_key', 'RECAPTCHA_SECRET_KEY'],
        'service_account_file' => ['secrets.fcm_service_account', 'FCM_SERVICE_ACCOUNT_JSON'],
    ];

    /** Rows of the payment gateways removed in Phase 0: keys and secrets nothing reads any more. */
    public const RETIRED_PREFIXES = ['razorpay_', 'paystack_', 'flutterwave_'];

    /** Whether the environment provides this secret. */
    public static function fromEnvironment(string $type): bool
    {
        return trim((string) config(self::SECRETS[$type][0] ?? '', '')) !== '';
    }

    /** The secret: the environment's value, else the settings row, else ''. */
    public static function get(string $type): string
    {
        if (self::fromEnvironment($type)) {
            return trim((string) config(self::SECRETS[$type][0]));
        }

        return trim((string) (getSettings($type)[$type] ?? ''));
    }

    /**
     * Settings types a settings page may still save: those whose secret the
     * environment provides are left out.
     *
     * @param  list<string>  $types
     * @return list<string>
     */
    public static function editable(array $types): array
    {
        return array_values(array_filter($types, fn (string $type) => ! (isset(self::SECRETS[$type]) && self::fromEnvironment($type))));
    }

    /**
     * Validation rules without those for secrets the environment provides.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function rulesFor(array $rules): array
    {
        return array_intersect_key($rules, array_flip(self::editable(array_keys($rules))));
    }

    /**
     * The Firebase service account: the JSON from FCM_SERVICE_ACCOUNT_JSON,
     * else null (the uploaded file is used).
     *
     * @return array<string, mixed>|null
     */
    public static function fcmCredentials(): ?array
    {
        if (! self::fromEnvironment('service_account_file')) {
            return null;
        }
        $json = json_decode((string) config('secrets.fcm_service_account'), true);

        return is_array($json) && ! empty($json['private_key']) && ! empty($json['client_email']) ? $json : null;
    }

    /** The Firebase project: the FCM settings' project id, else the service account's. */
    public static function fcmProjectId(): ?string
    {
        $setting = trim((string) (getSettings('project_id')['project_id'] ?? ''));

        return $setting !== '' ? $setting : (self::fcmCredentials()['project_id'] ?? null);
    }

    /**
     * Each secret-bearing row: what is stored (never the value) and whether
     * the environment provides it.
     *
     * @return list<array{type: string, env: string|null, in_settings: bool, from_environment: bool, retired: bool}>
     */
    public static function audit(): array
    {
        $rows = Settings::query()->get(['type', 'message'])->keyBy('type');
        $report = [];

        foreach (self::SECRETS as $type => [, $env]) {
            $stored = trim((string) ($rows[$type]->message ?? '')) !== '';
            $report[] = ['type' => $type, 'env' => $env, 'in_settings' => $stored, 'from_environment' => self::fromEnvironment($type), 'retired' => false];
        }
        foreach ($rows as $type => $row) {
            if (self::isRetired((string) $type)) {
                $report[] = ['type' => (string) $type, 'env' => null, 'in_settings' => trim((string) $row->message) !== '', 'from_environment' => false, 'retired' => true];
            }
        }

        return $report;
    }

    /**
     * Clears the rows that are no longer needed: secrets the environment
     * provides (the Firebase file too) and every row of the removed gateways.
     *
     * @return list<string> the types cleared
     */
    public static function purge(): array
    {
        $cleared = [];
        foreach (self::audit() as $row) {
            if (! $row['retired'] && ! ($row['in_settings'] && $row['from_environment'])) {
                continue;
            }
            if ($row['type'] === 'service_account_file') {
                $file = (string) Settings::where('type', 'service_account_file')->value('message');
                foreach (['local', 'public'] as $disk) {
                    if ($file !== '' && Storage::disk($disk)->exists($file)) {
                        Storage::disk($disk)->delete($file);
                    }
                }
            }
            Settings::where('type', $row['type'])->delete();
            $cleared[] = $row['type'];
        }

        return $cleared;
    }

    private static function isRetired(string $type): bool
    {
        foreach (self::RETIRED_PREFIXES as $prefix) {
            if (str_starts_with($type, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
