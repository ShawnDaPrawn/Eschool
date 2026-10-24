<?php

declare(strict_types=1);

namespace App\Jobs\Academics;

use App\Models\Academics\ContentVersion;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Services\Academics\Content\ContentPipeline;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RecoverContentConversions implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(CurrentSchool $schools, ContentPipeline $pipeline): void
    {
        School::orderBy('id')->each(function ($school) use ($schools, $pipeline) {
            $schools->run((int) $school->id, function () use ($pipeline) {
                ContentVersion::whereIn('state', ['scanning', 'converting'])->where('lease_until', '<=', now())->orderBy('id')->limit(100)->get()->each(function ($v) use ($pipeline) {
                    DB::transaction(function () use ($v, $pipeline) {
                        School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                        $fresh = ContentVersion::whereIn('state', ['scanning', 'converting'])->where('lease_until', '<=', now())->find($v->id);
                        if ($fresh === null) {
                            return;
                        }
                        AiUsage::where('feature', 'content_transcription')->where('operation_id', $fresh->attempt_id)->where('state', 'reserved')
                            ->update(['state' => 'failed', 'used_units' => DB::raw('reserved_units'), 'reserved_units' => 0, 'failure_code' => 'content_worker_interrupted', 'completed_at' => now()]);
                        $pipeline->exhausted((int) $fresh->id, $fresh->generation_id);
                    });
                });
            });
        });
    }
}
