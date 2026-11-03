<?php

declare(strict_types=1);

namespace App\Services\Platform\Offboarding;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolOffboarding;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * The leaving school's side of offboarding (Action Plan SA.11), in its own
 * panel: its Super Admin downloads the export through a short-lived signed
 * link bound to them, and confirms receipt. Only an active Super Admin of the
 * current school who is not platform staff gets here; Navigate Ed's HQ staff
 * never receive the export. engineering/specs/platform/offboarding.md.
 */
class SchoolExit
{
    public function __construct(private readonly CurrentSchool $currentSchool, private readonly OffboardingExport $export) {}

    /**
     * The current school's active Super Admins who are not platform staff.
     *
     * @return list<int>
     */
    public static function administratorIds(): array
    {
        $ids = SchoolMembership::query()->active()->where('school_memberships.school_id', app(CurrentSchool::class)->id())
            ->where('school_memberships.role', SchoolRoles::SUPER_ADMIN)
            ->join('users', 'users.id', '=', 'school_memberships.user_id')->where('users.status', 1)->whereNull('users.deleted_at')
            ->whereNotIn('school_memberships.user_id', PlatformStaffIdentity::query()->select('user_id')->where('active', true))
            ->distinct()->pluck('school_memberships.user_id');

        return $ids->map(fn ($id): int => (int) $id)->values()->all();
    }

    /** Whether the person is one of the current school's Super Admins (and not platform staff). */
    public function allowed(?User $user): bool
    {
        return $user !== null && in_array((int) $user->id, self::administratorIds(), true);
    }

    /** The offboarding the school's page shows, or null when the school is not leaving. */
    public function current(): ?SchoolOffboarding
    {
        return SchoolOffboarding::query()->whereIn('state', [SchoolOffboarding::PREPARING, SchoolOffboarding::FAILED, SchoolOffboarding::READY, SchoolOffboarding::RECEIVED])
            ->latest('id')->first();
    }

    /** @return array<string, mixed> */
    public function page(User $actor): array
    {
        abort_unless($this->allowed($actor), 403);
        $row = $this->current();
        if ($row === null) {
            return ['offboarding' => null];
        }
        $available = $this->available($row);

        return ['offboarding' => [
            'id' => (int) $row->id,
            // The school does not need HQ's build failures: a failed build is still being prepared.
            'state' => $row->state === SchoolOffboarding::FAILED ? SchoolOffboarding::PREPARING : $row->state,
            'receipt_deadline' => $row->receipt_deadline->toDateString(), 'delete_on' => $row->delete_on->toDateString(),
            'tables' => $row->table_count, 'rows' => $row->row_count, 'bytes' => $row->artifact_bytes, 'sha256' => $row->artifact_sha256,
            'downloads' => (int) $row->download_count, 'received_at' => $row->received_at?->timezone($this->timezone())->format('Y-m-d H:i'),
            'receipt_basis' => $row->receipt_basis, 'available' => $available,
            'download_url' => $available ? URL::temporarySignedRoute('school-exit.download', now()->addMinutes((int) config('offboarding.download_link_minutes')),
                ['offboarding' => $row->id, 'reader' => $actor->id]) : null,
        ]];
    }

    /**
     * The archive for the Super Admin the signed link was made for.
     *
     * @return array{bytes: string, name: string}
     */
    public function download(User $actor, int $id, int $reader): array
    {
        abort_unless($this->allowed($actor) && $reader === (int) $actor->id, 403);

        return DB::transaction(function () use ($actor, $id): array {
            School::query()->whereKey($this->currentSchool->id())->lockForUpdate()->firstOrFail();
            $row = SchoolOffboarding::query()->lockForUpdate()->findOrFail($id);
            abort_unless($this->available($row), 410, __('This export is no longer available to download.'));
            $bytes = $this->export->read($row);
            $row->forceFill(['download_count' => (int) $row->download_count + 1, 'last_downloaded_at' => now()])->save();
            Audit::record('platform_school_offboarding_export_downloaded', $row, [], ['offboarding_id' => (int) $row->id, 'downloaded_by' => (int) $actor->id, 'download' => (int) $row->download_count]);
            $slug = (string) DB::table('schools')->where('id', $row->school_id)->value('slug');

            return ['bytes' => $bytes, 'name' => 'navigateed-export-'.$slug.'-'.$row->export_ready_at?->format('Ymd').'.zip'];
        });
    }

    /** The Super Admin confirms the school received its export. */
    public function confirm(User $actor, int $id): void
    {
        abort_unless($this->allowed($actor), 403);
        DB::transaction(function () use ($actor, $id): void {
            School::query()->whereKey($this->currentSchool->id())->lockForUpdate()->firstOrFail();
            $row = SchoolOffboarding::query()->lockForUpdate()->findOrFail($id);
            abort_unless($row->state === SchoolOffboarding::READY && $this->available($row), 409, __('This export cannot be confirmed now.'));
            if ((int) $row->download_count === 0) {
                throw ValidationException::withMessages(['confirm' => __('Download the export before you confirm you received it.')]);
            }
            $row->forceFill(['state' => SchoolOffboarding::RECEIVED, 'received_at' => now(), 'received_by' => $actor->id, 'receipt_basis' => 'confirmed'])->save();
            Audit::record('platform_school_offboarding_receipt_confirmed', $row, ['state' => SchoolOffboarding::READY], ['offboarding_id' => (int) $row->id, 'state' => SchoolOffboarding::RECEIVED, 'receipt_basis' => 'confirmed']);
        });
    }

    /** Downloadable: built, not cancelled or archived, and the receipt deadline (in the school's time) not passed. */
    public function available(SchoolOffboarding $row): bool
    {
        return in_array($row->state, [SchoolOffboarding::READY, SchoolOffboarding::RECEIVED], true)
            && $row->artifact_path !== null
            && CarbonImmutable::now($this->timezone())->toDateString() <= $row->receipt_deadline->toDateString();
    }

    private function timezone(): string
    {
        return (string) ($this->currentSchool->school()?->timezone ?: 'Africa/Johannesburg');
    }
}
