<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\People\Profiles\LegacyProfileBackfill;
use Illuminate\Console\Command;

class BackfillLegacyProfiles extends Command
{
    protected $signature = 'profiles:backfill-legacy
        {--school= : Required numeric school ID}
        {--profile= : Required learner, parent or teacher}
        {--after=0 : Source record ID cursor for this profile}
        {--limit=500 : Maximum source rows, 1 to 1000}
        {--files : Validate and copy supported local legacy files into private storage}
        {--apply : Insert missing encrypted values; otherwise dry run}';

    protected $description = 'Rehearse or apply a bounded legacy profile backfill without overwriting existing values';

    public function handle(LegacyProfileBackfill $backfill): int
    {
        $school = (string) $this->option('school');
        $after = (string) $this->option('after');
        $limit = (string) $this->option('limit');
        $profile = (string) $this->option('profile');
        if (! ctype_digit($school) || (int) $school < 1 || ! School::query()->whereKey($school)->exists()
            || ! in_array($profile, ['learner', 'parent', 'teacher'], true)
            || ! ctype_digit($after) || ! ctype_digit($limit) || (int) $limit < 1 || (int) $limit > 1000) {
            $this->error('Select an existing --school, --profile=learner|parent|teacher, nonnegative --after and --limit=1..1000.');

            return self::INVALID;
        }

        $report = $backfill->run((int) $school, $profile, (int) $after, (int) $limit, (bool) $this->option('apply'), (bool) $this->option('files'));
        $this->line(json_encode($report, JSON_THROW_ON_ERROR));

        return $report['issue_count'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
