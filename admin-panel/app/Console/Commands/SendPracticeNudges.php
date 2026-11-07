<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Learning\Motivation\PracticeNudges;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Action Plan 3.13: gentle practice nudges, each school in its own context. Both nudges start off. */
class SendPracticeNudges extends Command
{
    protected $signature = 'practice:nudges';

    protected $description = 'Send gentle practice reminders where a school has switched them on (school days, afternoons, never in quiet hours, once a week)';

    public function handle(): int
    {
        $failed = false;
        School::query()->active()->orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                app(CurrentSchool::class)->run((int) $school->id, fn () => app(PracticeNudges::class)->run());
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [403, 404, 423], true)) {
                    $failed = true;
                    Log::warning('practice_nudges_retry_needed', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
                }
            } catch (Throwable $error) {
                $failed = true;
                Log::warning('practice_nudges_retry_needed', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
