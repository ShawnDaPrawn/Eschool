<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class BackfillPeopleIdentity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId) {}

    public function handle(CurrentSchool $schools, StudentNumbers $numbers, EnrolmentService $enrolments): void
    {
        $schools->run($this->schoolId, function () use ($numbers, $enrolments) {
            Students::query()->withTrashed()->orderBy('id')->chunkById(100, fn ($learners) => $learners->each(fn (Students $learner) => $numbers->assign($learner)));
            StudentSessions::withoutStudentScope()->withTrashed()->whereNotNull('class_section_id')->orderBy('id')->chunkById(100, function ($sessions) use ($enrolments) {
                foreach ($sessions as $session) {
                    $enrolments->fromLegacy($session);
                }
            });
        });
    }
}
