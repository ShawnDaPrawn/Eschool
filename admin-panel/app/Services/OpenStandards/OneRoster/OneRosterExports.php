<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\OneRoster;

use App\Jobs\OpenStandards\BuildOneRosterExport;
use App\Models\OpenStandards\OneRosterExport;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * OneRoster 1.2 CSV exports (Action Plan 5.9). A school member with the
 * sensitive "Export the school roster in OneRoster format" ability starts one
 * from the panel, with a reason; it is built by a queued job into private,
 * encrypted storage, and downloaded through a short-lived signed link bound
 * to the person who asked. Learners' birth dates and sex are left out unless
 * the export asks for them, and asking needs the sensitive profile export
 * ability too. Every step is audited. engineering/specs/platform/open-standards.md.
 */
class OneRosterExports
{
    public const PERMISSION = 'oneroster-export';

    public const DEMOGRAPHICS_PERMISSION = 'profile-sensitive-export';

    public const DISK = 'academic_private';

    public function __construct(private readonly CurrentSchool $currentSchool, private readonly OneRosterTables $tables) {}

    /** The fresh person, if they may export this school's roster now. */
    public function authorise(User $actor): User
    {
        $school = $this->currentSchool;
        // Navigate Ed's support and impersonation sessions never take a school's roster away.
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && ! $school->isSupportSession() && $school->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        abort_if(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) === [], 403);
        abort_unless($fresh->can(self::PERMISSION), 403);

        return $fresh;
    }

    public function allowed(?User $actor): bool
    {
        try {
            return $actor !== null && $this->authorise($actor) instanceof User;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    public function page(User $actor): array
    {
        $fresh = $this->authorise($actor);
        $rows = OneRosterExport::where('requested_by', $fresh->id)->orderByDesc('id')->limit(10)->get();

        return ['may_include_demographics' => $fresh->can(self::DEMOGRAPHICS_PERMISSION), 'year' => SessionYear::find((int) schoolSetting('session_year'))?->name,
            'exports' => $rows->map(fn (OneRosterExport $row) => [
                'id' => (int) $row->id, 'state' => $row->state, 'requested_at' => $row->created_at?->timezone($this->timezone())->format('Y-m-d H:i'),
                'demographics' => $row->include_demographics, 'counts' => (array) ($row->row_counts ?? []), 'sha256' => $row->artifact_sha256,
                'expires_at' => $row->expires_at?->setTimezone($this->timezone())->format('Y-m-d H:i'),
                'download_url' => $this->available($row) ? URL::temporarySignedRoute('open-standards.download', now()->addMinutes(max(1, (int) config('open_standards.oneroster.download_link_minutes'))),
                    ['export' => $row->id, 'reader' => $fresh->id]) : null,
            ])->all()];
    }

    /** @param array{reason: string, include_demographics?: bool} $input */
    public function request(User $actor, array $input): OneRosterExport
    {
        $fresh = $this->authorise($actor);
        $demographics = (bool) ($input['include_demographics'] ?? false);
        abort_if($demographics && ! $fresh->can(self::DEMOGRAPHICS_PERMISSION), 403, __('Exporting birth dates and sex needs the sensitive profile export ability.'));
        $year = SessionYear::findOrFail((int) schoolSetting('session_year'));

        return DB::transaction(function () use ($fresh, $input, $demographics, $year): OneRosterExport {
            School::query()->whereKey($this->currentSchool->id())->lockForUpdate()->firstOrFail();
            abort_if(OneRosterExport::where('state', OneRosterExport::PREPARING)->where('created_at', '>', now()->subMinutes(15))->exists(), 409, __('An export is already being prepared. Wait for it to finish.'));
            $row = OneRosterExport::create(['requested_by' => $fresh->id, 'session_year_id' => $year->id, 'reason' => trim($input['reason']), 'include_demographics' => $demographics,
                'state' => OneRosterExport::PREPARING, 'generation_id' => (string) Str::uuid()]);
            Audit::record('open_standards.oneroster_export_requested', $row, [], ['export_id' => (int) $row->id, 'session_year_id' => (int) $year->id, 'include_demographics' => $demographics, 'reason' => $row->reason]);
            BuildOneRosterExport::dispatch($this->currentSchool->id(), (int) $row->id, (string) $row->generation_id)->afterCommit();

            return $row;
        });
    }

    /** The job: build, encrypt and store the ZIP, inside the school. */
    public function generate(int $id, string $generation): void
    {
        $row = OneRosterExport::where('state', OneRosterExport::PREPARING)->where('generation_id', $generation)->find($id);
        if ($row === null) {
            return;
        }
        $requester = User::find($row->requested_by);
        if ($requester === null || ! $this->stillAllowed($requester, (bool) $row->include_demographics)) {
            // The person lost the ability while the export waited: nothing is built.
            $this->failed($id, $generation, 'not_authorised');

            return;
        }
        $built = $this->zip($row);
        if ($built === null) {
            $this->failed($id, $generation, 'too_large');

            return;
        }
        $path = $this->currentSchool->storagePath('oneroster/'.$id).'/'.Str::uuid().'.enc';
        $disk = Storage::disk(self::DISK);
        $committed = false;
        try {
            $cipher = Crypt::encryptString($built['bytes']);
            if (! $disk->put($path, $cipher)) {
                throw new RuntimeException('The private export could not be stored.');
            }
            $cipherSha = hash('sha256', $cipher);
            unset($cipher);
            $committed = DB::transaction(function () use ($id, $generation, $path, $built, $cipherSha): bool {
                $fresh = OneRosterExport::where('state', OneRosterExport::PREPARING)->where('generation_id', $generation)->lockForUpdate()->find($id);
                if ($fresh === null) {
                    return false;
                }
                $fresh->forceFill(['state' => OneRosterExport::READY, 'artifact_path' => $path, 'artifact_sha256' => $built['sha256'], 'cipher_sha256' => $cipherSha,
                    'artifact_bytes' => strlen($built['bytes']), 'row_counts' => $built['counts'], 'failure_code' => null, 'ready_at' => now(),
                    'expires_at' => now()->addDays(max(1, (int) config('open_standards.oneroster.keep_days')))])->save();
                Audit::record('open_standards.oneroster_export_ready', $fresh, [], ['export_id' => (int) $fresh->id, 'counts' => $built['counts'], 'sha256' => $built['sha256']]);

                return true;
            });
        } finally {
            if (! $committed) {
                $disk->delete($path);
            }
        }
    }

    public function failed(int $id, string $generation, string $code): void
    {
        DB::transaction(function () use ($id, $generation, $code): void {
            $row = OneRosterExport::where('state', OneRosterExport::PREPARING)->where('generation_id', $generation)->lockForUpdate()->find($id);
            if ($row === null) {
                return;
            }
            $row->forceFill(['state' => OneRosterExport::FAILED, 'failure_code' => $code])->save();
            Audit::record('open_standards.oneroster_export_failed', $row, [], ['export_id' => (int) $row->id, 'failure_code' => $code]);
        });
    }

    /**
     * The ZIP for the person the signed link was made for.
     *
     * @return array{bytes: string, name: string}
     */
    public function download(User $actor, int $id, int $reader): array
    {
        $fresh = $this->authorise($actor);
        abort_unless($reader === (int) $fresh->id, 403);

        return DB::transaction(function () use ($fresh, $id): array {
            $row = OneRosterExport::where('requested_by', $fresh->id)->lockForUpdate()->findOrFail($id);
            abort_unless($this->available($row), 410, __('This export is no longer available to download.'));
            abort_if($row->include_demographics && ! $fresh->can(self::DEMOGRAPHICS_PERMISSION), 403);
            $bytes = $this->read($row);
            $row->forceFill(['download_count' => $row->download_count + 1, 'last_downloaded_at' => now()])->save();
            Audit::record('open_standards.oneroster_export_downloaded', $row, [], ['export_id' => (int) $row->id, 'download' => (int) $row->download_count]);

            return ['bytes' => $bytes, 'name' => 'oneroster-'.$this->currentSchool->school()?->slug.'-'.$row->ready_at?->format('Ymd-His').'.zip'];
        });
    }

    /**
     * Retention (open_standards.oneroster.keep_days): delete the stored file of
     * every export of the current school whose download window has ended. The
     * row and its audit trail stay, marked purged. Runs inside one school
     * (the daily open-standards:prune-exports command visits each in turn).
     *
     * @return int how many files were deleted
     */
    public function prune(): int
    {
        abort_unless($this->currentSchool->isSet() && ! $this->currentSchool->isAcrossSchools(), 403);
        $purged = 0;
        $due = OneRosterExport::where('state', OneRosterExport::READY)->whereNull('purged_at')->where('expires_at', '<=', now())->orderBy('id')->pluck('id');
        foreach ($due as $id) {
            $purged += (int) DB::transaction(function () use ($id): bool {
                // Re-read under the lock: a download that started first finishes before the file goes.
                $row = OneRosterExport::where('state', OneRosterExport::READY)->whereNull('purged_at')->where('expires_at', '<=', now())->lockForUpdate()->find($id);
                if ($row === null) {
                    return false;
                }
                $path = (string) $row->artifact_path;
                if ($this->validPath($row)) {
                    Storage::disk(self::DISK)->delete($path);
                }
                $row->forceFill(['state' => OneRosterExport::PURGED, 'artifact_path' => null, 'purged_at' => now()])->save();
                Audit::record('open_standards.oneroster_export_purged', $row, [], ['export_id' => (int) $row->id, 'sha256' => $row->artifact_sha256]);

                return true;
            });
        }

        return $purged;
    }

    public function available(OneRosterExport $row): bool
    {
        return $row->state === OneRosterExport::READY && $row->artifact_path !== null && $row->expires_at !== null && $row->expires_at->isFuture();
    }

    private function read(OneRosterExport $row): string
    {
        $path = (string) $row->artifact_path;
        abort_unless($this->validPath($row), 409);
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path), 410, __('This export is no longer available to download.'));
        $cipher = (string) $disk->get($path);
        abort_unless(hash_equals((string) $row->cipher_sha256, hash('sha256', $cipher)), 409, __('This export copy is damaged. Prepare a new one.'));
        $bytes = Crypt::decryptString($cipher);
        abort_unless(str_starts_with($bytes, "PK\x03\x04") && hash_equals((string) $row->artifact_sha256, hash('sha256', $bytes)), 409, __('This export copy is damaged. Prepare a new one.'));

        return $bytes;
    }

    /** Only this export's own file inside this school's folder is ever read or deleted. */
    private function validPath(OneRosterExport $row): bool
    {
        $prefix = 'schools/'.$this->currentSchool->id().'/oneroster/'.$row->id.'/';
        $path = (string) $row->artifact_path;

        return (int) $row->school_id === $this->currentSchool->id() && str_starts_with($path, $prefix) && str_ends_with($path, '.enc') && Str::isUuid(substr($path, strlen($prefix), -4));
    }

    /** @return array{bytes: string, sha256: string, counts: array<string, int>}|null */
    private function zip(OneRosterExport $row): ?array
    {
        $tables = $this->tables->build((int) $row->session_year_id, (bool) $row->include_demographics, app(AcademicAuthority::class)->today());
        $counts = array_map('count', $tables);
        if (array_sum($counts) > (int) config('open_standards.oneroster.maximum_rows')) {
            return null;
        }
        $path = tempnam(sys_get_temp_dir(), 'oneroster-');
        if ($path === false) {
            throw new RuntimeException('The export could not be packaged.');
        }
        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('The export could not be packaged.');
            }
            $zip->addFromString('manifest.csv', OneRosterCsv::render(['propertyName', 'value'], OneRosterTables::manifest(array_keys($tables))));
            foreach ($tables as $file => $rows) {
                $zip->addFromString($file, OneRosterCsv::render(OneRosterTables::HEADERS[$file], $rows));
            }
            $zip->close();
            $bytes = (string) file_get_contents($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        return ['bytes' => $bytes, 'sha256' => hash('sha256', $bytes), 'counts' => $counts];
    }

    private function stillAllowed(User $requester, bool $demographics): bool
    {
        try {
            $fresh = User::query()->where('status', 1)->findOrFail($requester->id);
            abort_if(PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
            abort_if(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) === [], 403);

            return $fresh->can(self::PERMISSION) && (! $demographics || $fresh->can(self::DEMOGRAPHICS_PERMISSION));
        } catch (Throwable) {
            return false;
        }
    }

    private function timezone(): string
    {
        return (string) ($this->currentSchool->school()?->timezone ?: 'Africa/Johannesburg');
    }
}
