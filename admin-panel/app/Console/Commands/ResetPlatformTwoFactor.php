<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Saas\PlatformTwoFactor;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Break-glass for a platform staff member's two-factor sign-in (SA.2): from
 * the trusted operator console only. This is how an Owner who lost their
 * phone, or the last Owner, gets back in; in HQ an Owner resets anyone else's.
 * Asks for a reason, which the audit log keeps (platform_two_factor_reset,
 * via console). engineering/specs/platform/hq-two-factor.md.
 */
class ResetPlatformTwoFactor extends Command
{
    protected $signature = 'platform:reset-two-factor {email : The platform staff member\'s sign-in address}';

    protected $description = 'Reset a platform staff member\'s two-factor sign-in from the trusted operator console (asks for a reason)';

    public function handle(PlatformTwoFactor $twoFactor): int
    {
        abort_unless(app()->runningInConsole(), 403);
        $email = strtolower(trim((string) $this->argument('email')));
        $user = app(CurrentSchool::class)->acrossSchools(fn (): ?User => User::query()->whereRaw('LOWER(email) = ?', [$email])->first());
        if ($user === null || ! PlatformStaffIdentity::query()->where('user_id', $user->id)->exists()) {
            $this->error('No platform staff member signs in with that address.');

            return self::FAILURE;
        }
        $reason = trim((string) $this->ask('Why is this reset needed? (10 to 500 characters, kept in the audit log)'));
        if (Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10', 'max:500']])->fails()) {
            $this->error('Give a reason of 10 to 500 characters. Nothing was changed.');

            return self::FAILURE;
        }
        if (! $this->confirm("Reset two-factor sign-in for {$user->email}? They are signed out everywhere and set it up again at their next sign-in.")) {
            $this->line('Nothing was changed.');

            return self::FAILURE;
        }
        try {
            $twoFactor->breakGlass($user, $reason);
        } catch (HttpException $e) {
            $this->error($e->getMessage() !== '' ? $e->getMessage() : 'Nothing was changed.');

            return self::FAILURE;
        }
        $this->info('Two-factor sign-in was reset and recorded in the audit log.');

        return self::SUCCESS;
    }
}
