<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Secrets\SecretSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Lists the `settings` rows that hold secrets and whether the environment
 * (Secret Manager) provides each one. Values are never printed.
 *
 * --purge clears the rows that are no longer needed: a secret whose
 * environment variable is set (the uploaded Firebase file is deleted too),
 * and every row of the removed Razorpay, Paystack and Flutterwave gateways.
 * A secret only in `settings` is kept, so nothing stops working.
 */
class AuditSettingsSecrets extends Command
{
    protected $signature = 'secrets:audit-settings {--purge : Clear secret rows whose environment value is set, and the removed gateways\' rows}';

    protected $description = 'List secret-bearing settings rows (never their values) and optionally clear those the environment replaces';

    public function handle(): int
    {
        $rows = [];
        foreach (SecretSettings::audit() as $row) {
            $rows[] = [
                $row['type'],
                $row['env'] ?? '-',
                $row['in_settings'] ? 'yes' : 'no',
                $row['retired'] ? '-' : ($row['from_environment'] ? 'yes' : 'no'),
                $this->advice($row),
            ];
        }
        $this->table(['Settings row', 'Environment variable', 'Value in settings', 'Set in environment', 'What to do'], $rows);

        if (! $this->option('purge')) {
            return self::SUCCESS;
        }

        $cleared = SecretSettings::purge();
        Log::info('Cleared secret settings rows', ['types' => $cleared]);
        $this->info($cleared === [] ? 'Nothing to clear.' : 'Cleared: '.implode(', ', $cleared).'.');

        return self::SUCCESS;
    }

    /**
     * @param  array{type: string, env: string|null, in_settings: bool, from_environment: bool, retired: bool}  $row
     */
    private function advice(array $row): string
    {
        return match (true) {
            $row['retired'] => 'removed gateway: --purge clears it',
            $row['in_settings'] && $row['from_environment'] => '--purge clears the row',
            $row['in_settings'] => 'set '.$row['env'].', then --purge',
            $row['from_environment'] => 'done',
            default => 'not configured',
        };
    }
}
