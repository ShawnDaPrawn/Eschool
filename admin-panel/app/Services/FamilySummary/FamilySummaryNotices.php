<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Family\FamilyDailySummary;
use App\Models\User;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The family.daily_summary notice (Action Plan 5.11) is private: it only says
 * the summary is ready, and reaches only the guardian it was written for,
 * in the school it was filed in, while the summary is kept.
 */
class FamilySummaryNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    private function summaries(User $actor): Builder
    {
        app(FamilySummaryAuthority::class)->guardian($actor);

        return FamilyDailySummary::query()->where('user_id', $actor->id)->where('notice_school_id', app(CurrentSchool::class)->id())
            ->where('state', FamilyDailySummary::SENT)->where('expires_at', '>', now());
    }

    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->where('type', FamilySummaryFacts::TYPE)->whereIn('id', $this->summaries($actor)->whereNotNull('notification_id')->select('notification_id'));
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $id = $payload['summary_id'] ?? null;
        if ($type !== FamilySummaryFacts::TYPE || (! is_int($id) && ! is_string($id)) || ! preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            return false;
        }
        try {
            return $this->summaries($actor)->whereKey((int) $id)->exists();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
