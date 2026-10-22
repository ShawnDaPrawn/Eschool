<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Settings;
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
 * comparison. A full run (not --dry-run) records the settings row
 * COMPLETED_SETTING; with --if-not-done it does nothing once that row exists,
 * which is how the schedule runs it once after the release.
 */
class FlagDobPasswords extends Command
{
    public const COMPLETED_SETTING = 'learner_dob_password_scan_completed_at';

    protected $signature = 'learners:flag-dob-passwords
        {--chunk=200 : Learners read per query}
        {--dry-run : Count the matches without flagging them}
        {--if-not-done : Do nothing when a full run has already completed}';

    protected $description = 'Flag learners whose password is still their date of birth for a forced password change';

    public function handle(): int
    {
        if ($this->option('if-not-done') && self::completed()) {
            $this->info('Learner passwords were already checked against dates of birth.');

            return self::SUCCESS;
        }

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

        if (! $dryRun) {
            Settings::updateOrCreate(['type' => self::COMPLETED_SETTING, 'school_id' => null], ['message' => now()->toIso8601String()]);
        }

        $already = User::learners()->where('password_change_required', true)->count();
        Log::info('Checked learner passwords against dates of birth', ['checked' => $checked, 'flagged' => $flagged, 'dry_run' => $dryRun]);
        $this->info(($dryRun ? 'Would flag' : 'Flagged')." {$flagged} of {$checked} learner(s) checked; {$already} learner(s) now must change their password.");

        return self::SUCCESS;
    }

    /** Whether a full run has completed (the settings row exists). */
    public static function completed(): bool
    {
        return Settings::where('type', self::COMPLETED_SETTING)->whereNull('school_id')->exists();
    }
}
