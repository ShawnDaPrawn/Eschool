<?php

declare(strict_types=1);

namespace App\Jobs\Communication;

use App\Models\Communication\Message;
use App\Models\Platform\School;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecoverMessages implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 110;

    public function handle(CurrentSchool $schools): void
    {
        School::where('status', 'active')->orderBy('id')->each(function ($school) use ($schools) {
            $schools->run((int) $school->id, function () use ($school) {
                app(ModuleGate::class)->forget();
                if (! app(ModuleGate::class)->allows('communication')) {
                    return;
                }
                Message::where(function ($q) {
                    $q->where(fn ($pending) => $pending->where('state', 'pending')->whereColumn('review_completed_revision', '<', 'revision'))
                        ->orWhere(fn ($due) => $due->where('state', 'approved')->where(fn ($time) => $time->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now())))
                        ->orWhere('state', 'dispatching');
                })->orderBy('id')->chunkById(100, function ($rows) use ($school) {
                    foreach ($rows as $row) {
                        ProcessMessage::dispatch((int) $school->id, (int) $row->id);
                    }
                });
            });
        });
    }
}
