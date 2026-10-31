<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class GrantPlatformAdminAccess extends Command
{
    protected $signature = 'platform:admin-access {user_id : An active platform operator} {--hours=24 : Expiry from now, 1 to 8760 hours} {--role= : owner, operations, support, finance, content or sales (default: unchanged, owner for a new grant)} {--revoke}';

    protected $description = 'Grant or revoke expiring access to the platform admin area (/saas) from the trusted operator console';

    public function handle(): int
    {
        abort_unless(app()->runningInConsole(), 403);
        $id = (string) $this->argument('user_id');
        $hours = (string) $this->option('hours');

        return app(CurrentSchool::class)->acrossSchools(function () use ($id, $hours): int {
            if (! ctype_digit($id) || ! ctype_digit($hours) || (int) $hours < 1 || (int) $hours > 8760
                || ! User::query()->where('status', 1)->whereKey((int) $id)->exists()
                || ! PlatformStaffIdentity::query()->where('user_id', (int) $id)->where('active', true)->exists()) {
                $this->error('Choose an active platform operator (see platform:staff-identity) and a valid expiry.');

                return self::FAILURE;
            }
            $role = $this->option('role');
            if ($role !== null && ! array_key_exists((string) $role, (array) config('platform_roles'))) {
                $this->error('Choose a role from config/platform_roles.php.');

                return self::FAILURE;
            }
            // Changes are audited by the model (who is "system": the console).
            $values = ['active' => ! $this->option('revoke'), 'expires_at' => now()->utc()->addHours((int) $hours)];
            PlatformAdminGrant::query()->updateOrCreate(['user_id' => (int) $id], $role === null ? $values : $values + ['role' => (string) $role]);
            $this->info($this->option('revoke')
                ? 'Platform admin access revoked.'
                : "Platform admin access granted for {$hours} hours. No school roles or permissions were granted.");

            return self::SUCCESS;
        });
    }
}
