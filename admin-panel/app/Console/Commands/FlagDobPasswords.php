<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Flags learners whose password is still their date of birth (in any format
 * the seed used) for a forced change: users.password_change_required. The v1
 * sign-in then reports password_change_required: true, and clients that can
 * ask for a new password do.
 *
 * Idempotent: learners already flagged are skipped, so running it again
 * flags only new matches. Works in chunks, since each check is a bcrypt
 * comparison.
 */
class FlagDobPasswords extends Command
{
    protected $signature = 'learners:flag-dob-passwords
        {--chunk=200 : Learners read per query}
        {--dry-run : Count the matches without flagging them}';

    protected $description = 'Flag learners whose password is still their date of birth for a forced password change';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $flagged = 0;

        User::learners()
            ->where('password_change_required', false)
            ->whereNotNull('dob')
            ->select(['id', 'password', 'dob', 'password_change_required'])
            ->chunkById($chunk, function ($learners) use (&$checked, &$flagged, $dryRun) {
                foreach ($learners as $learner) {
                    $checked++;
                    if (! DateOfBirthPasswords::stillInUse($learner)) {
                        continue;
                    }
                    $flagged++;
                    if (! $dryRun) {
                        User::whereKey($learner->id)->update(['password_change_required' => true]);
                    }
                }
            });

        $already = User::learners()->where('password_change_required', true)->count();
        Log::info('Checked learner passwords against dates of birth', ['checked' => $checked, 'flagged' => $flagged, 'dry_run' => $dryRun]);
        $this->info(($dryRun ? 'Would flag' : 'Flagged')." {$flagged} of {$checked} learner(s) checked; {$already} learner(s) now must change their password.");

        return self::SUCCESS;
    }
}
