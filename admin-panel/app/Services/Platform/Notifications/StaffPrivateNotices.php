<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Notification;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Models\UserNotification;
use App\Policies\People\ProfilePolicy;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Dedicated staff purpose: registered private sources only, never a legacy broadcast. */
class StaffPrivateNotices
{
    private function actor(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        $fresh = app(FacilitatorAccess::class)->ready($actor);
        abort_if(PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('communication') : $gate->allows('communication'), 403);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->actor($actor);

            return $this->types() !== [];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    private function types(): array
    {
        $entries = config('private_notices.staff_inbox', []);
        $providers = config('private_notices.scopes', []);
        if (! is_array($entries) || ! is_array($providers)) {
            throw new RuntimeException('Private staff inbox configuration must be a map.');
        }
        $types = [];
        foreach ($entries as $type => $entry) {
            // Dotted configuration keys need exact indexing, not Laravel dot lookup.
            $definition = config('notification_types', [])[$type] ?? null;
            $registered = is_string($type) && collect(array_keys($providers))->contains(fn ($prefix) => is_string($prefix) && str_starts_with($type, $prefix));
            if (! $registered || ! is_array($definition) || ! is_array($entry)
                || array_intersect($definition['portals'] ?? [], ['teacher', 'admin']) === []
                || ! is_string($entry['feature'] ?? null) || ! preg_match('/^[a-z_]{1,50}$/D', $entry['feature'])
                || ! is_string($entry['title'] ?? null) || ! is_string($entry['message'] ?? null)) {
                throw new RuntimeException('Staff inbox entries require an exact registered private staff type and fixed generic metadata.');
            }
            if (app(ModuleGate::class)->allows($definition['module'])) {
                $types[$type] = $entry;
            }
        }

        return $types;
    }

    private function typeWritable(string $type): bool
    {
        $definition = config('notification_types', [])[$type] ?? null;

        return is_array($definition) && app(ModuleGate::class)->writable($definition['module']);
    }

    private function query(User $actor): Builder
    {
        $school = app(CurrentSchool::class)->id();
        $links = UserNotification::where('user_id', $actor->id)->where('school_id', $school)->select('notification_id');
        $query = Notification::where('school_id', $school)->where('preferences_enforced', true)->whereIn('id', $links)->whereIn('type', array_keys($this->types()));

        return app(PrivateNoticeScopes::class)->apply($query, (int) $actor->id);
    }

    private function roles(User $actor): array
    {
        $roles = app(ProfilePolicy::class)->roles($actor);
        sort($roles);

        return $roles;
    }

    private function unchanged(User $actor, int $school, array $roles): void
    {
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $fresh = $this->actor($actor);
        abort_unless($roles === $this->roles($fresh), 403);
    }

    public function inbox(User $actor, int $page = 1): array
    {
        $fresh = $this->actor($actor);
        $school = app(CurrentSchool::class)->id();
        $roles = $this->roles($fresh);
        $types = $this->types();
        $query = $this->query($fresh);
        $rows = $query->orderByDesc('id')->paginate(25, ['id', 'type', 'delivery_payload'], 'page', max(1, $page));
        $ids = $rows->pluck('id')->all();
        $links = UserNotification::where('user_id', $fresh->id)->whereIn('notification_id', $ids)->get(['notification_id', 'private_read_at'])->keyBy('notification_id');
        $payloads = $rows->mapWithKeys(fn ($row) => [(int) $row->id => ['type' => $row->type, 'payload' => $row->delivery_payload ?? []]])->all();
        $markers = app(PrivateNoticeReadStates::class)->markers($fresh, $payloads);
        $unreadLinks = UserNotification::where('user_id', $fresh->id)->whereNull('private_read_at')->select('notification_id');
        $unread = app(PrivateNoticeReadStates::class)->applyUnread($this->query($fresh)->whereIn('id', $unreadLinks), $fresh)->count();
        $this->unchanged($fresh, $school, $roles);
        abort_unless($this->query($fresh)->whereIn('id', $ids)->count() === count($ids), 403);
        $items = $rows->map(fn ($row) => ['id' => (int) $row->id, 'feature' => $types[$row->type]['feature'],
            'title' => __($types[$row->type]['title']), 'message' => __($types[$row->type]['message']),
            'writable' => $this->typeWritable($row->type), 'read_at' => $links->get($row->id)?->private_read_at?->toISOString() ?? ($markers[$row->id] ?? null)])->all();

        return ['school_id' => $school, 'reader_id' => (int) $fresh->id, 'writable' => app(ModuleGate::class)->writable('communication') && collect(array_keys($types))->contains(fn ($type) => $this->typeWritable($type)),
            'items' => $items, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'unread_count' => $unread];
    }

    public function markRead(User $actor, int $id): array
    {
        $fresh = $this->actor($actor, true);
        $school = app(CurrentSchool::class)->id();
        $roles = $this->roles($fresh);

        return DB::transaction(function () use ($fresh, $id, $school, $roles) {
            $this->actor($fresh, true);
            $notice = $this->query($fresh)->select('id', 'type', 'delivery_payload')->lockForUpdate()->findOrFail($id);
            abort_unless($this->typeWritable($notice->type), 403);
            app(PrivateNoticeReadStates::class)->record($fresh, $id, $notice->type, $notice->delivery_payload ?? []);
            UserNotification::where('user_id', $fresh->id)->where('notification_id', $id)->whereNull('private_read_at')->update(['private_read_at' => now()->utc()->toDateTimeString()]);
            $this->unchanged($fresh, $school, $roles);
            $this->actor($fresh, true);
            abort_unless($this->typeWritable($notice->type), 403);
            $this->query($fresh)->select('id')->findOrFail($id);
            $link = UserNotification::where('user_id', $fresh->id)->where('notification_id', $id)->whereNotNull('private_read_at')->firstOrFail();

            return ['id' => $id, 'school_id' => $school, 'reader_id' => (int) $fresh->id, 'read_at' => $link->private_read_at->toISOString()];
        });
    }
}
