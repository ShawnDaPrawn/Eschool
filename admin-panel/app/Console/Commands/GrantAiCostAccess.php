<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Console\Command;

class GrantAiCostAccess extends Command
{
    protected $signature = 'platform:ai-cost-access {user_id} {--hours=24 : Expiry from now, 1 to 8760 hours} {--revoke}';

    protected $description = 'Provision an expiring cost-only grant from the trusted operator console';

    public function handle(): int
    {
        abort_unless(app()->runningInConsole(), 403);
        $id = (string) $this->argument('user_id');
        $hours = (string) $this->option('hours');
        if (! ctype_digit($id) || ! ctype_digit($hours) || (int) $hours < 1 || (int) $hours > 8760 || ! User::where('status', 1)->whereKey((int) $id)->exists()
            || ! PlatformStaffIdentity::where('user_id', (int) $id)->where('active', true)->exists()) {
            $this->error('Choose an active platform operator and a valid expiry.');

            return self::FAILURE;
        }
        $grant = PlatformAiCostGrant::updateOrCreate(['user_id' => (int) $id], ['active' => ! $this->option('revoke'), 'expires_at' => now()->addHours((int) $hours)]);
        Audit::record('ai.platform_cost_access_changed', $grant, [], ['active' => $grant->active, 'expires_at' => $grant->expires_at->toIso8601String()]);
        $this->info('Cost-only access updated. No school or learner-content permissions were granted.');

        return self::SUCCESS;
    }
}
