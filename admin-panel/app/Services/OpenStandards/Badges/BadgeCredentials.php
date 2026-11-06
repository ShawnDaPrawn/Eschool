<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use App\Models\OpenStandards\OpenBadgeCredential;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\RecognitionBadge;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\RecognitionAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Open Badges 3.0 credentials for recognition awards (Action Plan 5.9). A
 * learner's guardians, and the learner themself once their age stage allows
 * it (config open_standards.open_badges.learner_stages), download the signed
 * credential of each of the learner's current awards. The credential is
 * issued on its first download and is the same document on every later one.
 * Withdrawing the award revokes it. engineering/specs/platform/open-standards.md.
 */
class BadgeCredentials
{
    public const SELF_REFUSED = 'Ask your parent or guardian to download your badges.';

    public function __construct(private readonly BadgeDocuments $documents, private readonly BadgeSigningKeys $keys) {}

    /**
     * The learner's current awards and whether each has a credential yet.
     *
     * @return array{available: bool, items: list<array{presentation_id: int, title: string, presented_on: string, kind: string, issued: bool}>}
     */
    public function forLearner(User $actor, int $learner): array
    {
        $this->authorise($actor, $learner);
        $rows = AwardPresentation::where('learner_id', $learner)->where('state', 'active')->orderByDesc('presented_on')->orderByDesc('id')->limit(200)->get();
        $issued = OpenBadgeCredential::whereIn('award_presentation_id', $rows->pluck('id'))->whereNull('revoked_at')->pluck('award_presentation_id')->map(fn ($id) => (int) $id)->all();

        return ['available' => $this->keys->configured(), 'items' => $rows->map(fn (AwardPresentation $p) => [
            'presentation_id' => (int) $p->id, 'title' => (string) $p->title, 'presented_on' => $p->presented_on->toDateString(),
            'kind' => $p->source_kind === 'badge' ? 'badge' : 'award', 'issued' => in_array((int) $p->id, $issued, true),
        ])->values()->all()];
    }

    /**
     * The signed credential for one of the learner's current awards.
     *
     * @return array{document: array<string, mixed>, filename: string}
     */
    public function download(User $actor, int $learner, int $presentation): array
    {
        $child = $this->authorise($actor, $learner);
        abort_unless($this->keys->configured(), 503, __(BadgeSigningKeys::UNAVAILABLE));
        $row = AwardPresentation::where('learner_id', $child->id)->where('state', 'active')->findOrFail($presentation);
        $credential = $this->issue($row);
        $document = $this->documents->signed($credential, $this->claim($row), app(CurrentSchool::class)->school());
        Audit::record('open_badges.credential_downloaded', $credential, [], ['credential_id' => $credential->credential_id, 'learner_id' => (int) $child->id, 'award_presentation_id' => (int) $row->id]);

        return ['document' => $document, 'filename' => 'badge-'.Str::slug((string) $row->title).'-'.$row->presented_on->toDateString().'.json'];
    }

    /** The credential row of a current award, made on its first download. */
    public function issue(AwardPresentation $presentation): OpenBadgeCredential
    {
        return DB::transaction(function () use ($presentation): OpenBadgeCredential {
            // Lock the award first, then re-read: a concurrent withdrawal or first issue waits here.
            $award = AwardPresentation::lockForUpdate()->findOrFail($presentation->id);
            abort_unless($award->state === 'active', 404);
            $existing = OpenBadgeCredential::where('award_presentation_id', $award->id)->lockForUpdate()->first();
            if ($existing !== null) {
                abort_if($existing->revoked_at !== null, 410, __('This badge was withdrawn.'));

                return $existing;
            }
            $key = $this->keys->signing(app(CurrentSchool::class)->id());
            sodium_memzero($key['secret']);
            $row = OpenBadgeCredential::create(['award_presentation_id' => $award->id, 'learner_id' => $award->learner_id, 'credential_id' => (string) Str::uuid(),
                'key_id' => $key['key_id'], 'recipient_salt' => bin2hex(random_bytes(16)), 'issued_at' => now()->startOfSecond()]);
            Audit::record('open_badges.credential_issued', $row, [], ['credential_id' => $row->credential_id, 'learner_id' => (int) $award->learner_id, 'award_presentation_id' => (int) $award->id, 'key_id' => $key['key_id']]);

            return $row;
        });
    }

    /** Called inside the award's withdrawal transaction: its credential is revoked with it. */
    public function awardWithdrawn(AwardPresentation $presentation, User $actor): void
    {
        $row = OpenBadgeCredential::where('award_presentation_id', $presentation->id)->whereNull('revoked_at')->lockForUpdate()->first();
        if ($row === null) {
            return;
        }
        $row->update(['revoked_at' => now(), 'revoked_by' => $actor->id, 'revocation_code' => 'award_withdrawn']);
        Audit::record('open_badges.credential_revoked', $row, [], ['credential_id' => $row->credential_id, 'award_presentation_id' => (int) $presentation->id, 'revocation_code' => 'award_withdrawn']);
    }

    /**
     * What the credential of a recognition award says. This is the seam the
     * learner passport (#449) uses for its own achievements.
     */
    public function claim(AwardPresentation $presentation): AchievementClaim
    {
        $school = app(CurrentSchool::class);
        $timezone = (string) ($school->school()?->timezone ?: 'Africa/Johannesburg');
        $base = rtrim((string) config('app.url'), '/').'/open-badges/schools/'.$school->id();
        $badge = $presentation->source_kind === 'badge' ? RecognitionBadge::where('award_presentation_id', $presentation->id)->value('kind') : null;
        $achievement = match (true) {
            $presentation->award_definition_id !== null => $base.'/award-definitions/'.$presentation->award_definition_id,
            $badge !== null => $base.'/badges/'.$badge,
            default => $base.'/awards/'.$presentation->id,
        };
        $learner = Students::with('user')->findOrFail($presentation->learner_id);

        return new AchievementClaim(
            achievementId: 'urn:uuid:'.Uuid::uuid5(Uuid::NAMESPACE_URL, $achievement)->toString(),
            achievementType: $presentation->source_kind === 'badge' ? 'Badge' : 'Award',
            name: (string) $presentation->title,
            description: $presentation->outside_organisation !== null ? __('Awarded by :organisation', ['organisation' => $presentation->outside_organisation]) : (string) $presentation->title,
            criteria: (string) $presentation->criteria,
            awardedOn: CarbonImmutable::parse($presentation->presented_on->toDateString(), $timezone),
            recipientName: (string) ($learner->user?->full_name ?? ''),
        );
    }

    /**
     * A guardian of the learner, or the learner themself when their age stage
     * allows it. The awards module must be on; other schools' learners are not found.
     */
    private function authorise(User $actor, int $learner): Students
    {
        $child = app(RecognitionAuthority::class)->family($actor, $learner, 'awards');
        if ((int) $child->user_id === (int) $actor->id) {
            $stages = (array) config('open_standards.open_badges.learner_stages', []);
            abort_unless(in_array(app(SchoolStages::class)->forLearner($child), $stages, true), 403, __(self::SELF_REFUSED));
        }

        return $child;
    }
}
