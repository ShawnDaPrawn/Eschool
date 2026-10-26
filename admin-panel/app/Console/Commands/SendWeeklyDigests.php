<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Learning\WeeklyParentDigest;
use App\Models\Platform\School;
use App\Services\Learning\Digests\WeeklyDigests;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class SendWeeklyDigests extends Command
{
    protected $signature = 'digest:weekly {--retention : Erase expired private summary copies}';

    protected $description = 'Compose school-local Monday parent digests from approved language templates and school evidence';

    public function handle(): int
    {
        $failed = false;
        School::orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                app(CurrentSchool::class)->run((int) $school->id, function (): void {
                    if ($this->option('retention')) {
                        WeeklyParentDigest::where('expires_at', '<=', now())->delete();
                    } else {
                        app(WeeklyDigests::class)->monday();
                    }
                });
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [403, 404, 423], true)) {
                    $failed = true;
                    Log::warning('weekly_digest_retry_needed', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
                }
            } catch (Throwable $error) {
                $failed = true;
                Log::warning('weekly_digest_retry_needed', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
