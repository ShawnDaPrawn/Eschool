<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class PlatformStaffIdentityCommand extends Command
{
    protected $signature = 'platform:staff-identity {user_id : Existing global user ID} {--revoke : End the platform-staff exclusion identity}';

    protected $description = 'Register or revoke a platform staff identity from the trusted operator console';

    public function handle(): int
    {
        abort_unless(app()->runningInConsole(), 403);
        $id = (string) $this->argument('user_id');
        if (! ctype_digit($id) || (int) $id < 1) {
            $this->error('Choose an existing active user ID.');

            return self::FAILURE;
        }

        return app(CurrentSchool::class)->acrossSchools(function () use ($id): int {
            $user = User::query()->where('status', 1)->find((int) $id);
            if ($user === null) {
                $this->error('Choose an existing active user ID.');

                return self::FAILURE;
            }
            PlatformStaffIdentity::query()->updateOrCreate(['user_id' => $user->id], ['active' => ! (bool) $this->option('revoke')]);
            $this->info('Platform staff identity updated; no school permissions were granted.');

            return self::SUCCESS;
        });
    }
}
