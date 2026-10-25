<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationDocument;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\Admissions\Offer;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Retention continues when a feature or school is switched off. */
class IntakeRetention
{
    public function due(Application $row): bool
    {
        if ($row->state === 'accepted') {
            return false;
        }
        $intake = Intake::findOrFail($row->intake_id);
        $days = max(1, min((int) $intake->keep_for_days, (int) config('admissions.maximum_keep_days')));
        if ($row->state === 'enrolled') {
            return $row->enrolled_at !== null && $row->enrolled_at->addDays($days)->lessThanOrEqualTo(now());
        }
        if ($row->state === 'draft') {
            return $row->updated_at->toImmutable()->addDays($days)->lessThanOrEqualTo(now());
        }
        if ($row->state === 'offered') {
            $offer = Offer::where('application_id', $row->id)->where('state', 'offered')->orderByDesc('id')->first(['expires_at']);

            return $offer !== null && $offer->expires_at->addDays($days)->lessThanOrEqualTo(now());
        }
        if (in_array($row->state, ['rejected', 'withdrawn', 'offer_expired'], true)) {
            return $row->terminal_at !== null && $row->terminal_at->addDays($days)->lessThanOrEqualTo(now());
        }

        return in_array($row->state, ['submitted', 'waitlisted'], true) && $intake->closes_at->addDays($days)->lessThanOrEqualTo(now());
    }

    private function context(): int
    {
        $scope = app(CurrentSchool::class);
        abort_unless($scope->isSet() && ! $scope->isAcrossSchools() && ! $scope->isImpersonating(), 403);

        return $scope->id();
    }

    public function purge(int $id): bool
    {
        $school = $this->context();
        $initial = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at'])->find($id);
        if ($initial === null || ! $this->due($initial)) {
            return false;
        }

        return DB::transaction(function () use ($initial, $id, $school): bool {
            IntakeGrade::whereKey($initial->intake_grade_id)->lockForUpdate()->firstOrFail();
            $row = Application::select(['id', 'intake_id', 'intake_grade_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at'])->lockForUpdate()->find($id);
            abort_unless($this->context() === $school, 403);
            if ($row === null || ! $this->due($row) || Offer::where('application_id', $id)->where(fn ($q) => $q->where('state', 'accepted')->orWhere(fn ($o) => $o->where('state', 'offered')->where('expires_at', '>', now()->utc())))->exists()) {
                return false;
            }
            $prefix = app(CurrentSchool::class)->storagePath('applications').'/';
            foreach (ApplicationDocument::where('application_id', $id)->get(['path']) as $document) {
                if (! str_starts_with($document->path, $prefix) || str_contains($document->path, '..')) {
                    throw new RuntimeException('Private document retention needs review.');
                }
                // Delete bytes first: a failed private-store operation must not
                // remove the row that allows the next run to retry erasure.
                if (Storage::disk(ApplicationDocuments::DISK)->exists($document->path) && ! Storage::disk(ApplicationDocuments::DISK)->delete($document->path)) {
                    throw new RuntimeException('Private document retention is temporarily unavailable.');
                }
            }
            Audit::record('admissions.application_purged', $row, [], ['state' => $row->state]);
            // Cascades remove encrypted answers/contact, offers, resume tokens,
            // document references and encrypted application events together.
            $row->delete();

            return true;
        }, 3);
    }

    /** Stream bounded metadata batches; never decrypt orphan candidates. */
    public function orphanFiles(): int
    {
        $this->context();
        $prefix = app(CurrentSchool::class)->storagePath('applications');
        $disk = Storage::disk(ApplicationDocuments::DISK);
        $cutoff = now()->subDays(2)->getTimestamp();
        $batch = [];
        $removed = 0;
        $clean = function (array $entries) use ($disk, $prefix, $cutoff, &$removed): void {
            $paths = array_column($entries, 'path');
            $known = array_fill_keys(ApplicationDocument::whereIn('path', $paths)->pluck('path')->all(), true);
            foreach ($entries as $entry) {
                if ($removed >= 1000) {
                    break;
                }
                if (! isset($known[$entry['path']]) && $entry['modified'] !== null && $entry['modified'] < $cutoff
                    && str_starts_with($entry['path'], $prefix.'/') && ! str_contains($entry['path'], '..')) {
                    if (! $disk->delete($entry['path'])) {
                        throw new RuntimeException('Private orphan cleanup is temporarily unavailable.');
                    }
                    $removed++;
                }
            }
        };
        foreach ($disk->getDriver()->listContents($prefix, true) as $entry) {
            if (! $entry->isFile()) {
                continue;
            }
            $batch[] = ['path' => $entry->path(), 'modified' => $entry->lastModified()];
            if (count($batch) === 100) {
                $clean($batch);
                $batch = [];
            }
            if ($removed >= 1000) {
                break;
            }
        }
        if ($batch !== []) {
            $clean($batch);
        }

        return $removed;
    }
}
