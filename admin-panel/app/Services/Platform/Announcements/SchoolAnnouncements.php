<?php

declare(strict_types=1);

namespace App\Services\Platform\Announcements;

use App\Models\Platform\PlatformAnnouncementRead;
use App\Models\Platform\PlatformAnnouncementSchool;
use App\Models\User;
use App\Services\Platform\Saas\PlatformAnnouncements;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Navigate Ed's announcements as a school sees them (SA.13): only the ones
 * that reached this school (its own platform_announcement_schools row), only
 * for its Super Admins, and only each person's own read. A school never sees
 * which other schools an announcement reached or who read it there.
 * engineering/specs/platform/hq-announcements.md.
 */
class SchoolAnnouncements
{
    /** At most this many banners at once; the rest wait on the announcements page. */
    public const BANNERS = 3;

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** Whether the person is a Super Admin of the school in context (never across schools or as a platform impersonation). */
    public function reaches(?User $user): bool
    {
        return $user !== null && $this->currentSchool->isSet() && ! $this->currentSchool->isAcrossSchools()
            && ! $this->currentSchool->isImpersonating() && $user->hasRole(SchoolRoles::SUPER_ADMIN);
    }

    /**
     * The banners: published, between their dates, not withdrawn, and not yet
     * dismissed by this person. Warnings first.
     *
     * @return list<array<string, mixed>>
     */
    public function banners(?User $user): array
    {
        if (! $this->reaches($user)) {
            return [];
        }
        // In UTC, as stored: inside a school, now() is in the school's own time zone.
        $now = PlatformAnnouncements::now()->format('Y-m-d H:i:s');

        return $this->published()
            ->where('a.shows_from', '<=', $now)
            ->where(fn ($q) => $q->whereNull('a.shows_until')->orWhere('a.shows_until', '>', $now))
            ->whereNotExists(fn ($q) => $q->from('platform_announcement_reads')->whereColumn('platform_announcement_reads.announcement_id', 'a.id')
                ->where('platform_announcement_reads.school_id', $this->currentSchool->id())->where('platform_announcement_reads.user_id', $user->id))
            ->orderByRaw("a.severity = 'warning' desc")->orderByDesc('a.shows_from')->limit(self::BANNERS)
            ->get($this->columns())->map(fn ($row) => $this->present($row))->values()->all();
    }

    public const PER_PAGE = 25;

    /**
     * The announcements page: everything published to the school and not
     * withdrawn, newest first, a page at a time, with whether this person has
     * read each.
     */
    public function page(User $user, int $page = 1): LengthAwarePaginator
    {
        abort_unless($this->reaches($user), 403);
        $paginator = $this->published()->orderByDesc('a.shows_from')->orderByDesc('a.id')->select($this->columns())
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page))->withPath(route('platform-announcements.index'));
        $read = PlatformAnnouncementRead::query()->where('user_id', $user->id)
            ->whereIn('announcement_id', collect($paginator->items())->pluck('id'))->pluck('read_at', 'announcement_id');

        return $paginator->through(fn ($row) => $this->full($row, $read));
    }

    /** One announcement published to this school (404 otherwise): what a banner's "Read in full" opens. */
    public function one(User $user, int $announcementId): array
    {
        abort_unless($this->reaches($user), 403);
        $row = $this->published()->where('a.id', $announcementId)->first($this->columns());
        abort_if($row === null, 404);
        $read = PlatformAnnouncementRead::query()->where('user_id', $user->id)->where('announcement_id', $announcementId)->pluck('read_at', 'announcement_id');

        return $this->full($row, $read);
    }

    /** @param  Collection<int, mixed>  $read */
    private function full(object $row, $read): array
    {
        return $this->present($row) + ['html' => AnnouncementText::html((string) $row->body), 'read_at' => isset($read[$row->id]) ? $this->local($read[$row->id]) : null];
    }

    /** The person dismissed the banner: a read, once. */
    public function markRead(User $user, int $announcementId): void
    {
        abort_unless($this->reaches($user), 403);
        abort_unless($this->published()->where('a.id', $announcementId)->exists(), 404);
        DB::transaction(function () use ($user, $announcementId): void {
            if (! PlatformAnnouncementRead::query()->where('announcement_id', $announcementId)->where('user_id', $user->id)->exists()) {
                PlatformAnnouncementRead::query()->create(['announcement_id' => $announcementId, 'user_id' => $user->id, 'read_at' => PlatformAnnouncements::now()->format('Y-m-d H:i:s')]);
            }
        });
    }

    /** This school's targets joined to announcements still published (the school scope limits it to this school). */
    private function published(): Builder
    {
        return PlatformAnnouncementSchool::query()
            ->join('platform_announcements as a', 'a.id', '=', 'platform_announcement_schools.announcement_id')
            ->where('a.status', 'published');
    }

    /** @return list<string> */
    private function columns(): array
    {
        return ['a.id', 'a.kind', 'a.severity', 'a.title', 'a.body', 'a.shows_from', 'a.shows_until', 'a.window_starts_at', 'a.window_ends_at'];
    }

    private function present(object $row): array
    {
        return [
            'id' => (int) $row->id, 'kind' => (string) $row->kind, 'severity' => (string) $row->severity, 'title' => (string) $row->title,
            'excerpt' => AnnouncementText::plain((string) $row->body), 'kind_label' => PlatformAnnouncements::KINDS[$row->kind] ?? '',
            'shows_from' => $this->local($row->shows_from), 'shows_until' => $this->local($row->shows_until),
            'window_starts_at' => $this->local($row->window_starts_at), 'window_ends_at' => $this->local($row->window_ends_at),
        ];
    }

    /** In the school's own time zone. */
    private function local(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $zone = $this->currentSchool->school()?->timezone ?: PlatformAnnouncements::TIMEZONE;

        return PlatformAnnouncements::stored($value)->setTimezone($zone)->format('Y-m-d H:i');
    }
}
