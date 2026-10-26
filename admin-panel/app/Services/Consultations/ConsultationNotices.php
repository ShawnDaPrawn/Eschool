<?php

declare(strict_types=1);

namespace App\Services\Consultations;

use App\Models\Consultations\Consultation;
use App\Models\Notification;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Generic opening intents only. Never reads responses or participation. */
class ConsultationNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    private const FAMILY = 'consultations.opened';

    private const STAFF = 'consultations.staff_opened';

    private const COLUMNS = ['id', 'school_id', 'session_year_id', 'audience', 'status', 'revision', 'opens_at', 'closes_at'];

    private function opening(): Builder
    {
        return Consultation::query()->select(self::COLUMNS)->where('status', 'open')
            ->where('session_year_id', (int) schoolSetting('session_year'))
            ->where('opens_at', '<=', now()->utc())->where('closes_at', '>', now()->utc());
    }

    private function type(User $actor): string
    {
        return array_intersect(['learner', 'parent'], app(NotificationRules::class)->portals($actor)) !== [] ? self::FAMILY : self::STAFF;
    }

    private function link(string $type, int $id): string
    {
        return ($type === self::STAFF ? '/school-consultations/' : '/consultations/').$id;
    }

    public function opened(int $id, int $revision): void
    {
        app(ModuleGate::class)->forget();
        if (! app(ModuleGate::class)->writable('consultations')) {
            return;
        }
        $row = $this->opening()->whereKey($id)->where('revision', $revision)->first();
        if ($row === null) {
            return;
        }
        $groups = [];
        $access = app(ConsultationAccess::class);
        try {
            $candidates = array_keys($access->recipients($row));
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return;
        }
        foreach ($candidates as $userId) {
            $actor = User::find($userId);
            if ($actor !== null && $access->eligible($actor, $row)) {
                $groups[$this->type($actor)][] = (int) $actor->id;
            }
        }
        foreach ($groups as $type => $ids) {
            $key = "consultation-open:$id:$revision:$type";
            $notice = Notification::withTrashed()->where('source_key', $key)->first();
            if ($notice?->trashed()) {
                continue;
            }
            $notifier = app(Notifier::class);
            if ($notice === null) {
                $link = $this->link($type, $id);
                $notice = $notifier->makeNotice($type, __('School consultation available'), __('Open school consultations to review an available ballot.'), $link);
                $notice->source_key = $key;
                $notice->delivery_payload = ['link' => $link, 'consultation_id' => $id, 'consultation_revision' => $revision];
                $notice->save();
            }
            $notifier->assign($notice, $ids);
        }
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if (! in_array($type, [self::FAMILY, self::STAFF], true)) {
            return false;
        }
        foreach (['consultation_id', 'consultation_revision'] as $key) {
            if ((! is_int($payload[$key] ?? null) && ! is_string($payload[$key] ?? null)) || preg_match('/^[1-9][0-9]*$/D', (string) $payload[$key]) !== 1 || filter_var($payload[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                return false;
            }
        }
        try {
            app(ConsultationAccess::class)->actor($actor);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
        $id = (int) $payload['consultation_id'];
        if (($payload['link'] ?? null) !== $this->link($type, $id)) {
            return false;
        }
        $row = $this->opening()->whereKey($id)->where('revision', (int) $payload['consultation_revision'])->first();

        return $row !== null && app(ConsultationAccess::class)->eligible($actor, $row) && $type === $this->type($actor);
    }

    /** Filter sources before inbox counts, pagination or body selection. */
    public function apply(Builder $notifications, User $actor): void
    {
        app(ConsultationAccess::class)->actor($actor);
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $type = $this->type($actor);
        $allowed = [];
        foreach ($this->opening()->orderBy('id')->cursor() as $row) {
            if (app(ConsultationAccess::class)->eligible($actor, $row)) {
                $allowed[] = (int) $row->id;
            }
        }
        $source = $this->opening()->whereIn('id', $allowed)
            ->whereRaw("CAST(consultations.id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.consultation_id'))")
            ->whereRaw("CAST(consultations.revision AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.consultation_revision'))")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = CONCAT(?, consultations.id)", [$type === self::STAFF ? '/school-consultations/' : '/consultations/']);
        $notifications->where('type', $type)->where($notifications->qualifyColumn('school_id'), app(CurrentSchool::class)->id())
            ->whereExists($source->selectRaw('1')->toBase());
    }
}
