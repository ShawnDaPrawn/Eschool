<?php

declare(strict_types=1);

namespace App\Services\Platform\Offboarding;

use App\Models\Platform\School;
use App\Models\Platform\SchoolOffboarding;
use App\Services\Academics\Transitions\ExitSigning;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Secrets\SecretSettings;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * The full export a leaving school receives (Action Plan SA.11): every row
 * of the school's own data as JSON Lines, one file per table, in a ZIP with a
 * signed manifest (table, rows, bytes, SHA-256). Built with the academic exit
 * building blocks: the school's Ed25519 export key (ExitSigning), Laravel's
 * encryption at rest, and the private academic disk.
 *
 * Values the panel stores encrypted (the models' encrypted casts) are
 * decrypted so the school can read them; credentials (passwords, token
 * hashes, card keys) are never exported. Navigate Ed's own customer record of
 * the school and delivery machinery are listed in the manifest as not
 * exported, with the reason. Runs inside the school (CurrentSchool::run).
 * engineering/specs/platform/offboarding.md.
 */
class OffboardingExport
{
    public const DISK = 'academic_private';

    public const FORMAT = 'navigateed-school-offboarding-v1';

    /** Navigate Ed's own records about the school as its customer: kept by HQ, not the school's data. */
    public const HQ_RECORDS = [
        'school_accounts' => 'Navigate Ed\'s customer record of the school',
        'school_contacts' => 'Navigate Ed\'s contacts at the school',
        'school_contracts' => 'the contracts between the school and Navigate Ed',
        'school_account_events' => 'Navigate Ed\'s timeline of the customer record',
        'platform_incident_schools' => 'Navigate Ed\'s breach and incident register',
        'school_offboardings' => 'this offboarding record',
    ];

    /** Delivery machinery, not records: not exported. */
    public const OPERATIONAL = [
        'domain_events' => 'the outbox that delivers notices and updates',
        'idempotency_keys' => 'stored replies to retried requests',
    ];

    /**
     * Credentials, keys and the hashes that stand in for them: never exported,
     * whatever the table, and never decrypted. They are access material, not
     * the school's records, and some stay live after the export (card chip
     * keys are what SunVerifier checks a tap against).
     */
    public const NEVER_EXPORTED_COLUMNS = [
        // Sign-in and device credentials.
        'password', 'remember_token', 'fcm_id', 'two_factor_secret', 'two_factor_recovery_codes',
        // Invitation, calendar, lease and reader secrets, and their hashes (a short invitation code's hash can be guessed).
        'token', 'token_hash', 'encrypted_token', 'code_hash', 'secret_hash', 'secret_digest', 'key_hash', 'draft_key_hash',
        // Card keys: the card signing key and the chip's SUN metadata and file-read keys, and the chip encoding plan that carries them.
        'secret_key', 'metadata_key', 'file_read_key', 'encoding_package',
        // Bearer codes that admit or redeem: ticket and order QR tokens and ticket numbers, their lookup hashes, online exam keys.
        'private_qr_token', 'qr_hash', 'private_number', 'number_hash', 'exam_key',
    ];

    /** @var array<string, array<string, string>>|null */
    private ?array $encrypted = null;

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * What the export reads, table by table, for the current school.
     *
     * @return array<string, Builder>
     */
    public function sources(int $schoolId): array
    {
        $sources = [];
        foreach (SchoolOwnedTables::REQUIRED as $table) {
            if (! isset(self::HQ_RECORDS[$table]) && Schema::hasTable($table)) {
                $sources[$table] = DB::table($table)->where('school_id', $schoolId);
            }
        }
        foreach (['audit_log', 'notification_deliveries'] as $table) {
            $sources[$table] = DB::table($table)->where('school_id', $schoolId);
        }
        // The school's own settings, without secrets (mail passwords, gateway keys).
        $sources['settings'] = DB::table('settings')->where('school_id', $schoolId)->whereNotIn('type', array_keys(SecretSettings::SECRETS))
            ->where(function (Builder $query): void {
                foreach (SecretSettings::RETIRED_PREFIXES as $prefix) {
                    $query->where('type', 'not like', $prefix.'%');
                }
            });
        foreach (SchoolOwnedTables::TEAMS as $table) {
            $sources[$table] = DB::table($table)->where('school_id', $schoolId);
        }
        $sources['role_has_permissions'] = DB::table('role_has_permissions')->whereIn('role_id', DB::table('roles')->select('id')->where('school_id', $schoolId));
        // The permission names the school's roles refer to (a shared catalogue, no school data).
        $sources['permissions'] = DB::table('permissions');
        $sources['school_memberships'] = DB::table('school_memberships')->where('school_id', $schoolId);
        // The school's people: one row per person, whatever other school they also belong to.
        $sources['users'] = DB::table('users')->whereIn('id', DB::table('school_memberships')->select('user_id')->where('school_id', $schoolId));

        return $sources;
    }

    /**
     * Build, encrypt and store the export of a preparing offboarding, then
     * make it available to the school's Super Admins. Runs in the school.
     */
    public function generate(int $id, string $generation): void
    {
        $row = SchoolOffboarding::query()->where('state', SchoolOffboarding::PREPARING)->where('generation_id', $generation)->find($id);
        if ($row === null) {
            return;
        }
        try {
            $built = $this->build($row);
        } catch (ExportTooLarge) {
            $this->failed($id, $generation, 'too_large');

            return;
        }
        $path = $this->currentSchool->storagePath('offboarding/'.$id).'/'.Str::uuid().'.enc';
        $disk = Storage::disk(self::DISK);
        $committed = false;
        try {
            $cipher = Crypt::encryptString($built['bytes']);
            unset($built['bytes']);
            if (! $disk->put($path, $cipher)) {
                throw new RuntimeException('The private export could not be stored.');
            }
            $cipherSha = hash('sha256', $cipher);
            unset($cipher);
            $committed = DB::transaction(function () use ($id, $generation, $path, $built, $cipherSha): bool {
                School::query()->whereKey($this->currentSchool->id())->lockForUpdate()->firstOrFail();
                $fresh = SchoolOffboarding::query()->where('state', SchoolOffboarding::PREPARING)->where('generation_id', $generation)->lockForUpdate()->find($id);
                if ($fresh === null) {
                    return false;
                }
                $fresh->forceFill([
                    'state' => SchoolOffboarding::READY, 'artifact_path' => $path, 'artifact_sha256' => $built['sha256'], 'cipher_sha256' => $cipherSha,
                    'artifact_bytes' => $built['size'], 'manifest_sha256' => $built['manifest_sha256'], 'signing_key_id' => $built['key_id'],
                    'table_count' => $built['tables'], 'row_count' => $built['rows'], 'failure_code' => null, 'export_ready_at' => now(),
                ])->save();
                Audit::record('platform_school_offboarding_export_ready', $fresh, [], ['offboarding_id' => (int) $fresh->id, 'tables' => $built['tables'],
                    'rows' => $built['rows'], 'bytes' => $built['size'], 'sha256' => $built['sha256']]);
                $this->notifyAdministrators($fresh);

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
            School::query()->whereKey($this->currentSchool->id())->lockForUpdate()->firstOrFail();
            $row = SchoolOffboarding::query()->where('state', SchoolOffboarding::PREPARING)->where('generation_id', $generation)->lockForUpdate()->find($id);
            if ($row === null) {
                return;
            }
            $row->forceFill(['state' => SchoolOffboarding::FAILED, 'failure_code' => $code])->save();
            Audit::record('platform_school_offboarding_export_failed', $row, [], ['offboarding_id' => (int) $row->id, 'failure_code' => $code]);
        });
    }

    /** The archive's bytes, checked against the hashes recorded when it was built. */
    public function read(SchoolOffboarding $row): string
    {
        abort_unless($this->validPath($row), 409, __('This export is not available. Contact Navigate Ed.'));
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists((string) $row->artifact_path), 409, __('This export copy needs restoration. Contact Navigate Ed.'));
        $cipher = (string) $disk->get((string) $row->artifact_path);
        abort_unless(hash_equals((string) $row->cipher_sha256, hash('sha256', $cipher)), 409, __('This export copy needs restoration. Contact Navigate Ed.'));
        try {
            $bytes = Crypt::decryptString($cipher);
        } catch (Throwable) {
            abort(409, __('This export copy needs restoration. Contact Navigate Ed.'));
        }
        abort_unless(str_starts_with($bytes, "PK\x03\x04") && hash_equals((string) $row->artifact_sha256, hash('sha256', $bytes)), 409, __('This export copy needs restoration. Contact Navigate Ed.'));

        return $bytes;
    }

    /** Whether the stored archive is still there, unchanged (the re-check before deletion). */
    public function intact(SchoolOffboarding $row): bool
    {
        if (! $this->validPath($row) || $row->cipher_sha256 === null) {
            return false;
        }
        $disk = Storage::disk(self::DISK);
        try {
            return $disk->exists((string) $row->artifact_path) && hash_equals((string) $row->cipher_sha256, hash('sha256', (string) $disk->get((string) $row->artifact_path)));
        } catch (Throwable) {
            return false;
        }
    }

    public function discard(SchoolOffboarding $row): void
    {
        if ($this->validPath($row)) {
            Storage::disk(self::DISK)->delete((string) $row->artifact_path);
        }
    }

    public function validPath(SchoolOffboarding $row): bool
    {
        $prefix = 'schools/'.$row->school_id.'/offboarding/'.$row->id.'/';
        $path = (string) $row->artifact_path;

        return str_starts_with($path, $prefix) && str_ends_with($path, '.enc') && Str::isUuid(substr($path, strlen($prefix), -4));
    }

    public function scratchRoot(): string
    {
        return storage_path('app/private/offboarding-scratch');
    }

    /**
     * Columns that models keep encrypted at rest, by table: 'string' for the
     * encrypted cast, 'json' for the encrypted array, collection and object casts.
     *
     * @return array<string, array<string, string>>
     */
    public function encryptedColumns(): array
    {
        if ($this->encrypted !== null) {
            return $this->encrypted;
        }
        $map = [];
        foreach (File::allFiles(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            try {
                if (! class_exists($class) || ! is_subclass_of($class, Model::class) || (new ReflectionClass($class))->isAbstract()) {
                    continue;
                }
                $model = new $class;
                foreach ($model->getCasts() as $column => $cast) {
                    $cast = is_string($cast) ? $cast : '';
                    if ($cast === 'encrypted') {
                        $map[$model->getTable()][$column] = 'string';
                    } elseif (str_starts_with($cast, 'encrypted:') || str_contains($cast, 'AsEncrypted')) {
                        $map[$model->getTable()][$column] = 'json';
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $this->encrypted = $map;
    }

    /**
     * @return array{bytes: string, sha256: string, size: int, manifest_sha256: string, key_id: string, tables: int, rows: int}
     */
    private function build(SchoolOffboarding $row): array
    {
        $schoolId = (int) $row->school_id;
        $root = $this->scratchRoot();
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new RuntimeException('Private export scratch is unavailable.');
        }
        $scratch = $root.'/'.Str::uuid();
        if (! mkdir($scratch, 0700)) {
            throw new RuntimeException('Private export scratch is unavailable.');
        }
        $maximum = (int) config('offboarding.maximum_export_bytes');
        $zip = new ZipArchive;
        $opened = false;
        try {
            if ($zip->open($scratch.'/export.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('The export archive could not be created.');
            }
            $opened = true;
            $total = 0;
            $rows = 0;
            $tables = [];
            foreach ($this->sources($schoolId) as $table => $query) {
                $entry = $this->writeTable($table, $query, $scratch.'/'.$table.'.jsonl', $total, $maximum);
                if (! $zip->addFile($scratch.'/'.$table.'.jsonl', 'tables/'.$table.'.jsonl')) {
                    throw new RuntimeException('An export file could not be added.');
                }
                $tables[] = $entry;
                $rows += $entry['rows'];
            }
            $school = DB::table('schools')->where('id', $schoolId)->first(['id', 'name', 'slug', 'timezone', 'short_name', 'tagline', 'created_at']);
            $json = fn (array $data): string => json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
            $manifest = $json([
                'format' => self::FORMAT, 'offboarding_id' => (int) $row->id, 'school' => (array) $school, 'prepared_at' => now()->toISOString(),
                'table_count' => count($tables), 'row_count' => $rows, 'tables' => $tables,
                'not_exported' => self::HQ_RECORDS + self::OPERATIONAL + ['stored files' => 'Uploaded files (documents, photos, report PDFs) are not in this export yet; ask Navigate Ed for them.'],
                'never_exported_columns' => self::NEVER_EXPORTED_COLUMNS,
            ]);
            $signature = app(ExitSigning::class)->sign($manifest);
            $readme = "This is the full export of your school's data from NavigateEd.\n\nEach file in tables/ holds one table, one row per line as JSON (JSON Lines).\nmanifest.json lists every file with its row count and SHA-256, and what is not included and why.\nmanifest.signature is the Ed25519 signature of manifest.json; verification.json holds the public key.\nValues NavigateEd stored encrypted are decrypted here. Keep this file safe: it contains personal information.\n";
            if (! $zip->addFromString('school.json', $json((array) $school)) || ! $zip->addFromString('manifest.json', $manifest)
                || ! $zip->addFromString('manifest.signature', $signature['signature']."\n")
                || ! $zip->addFromString('verification.json', $json(['algorithm' => $signature['algorithm'], 'public_key' => $signature['public_key'], 'key_id' => $signature['key_id']]))
                || ! $zip->addFromString('README.txt', $readme)) {
                throw new RuntimeException('The export archive could not be completed.');
            }
            if (! $zip->close()) {
                throw new RuntimeException('The export archive could not be completed.');
            }
            $opened = false;
            if ((int) filesize($scratch.'/export.zip') > $maximum) {
                throw new ExportTooLarge;
            }
            $bytes = file_get_contents($scratch.'/export.zip');
            if (! is_string($bytes)) {
                throw new RuntimeException('The export archive could not be read.');
            }

            return ['bytes' => $bytes, 'sha256' => hash('sha256', $bytes), 'size' => strlen($bytes), 'manifest_sha256' => hash('sha256', $manifest),
                'key_id' => $signature['key_id'], 'tables' => count($tables), 'rows' => $rows];
        } finally {
            if ($opened) {
                $zip->close();
            }
            File::deleteDirectory($scratch);
        }
    }

    /**
     * One table as JSON Lines, read a chunk at a time.
     *
     * @return array{table: string, file: string, rows: int, bytes: int, sha256: string, omitted_columns: list<string>, decrypted_columns: list<string>, unreadable_values: int}
     */
    private function writeTable(string $table, Builder $query, string $file, int &$total, int $maximum): array
    {
        $columns = Schema::getColumnListing($table);
        $omitted = array_values(array_intersect($columns, self::NEVER_EXPORTED_COLUMNS));
        $keep = array_values(array_diff($columns, $omitted));
        $decrypt = array_intersect_key($this->encryptedColumns()[$table] ?? [], array_flip($keep));
        $select = (clone $query)->select(array_map(fn (string $column): string => $table.'.'.$column, $keep));
        if (in_array('id', $columns, true)) {
            $records = $select->lazyById(500, $table.'.id', 'id');
        } else {
            foreach ($keep as $column) {
                $select->orderBy($table.'.'.$column);
            }
            $records = $select->lazy(500);
        }
        $handle = fopen($file, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Private export scratch is unavailable.');
        }
        $count = 0;
        $unreadable = 0;
        try {
            foreach ($records as $record) {
                $values = (array) $record;
                foreach ($decrypt as $column => $kind) {
                    if ($values[$column] === null) {
                        continue;
                    }
                    try {
                        $plain = Crypt::decryptString((string) $values[$column]);
                        $values[$column] = $kind === 'json' ? json_decode($plain, true) : $plain;
                    } catch (Throwable) {
                        $values[$column] = null;
                        $unreadable++;
                    }
                }
                $line = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
                $total += strlen($line);
                if ($total > $maximum) {
                    throw new ExportTooLarge;
                }
                fwrite($handle, $line);
                $count++;
            }
        } finally {
            fclose($handle);
        }

        return ['table' => $table, 'file' => 'tables/'.$table.'.jsonl', 'rows' => $count, 'bytes' => (int) filesize($file), 'sha256' => (string) hash_file('sha256', $file),
            'omitted_columns' => $omitted, 'decrypted_columns' => array_keys($decrypt), 'unreadable_values' => $unreadable];
    }

    /** Tell the school's Super Admins the export is ready in their panel. */
    private function notifyAdministrators(SchoolOffboarding $row): void
    {
        $ids = SchoolExit::administratorIds();
        if ($ids === []) {
            return;
        }
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice('school.offboarding_export', __('Your school\'s data export is ready'),
            __('Download it and confirm you received it by :date.', ['date' => $row->receipt_deadline->toDateString()]), '/school-exit');
        $notifier->assign($notice, $ids);
    }
}
