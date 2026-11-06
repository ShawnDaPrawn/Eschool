<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\SchoolBusiness\BursaryApplication;
use App\Models\SchoolBusiness\BursaryEvent;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/**
 * The bursary retention rule. Household details, committee reasons and
 * documents are erased bursary_keep_days after a decision or withdrawal,
 * and bursary_draft_days after an unsent draft last changed. A submitted
 * application waiting for the committee is kept. An erased draft becomes
 * expired: it cannot be changed or sent, and the guardian can start a new
 * application for the child and year. The stub row (state, award and its
 * fee discount) and the reason-free audit trail remain.
 * Runs whether or not the module is on or the school is active.
 */
class BursaryRetention
{
    public function due(BursaryApplication $row): bool
    {
        if ($row->erased_at !== null) {
            return false;
        }
        $keep = max(1, (int) config('school_business.bursary_keep_days'));
        $draft = max(1, (int) config('school_business.bursary_draft_days'));

        return match ($row->state) {
            'draft' => $row->updated_at->addDays($draft)->lessThanOrEqualTo(now()),
            'awarded', 'declined' => $row->decided_at !== null && $row->decided_at->addDays($keep)->lessThanOrEqualTo(now()),
            'withdrawn' => $row->updated_at->addDays($keep)->lessThanOrEqualTo(now()),
            default => false,
        };
    }

    public function purge(int $id): bool
    {
        $scope = app(CurrentSchool::class);
        abort_unless($scope->isSet() && ! $scope->isAcrossSchools() && ! $scope->isImpersonating(), 403);
        $initial = BursaryApplication::select(['id', 'state', 'decided_at', 'updated_at', 'erased_at'])->find($id);
        if ($initial === null || ! $this->due($initial)) {
            return false;
        }

        return DB::transaction(function () use ($id): bool {
            $row = BursaryApplication::lockForUpdate()->find($id);
            if ($row === null || ! $this->due($row)) {
                return false;
            }
            app(BursaryDocuments::class)->erase($row);
            BursaryEvent::where('application_id', $row->id)->whereNotNull('reasons')->update(['reasons' => null]);
            // An unsent draft cannot be completed without its details: it
            // expires, which also frees the child's year for a new application.
            $row->forceFill(['household' => null, 'erased_at' => now('UTC'), 'state' => $row->state === 'draft' ? 'expired' : $row->state])->save();
            Audit::record('bursary.erased', $row, [], ['state' => $row->state]);

            return true;
        }, 3);
    }

    /** One school's due applications; returns how many were erased. */
    public function run(): int
    {
        $erased = 0;
        BursaryApplication::select(['id'])->whereNull('erased_at')->orderBy('id')->chunkById(100, function ($rows) use (&$erased): void {
            foreach ($rows as $row) {
                $erased += $this->purge((int) $row->id) ? 1 : 0;
            }
        });

        return $erased;
    }
}
