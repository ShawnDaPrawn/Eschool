<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportRecipient;
use App\Models\User;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\URL;

class ReportFamily
{
    public function list(User $actor, int $learner, int $page = 1): array
    {
        app(ReleasedMarks::class)->learner($actor, $learner);
        $cards = ReportCard::with('batch')->where('learner_id', $learner)->whereHas('batch', fn ($q) => $q->where('state', 'released')->whereColumn('report_batches.fingerprint', 'report_cards.generated_fingerprint'))->orderByDesc('id')->paginate(50, ['*'], 'page', $page);
        $read = ReportRecipient::where('user_id', $actor->id)->whereIn('report_card_id', $cards->pluck('id'))->get()->keyBy('report_card_id');

        return ['learner_id' => $learner, 'page' => $cards->currentPage(), 'last_page' => $cards->lastPage(), 'reports' => $cards->getCollection()->map(function ($card) use ($actor, $read) {
            $payload = $card->payload;

            return ['id' => (int) $card->id, 'batch_id' => (int) $card->report_batch_id, 'session_year_id' => (int) $payload['session_year_id'], 'term_id' => $payload['term_id'],
                'year_name' => $payload['year_name'], 'term_name' => $payload['term_name'], 'learner_name' => $payload['learner_name'], 'provisional' => $payload['provisional'],
                'released_at' => $card->batch->released_at->toISOString(), 'read_at' => $read->get($card->id)?->read_at?->toISOString(), 'download_url' => $this->url($actor, $card, false)];
        })->all()];
    }

    public function access(User $actor, int $id): array
    {
        $card = app(ReportPolicy::class)->family($actor, $id);
        abort_unless(app(ReportArtifacts::class)->available($card, $card->batch->fingerprint), 409, __('The published PDF needs restoration. Contact the school.'));

        return ['id' => $id, 'learner_id' => (int) $card->learner_id, 'download_url' => $this->url($actor, $card, false)];
    }

    public function url(User $actor, ReportCard $card, bool $staff): string
    {
        if ($staff) {
            app(ReportPolicy::class)->batch($actor, (int) $card->report_batch_id);
        } else {
            app(ReportPolicy::class)->family($actor, (int) $card->id);
        }

        return URL::temporarySignedRoute('api.v2.academics.reports.download', now()->addMinutes(10), ['card' => $card->id, 'school' => app(CurrentSchool::class)->id(), 'reader' => $actor->id, 'staff' => $staff ? 1 : 0]);
    }

    public function read(User $actor, int $id): array
    {
        $card = app(ReportPolicy::class)->family($actor, $id);
        $recipient = ReportRecipient::firstOrCreate(['report_card_id' => $card->id, 'user_id' => $actor->id]);
        if ($recipient->read_at === null) {
            $recipient->forceFill(['read_at' => now()])->save();
        }

        return ['id' => $id, 'read_at' => $recipient->read_at->toISOString()];
    }
}
