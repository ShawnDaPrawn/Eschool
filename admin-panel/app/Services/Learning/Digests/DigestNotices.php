<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\User;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DigestNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->where('type', 'digest.weekly')->whereIn('id', app(DigestQueries::class)->owned($actor)->whereNotNull('notification_id')->select('notification_id'));
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $id = $payload['digest_id'] ?? null;
        if ($type !== 'digest.weekly' || (! is_int($id) && ! is_string($id)) || ! preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            return false;
        }
        try {
            return app(DigestQueries::class)->owned($actor)->whereKey($id)->exists();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
