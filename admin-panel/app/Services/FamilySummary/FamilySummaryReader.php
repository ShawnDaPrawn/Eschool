<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Family\FamilyDailySummary;
use App\Models\Family\FamilyDailySummaryPart;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Reading a daily family summary (Action Plan 5.11). Each school's part is
 * read inside that school and shown only while the reader is still that
 * school's guardian of every child the part is about: a guardian removed at
 * a school since the summary was written no longer sees that school's part.
 * Another person's summary, an expired one and one with nothing left all
 * look the same: not found.
 */
class FamilySummaryReader
{
    public function show(User $actor, int $id): array
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && ! $school->isSupportSession(), 403);
        // The reader must be a guardian where they are signed in, as for every family read.
        app(FamilySummaryAuthority::class)->guardian($actor);
        $summary = FamilyDailySummary::query()->where('user_id', $actor->id)->where('state', FamilyDailySummary::SENT)->where('expires_at', '>', now())->findOrFail($id);
        $schools = FamilyDailySummaryPart::acrossSchools()->where('family_daily_summary_id', $summary->id)->where('user_id', $actor->id)
            ->orderBy('school_id')->pluck('school_id')->map(fn ($schoolId) => (int) $schoolId)->unique()->values();
        $sections = [];
        foreach ($schools as $schoolId) {
            try {
                $section = $school->run($schoolId, function () use ($actor, $summary): array {
                    $authority = app(FamilySummaryAuthority::class);
                    $fresh = $authority->guardian($actor);
                    $part = FamilyDailySummaryPart::query()->where('family_daily_summary_id', $summary->id)->where('user_id', $fresh->id)->firstOrFail();
                    foreach ((array) $part->learner_ids as $learnerId) {
                        $authority->child($fresh, (int) $learnerId);
                    }

                    return ['school_id' => app(CurrentSchool::class)->id(), 'school_name' => (string) ($part->facts['school'] ?? ''), 'text' => (string) $part->text,
                        'wording' => (string) $part->wording, 'unread_messages' => (int) ($part->facts['unread_messages'] ?? 0), 'private_updates' => (int) ($part->facts['private_updates'] ?? 0)];
                });
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
            $sections[] = $section;
        }
        abort_if($sections === [], 404);

        return ['id' => (int) $summary->id, 'date' => $summary->summary_date->toDateString(), 'title' => __('Your daily family summary'),
            'sections' => $sections, 'private_updates' => array_sum(array_column($sections, 'private_updates')), 'notifications_target' => '/notifications'];
    }
}
