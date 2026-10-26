<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FamilyForms\ConsentRequest;
use App\Models\Platform\School;
use App\Services\FamilyForms\ConsentNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RecoverConsentNotices extends Command
{
    protected $signature = 'consents:notify-open {--limit=200 : Maximum open requests per school}';

    protected $description = 'Recover pending generic consent notices under current guardian and event authority';

    public function handle(): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5000]]);
        if ($limit === false) {
            $this->error('Use a limit from 1 to 5000.');

            return self::FAILURE;
        }
        $failed = false;
        School::active()->orderBy('id')->each(function (School $school) use ($limit, &$failed): void {
            try {
                app(CurrentSchool::class)->run((int) $school->id, function () use ($limit): void {
                    $key = 'consent-notice-cursor:'.app(CurrentSchool::class)->id();
                    $cursor = Cache::get($key);
                    $query = ConsentRequest::where('status', 'open')->where('respond_by', '>', now()->utc())
                        ->where('session_year_id', (int) schoolSetting('session_year'))->orderBy('id')->select('id');
                    $rows = (clone $query)->when(is_string($cursor) && Str::isUuid($cursor), fn ($q) => $q->where('id', '>', $cursor))->limit($limit)->get();
                    if ($rows->isEmpty() && $cursor !== null) {
                        $rows = $query->limit($limit)->get();
                    }
                    foreach ($rows as $request) {
                        app(ConsentNotices::class)->requested($request->id);
                    }
                    if ($rows->count() === $limit) {
                        Cache::put($key, $rows->last()->id, now()->addDays(7));
                    } else {
                        Cache::forget($key);
                    }
                });
            } catch (Throwable $error) {
                $failed = true;
                Log::warning('Consent notice recovery needs retry.', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
