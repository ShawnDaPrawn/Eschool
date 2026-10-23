<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\NotificationPreference;
use App\Models\Platform\NotificationRule;
use App\Models\User;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationRules
{
    public const CHANNELS = ['push', 'email', 'in_app'];

    public const MODES = ['mandatory', 'default_on', 'default_off', 'off'];

    private array $jobRules = [];

    public function portals(User $user): array
    {
        $roles = app(MembershipService::class)->active($user)->where('school_id', app(CurrentSchool::class)->id())->pluck('role');
        $portals = [];
        foreach (['Student' => 'learner', 'Parent' => 'parent', 'Teacher' => 'teacher'] as $role => $portal) {
            if ($roles->contains($role)) {
                $portals[] = $portal;
            }
        }
        if ($roles->diff(['Student', 'Parent', 'Teacher'])->isNotEmpty()) {
            $portals[] = 'admin';
        }

        return $portals;
    }

    private function schoolRows(): array
    {
        $id = app(CurrentSchool::class)->id();
        $key = 'notification.rules.'.$id;
        if (RequestContext::inHttpRequest() && request()->attributes->has($key)) {
            return request()->attributes->get($key);
        }
        if (! RequestContext::inHttpRequest() && isset($this->jobRules[$id])) {
            return $this->jobRules[$id];
        }
        $rows = NotificationRule::query()->get()->keyBy(fn ($row) => $row->portal.'|'.$row->type.'|'.$row->channel)->all();
        request()->attributes->set($key, $rows);

        return $this->jobRules[$id] = $rows;
    }

    public function forget(): void
    {
        $id = app(CurrentSchool::class)->id();
        unset($this->jobRules[$id]);
        request()->attributes->remove('notification.rules.'.$id);
    }

    public function offered(User $user, string $type): bool
    {
        $definition = (config('notification_types', [])[$type] ?? null);

        return is_array($definition) && app(ModuleGate::class)->allows($definition['module'])
            && array_intersect($this->portals($user), $definition['portals']) !== []
            && (! isset($definition['permission']) || $user->can($definition['permission']));
    }

    public function mode(string $portal, string $type, string $channel): string
    {
        $definition = (config('notification_types', [])[$type] ?? null);
        if (! is_array($definition) || ! in_array($portal, $definition['portals'], true) || ! in_array($channel, self::CHANNELS, true)) {
            return 'off';
        }
        $default = $definition['channels'][$channel] ?? 'off';
        if (in_array($portal, $definition['locked'] ?? [], true)) {
            return in_array($channel, $definition['mandatory_channels'] ?? [], true) || $default === 'mandatory' ? 'mandatory' : 'off';
        }
        $row = $this->schoolRows()[$portal.'|'.$type.'|'.$channel] ?? null;

        return $row?->mode ?? $default;
    }

    private function effectiveMode(User $user, string $type, string $channel): string
    {
        $rank = ['off' => 0, 'default_off' => 1, 'default_on' => 2, 'mandatory' => 3];
        $mode = 'off';
        foreach ($this->portals($user) as $portal) {
            $candidate = $this->mode($portal, $type, $channel);
            if (($rank[$candidate] ?? 0) > $rank[$mode]) {
                $mode = $candidate;
            }
        }

        return $mode;
    }

    public function preferences(User $user): array
    {
        $choices = NotificationPreference::query()->where('user_id', $user->id)->get()->keyBy(fn ($row) => $row->type.'|'.$row->channel);
        $rows = [];
        foreach (config('notification_types') as $type => $definition) {
            if (! $this->offered($user, $type)) {
                continue;
            }
            $channels = [];
            foreach (self::CHANNELS as $channel) {
                $mode = $this->effectiveMode($user, $type, $channel);
                if ($mode === 'off') {
                    continue;
                }
                $mandatory = $mode === 'mandatory';
                $channels[$channel] = [
                    'enabled' => $mandatory || ($choices->get($type.'|'.$channel)?->enabled ?? $mode === 'default_on'),
                    'locked' => $mandatory,
                    'reason' => $mandatory ? __('This notice is required for account safety or school responsibility.') : null,
                ];
            }
            if ($channels !== []) {
                $rows[] = ['type' => $type, 'label' => __($definition['label']), 'module' => $definition['module'], 'channels' => $channels];
            }
        }

        return $rows;
    }

    public function allows(User $user, string $type, string $channel): bool
    {
        if (! $this->offered($user, $type)) {
            return false;
        }
        $mode = $this->effectiveMode($user, $type, $channel);
        if (in_array($mode, ['mandatory', 'off'], true)) {
            return $mode === 'mandatory';
        }

        return NotificationPreference::query()->where('user_id', $user->id)->where('type', $type)->where('channel', $channel)->value('enabled') ?? $mode === 'default_on';
    }

    public function choose(User $user, string $type, string $channel, bool $enabled): void
    {
        if (! $this->offered($user, $type)) {
            throw ValidationException::withMessages(['type' => 'This notification type is unavailable.']);
        }
        if (in_array($this->effectiveMode($user, $type, $channel), ['mandatory', 'off'], true)) {
            throw ValidationException::withMessages(['channel' => 'The school requires this notice or does not offer this channel.']);
        }
        NotificationPreference::query()->updateOrCreate(['user_id' => $user->id, 'type' => $type, 'channel' => $channel], ['enabled' => $enabled]);
    }

    public function change(User $actor, string $portal, string $type, string $channel, string $mode): void
    {
        DB::transaction(function () use ($actor, $portal, $type, $channel, $mode) {
            $definition = (config('notification_types', [])[$type] ?? null);
            if (! is_array($definition) || ! in_array($portal, $definition['portals'], true) || ! in_array($channel, self::CHANNELS, true) || ! in_array($mode, self::MODES, true)) {
                throw ValidationException::withMessages(['type' => 'Choose a registered notification type, portal and channel.']);
            }
            if (in_array($portal, $definition['locked'] ?? [], true)) {
                throw ValidationException::withMessages(['mode' => 'This account or safeguarding notice is locked by NavigateEd.']);
            }
            NotificationRule::query()->updateOrCreate(['portal' => $portal, 'type' => $type, 'channel' => $channel], ['mode' => $mode, 'changed_by' => $actor->id]);
            $this->forget();
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'notification.rules'));
        });
    }

    public function context(User $user): array
    {
        $answer = [];
        foreach (config('notification_types') as $type => $definition) {
            if (! $this->offered($user, $type)) {
                continue;
            }
            foreach (self::CHANNELS as $channel) {
                $answer[] = ['type' => $type, 'channel' => $channel, 'mode' => $this->effectiveMode($user, $type, $channel)];
            }
        }

        return $answer;
    }
}
