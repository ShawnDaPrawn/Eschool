<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Models\Academics\AcademicExitPackage;
use App\Models\Platform\School;
use App\Services\Academics\Transitions\ExitArtifacts;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Only explicit safe copy expiry is supported; educational source rules retain. */
class ApplyRetention implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(CurrentSchool $schools, ExitArtifacts $artifacts): void
    {
        School::query()->orderBy('id')->each(function (School $school) use ($schools, $artifacts) {
            $schools->run((int) $school->id, function () use ($artifacts) {
                AcademicExitPackage::where('state', '!=', 'expired')->where('expires_at', '<=', now())->select('id')->chunkById(100, function ($rows) use ($artifacts) {
                    foreach ($rows as $item) {
                        DB::transaction(function () use ($item, $artifacts) {
                            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                            $row = AcademicExitPackage::where('expires_at', '<=', now())->where('state', '!=', 'expired')->lockForUpdate()->find($item->id);
                            if ($row === null) {
                                return;
                            }
                            if ($row->artifact_path !== null) {
                                if ($artifacts->validPath($row)) {
                                    if (! Storage::disk(ExitArtifacts::DISK)->delete($row->artifact_path)) {
                                        return;
                                    }
                                } else {
                                    Log::warning('exit_package_retention_path_invalid', ['package_id' => (int) $row->id]);
                                }
                            }
                            $row->forceFill(['state' => 'expired', 'blueprint' => null, 'manifest' => null, 'artifact_path' => null, 'failure_code' => null])->save();
                            Audit::record('academics.exit_package_expired', $row, [], ['retained_receipt' => true]);
                        });
                    }
                });
            });
        });
        $root = $artifacts->scratchRoot();
        foreach (glob($root.'/exit-*') ?: [] as $path) {
            if (! is_link($path) && is_file($path) && filemtime($path) < now()->subHour()->getTimestamp()) {
                unlink($path);
            }
        }
    }
}
