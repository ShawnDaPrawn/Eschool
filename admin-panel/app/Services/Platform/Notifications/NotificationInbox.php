<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The in-app inbox in three layers (Action Plan 1.4): each row carries the
 * title, a short excerpt and the app path it opens; the full body is read
 * one notice at a time, after the same fresh access check its delivery uses.
 * engineering/specs/notifications/notification-layers.md.
 */
class NotificationInbox
{
    public const PER_PAGE = 25;

    /** Row columns: the body itself is never read for a list. */
    private const ROW = ['id', 'school_id', 'type', 'title', 'message', 'excerpt', 'target', 'delivery_payload', 'date'];

    private function school(): int
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);

        return $school->id();
    }

    /** The user's own notices in this school (linked recipients plus legacy whole-school history), private sources filtered in SQL. */
    private function query(User $actor, int $school): Builder
    {
        return NotificationVisibility::forUser(Notification::query()->where('school_id', $school), (int) $actor->id, 0);
    }

    public function page(User $actor, int $page = 1): array
    {
        $school = $this->school();
        $rows = $this->query($actor, $school)->select(self::ROW)->selectRaw('(`body` IS NOT NULL) AS has_body')
            ->orderByDesc('id')->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['school_id' => $school, 'reader_id' => (int) $actor->id,
            'items' => $rows->getCollection()->map(fn (Notification $row) => $this->item($row))->values()->all(),
            'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()];
    }

    public function show(User $actor, int $id): array
    {
        $school = $this->school();
        // Another user's notice, another school's notice and a withdrawn
        // private source all look the same: not found.
        $meta = $this->query($actor, $school)->select(['id', 'school_id', 'type', 'delivery_payload'])->findOrFail($id);
        $type = (string) $meta->type;
        abort_unless(app(DeliveryGuard::class)->permits((int) $actor->id, $school, $type, $meta->delivery_payload ?? []), 404);
        $definition = config('notification_types', [])[$type] ?? null;
        if (is_array($definition)) {
            abort_unless(app(NotificationRules::class)->offered(User::query()->findOrFail($actor->id), $type), 404);
        }
        // The text is loaded only now, through the same scoped query.
        $row = $this->query($actor, $school)->select(self::ROW)->addSelect('body')->findOrFail($id);
        abort_unless(app(CurrentSchool::class)->id() === $school && (int) $row->school_id === $school, 404);
        $item = $this->item($row);
        $item['body'] = $item['has_body'] ? (string) $row->body : $item['excerpt'];

        return $item;
    }

    private function item(Notification $row): array
    {
        $type = (string) $row->type;
        $private = NotificationTypes::isPrivate($type);
        // A private or notice-only type shows only its generic message,
        // whatever an older row or another writer may have stored.
        $excerpt = $private || $row->excerpt === null || $row->excerpt === '' ? Str::limit((string) $row->message, 140, '') : (string) $row->excerpt;
        $flag = $row->getAttribute('has_body');
        $hasBody = ! $private && ($flag !== null ? (bool) $flag : $row->getAttribute('body') !== null);

        return ['id' => (int) $row->id, 'type' => $type, 'title' => (string) $row->title, 'excerpt' => $excerpt,
            'target' => $this->target($row), 'has_body' => $hasBody, 'date' => Carbon::parse($row->date)->toISOString()];
    }

    /** The stored target, else the delivery link, else the type's default screen. */
    private function target(Notification $row): string
    {
        foreach ([$row->target, $row->delivery_payload['link'] ?? null] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                try {
                    return NotificationTypes::assertAppPath($candidate);
                } catch (RuntimeException) {
                    continue;
                }
            }
        }

        return NotificationTypes::link(NotificationTypes::legacy((string) $row->type));
    }
}
