<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Platform\BackfillPeopleIdentity;
use App\Models\Platform\School;
use Illuminate\Console\Command;

class BackfillPeople extends Command
{
    protected $signature = 'people:backfill-identities {--school= : Limit to one school} {--queue : Run on the database worker}';

    protected $description = 'Assign lifelong student numbers and record known year-boundary enrolments without inventing mid-year history';

    public function handle(): int
    {
        $schools = School::query()->when($this->option('school'), fn ($query, $id) => $query->whereKey((int) $id))->orderBy('id')->get();
        foreach ($schools as $school) {
            $job = new BackfillPeopleIdentity((int) $school->id);
            $this->option('queue') ? dispatch($job) : app()->call([$job, 'handle']);
            $this->line('School '.$school->id.($this->option('queue') ? ': queued' : ': back-filled'));
        }

        return self::SUCCESS;
    }
}
