<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicExitPackage;
use App\Models\Academics\ReportCard;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\TransitionPolicy;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use ZipArchive;

class ExitArtifacts
{
    public const DISK = 'academic_private';

    public function validPath(AcademicExitPackage $row): bool
    {
        $prefix = app(CurrentSchool::class)->storagePath('exit-packages').'/'.$row->id.'/';
        if (! is_string($row->artifact_path) || ! str_starts_with($row->artifact_path, $prefix) || ! str_ends_with($row->artifact_path, '.enc')) {
            return false;
        }

        return Str::isUuid(substr($row->artifact_path, strlen($prefix), -4));
    }

    public function read(User $actor, int $id): string
    {
        $row = app(TransitionPolicy::class)->package($actor, $id);
        abort_unless($row->state === 'ready' && $row->expires_at->isFuture() && $this->validPath($row), 409, __('This export is not available. Prepare a fresh copy or contact the school.'));
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($row->artifact_path), 409, __('This export copy needs restoration. Prepare a new export.'));
        try {
            $bytes = Crypt::decryptString($disk->get($row->artifact_path));
        } catch (\Throwable) {
            abort(409, __('This export copy needs restoration. Prepare a new export.'));
        }
        abort_unless(str_starts_with($bytes, "PK\x03\x04") && hash_equals((string) $row->artifact_sha256, hash('sha256', $bytes)), 409, __('This export copy needs restoration. Prepare a new export.'));
        Audit::record('academics.exit_package_downloaded', $row);

        return $bytes;
    }

    public function failed(int $id, string $generation, string $code): void
    {
        DB::transaction(function () use ($id, $generation, $code) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $row = AcademicExitPackage::where('generation_id', $generation)->where('state', 'queued')->find($id);
            if ($row === null) {
                return;
            }
            $row->forceFill(['state' => 'failed', 'failure_code' => $code])->save();
            Audit::record('academics.exit_package_failed', $row, [], ['failure_code' => $code]);
        });
    }

    public function generate(int $id, string $generation): void
    {
        $row = AcademicExitPackage::where('state', 'queued')->where('generation_id', $generation)->find($id);
        if ($row === null) {
            return;
        }
        if ($row->expires_at->lte(now())) {
            $this->failed($id, $generation, 'expired');

            return;
        }
        try {
            $actor = User::findOrFail($row->requested_by);
            app(TransitionPolicy::class)->package($actor, $id);
            $source = app(ExitEvidence::class)->collect($actor, $row->blueprint['selection']);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $this->failed($id, $generation, 'source_or_authority_unavailable');

            return;
        }
        if (! $source['can_prepare'] || ! hash_equals($row->source_fingerprint, $source['fingerprint'])) {
            $this->failed($id, $generation, 'source_changed');

            return;
        }
        $path = app(CurrentSchool::class)->storagePath('exit-packages').'/'.$id.'/'.Str::uuid().'.enc';
        $built = $this->build($actor, $row, $source['blueprint']);
        $disk = Storage::disk(self::DISK);
        $committed = false;
        try {
            if (! $disk->put($path, Crypt::encryptString($built['bytes']))) {
                throw new RuntimeException('The private export could not be stored.');
            }
            $committed = DB::transaction(function () use ($id, $generation, $actor, $source, $path, $built) {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                $fresh = AcademicExitPackage::where('state', 'queued')->where('generation_id', $generation)->find($id);
                if ($fresh === null || $fresh->expires_at->lte(now())) {
                    return false;
                }
                try {
                    app(TransitionPolicy::class)->package($actor, $id);
                    $current = app(ExitEvidence::class)->collect($actor, $source['blueprint']['selection']);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    $fresh->forceFill(['state' => 'failed', 'failure_code' => 'source_or_authority_unavailable'])->save();

                    return false;
                }
                if (! $current['can_prepare'] || ! hash_equals($fresh->source_fingerprint, $current['fingerprint'])) {
                    $fresh->forceFill(['state' => 'failed', 'failure_code' => 'source_changed'])->save();

                    return false;
                }
                $fresh->forceFill(['state' => 'ready', 'artifact_path' => $path, 'artifact_sha256' => hash('sha256', $built['bytes']), 'artifact_bytes' => strlen($built['bytes']),
                    'manifest' => $built['manifest'], 'signing_public_key' => $built['verification']['public_key'], 'signing_key_id' => $built['verification']['key_id'], 'generated_at' => now(), 'failure_code' => null])->save();
                Audit::record('academics.exit_package_ready', $fresh, [], ['sha256' => $fresh->artifact_sha256, 'bytes' => $fresh->artifact_bytes]);

                return true;
            });
        } finally {
            if (! $committed) {
                $disk->delete($path);
            }
        }
    }

    public function scratchRoot(): string
    {
        return storage_path('app/private/academic-export-scratch');
    }

    private function build(User $actor, AcademicExitPackage $row, array $blueprint): array
    {
        $root = $this->scratchRoot();
        if (! is_dir($root) && ! mkdir($root, 0700, true) && ! is_dir($root)) {
            throw new RuntimeException('Private export scratch is unavailable.');
        }
        $scratch = tempnam($root, 'exit-');
        if ($scratch === false) {
            throw new RuntimeException('Private export scratch is unavailable.');
        }
        chmod($scratch, 0600);
        $zip = new ZipArchive;
        $opened = false;
        try {
            if ($zip->open($scratch, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('The export archive could not be created.');
            }
            $opened = true;
            $files = [];
            $total = 0;
            $add = function (string $name, string $bytes, string $mime, ?string $label = null) use ($zip, &$files, &$total, $row): void {
                $total += strlen($bytes);
                if ($total > (int) config('academic_transitions.maximum_export_bytes')) {
                    throw new RuntimeException('The selected export exceeds its size limit.');
                }
                if (! $zip->addFromString($name, $bytes)) {
                    throw new RuntimeException('An export file could not be added.');
                }
                $zip->setMtimeName($name, $row->created_at->getTimestamp());
                $files[] = ['path' => $name, 'name' => $label ?? $name, 'mime' => $mime, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
            };
            $json = fn (array $data): string => json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
            $add('released-marks.json', $json(['results' => $blueprint['released_marks']]), 'application/json');
            $states = [];
            foreach ($blueprint['attendance'] as $entry) {
                if ($entry['kind'] === 'homeroom') {
                    $states[$entry['state']] = ($states[$entry['state']] ?? 0) + 1;
                }
            }
            ksort($states);
            $add('attendance.json', $json(['through' => $blueprint['attendance_through'], 'homeroom_state_counts' => (object) $states, 'captured_entries' => $blueprint['attendance']]), 'application/json');
            $add('enrolment-history.json', $json(['entries' => $blueprint['enrolments']]), 'application/json');
            foreach ($blueprint['reports'] as $report) {
                $card = ReportCard::where('learner_id', $row->learner_id)->findOrFail($report['id']);
                $add('reports/report-'.$card->id.'.pdf', app(ReportArtifacts::class)->read($card), 'application/pdf');
            }
            $learner = Students::findOrFail($row->learner_id);
            $person = $learner->user;
            foreach ($blueprint['documents'] as $document) {
                if (! in_array($document['extension'], ['pdf', 'png', 'jpg'], true)) {
                    throw new RuntimeException('The selected document type is unavailable.');
                }
                $file = app(ProfileFiles::class)->read($actor, $person, $document['id'].'.'.$document['extension']);
                $add('documents/document-'.$document['id'].'.'.$document['extension'], $file['bytes'], $file['mime'], $file['name']);
            }
            $manifest = ['format' => 'navigateed-academic-exit-v1', 'school_id' => $blueprint['school_id'], 'school_name' => $blueprint['school_name'], 'package_id' => (int) $row->id,
                'learner_id' => $blueprint['learner_id'], 'learner_name' => $blueprint['learner_name'], 'student_number' => $blueprint['student_number'],
                // The headline number (ADR 0007), read when the archive is built; held only in the encrypted copy.
                'primary_identifier' => app(LuritsNumbers::class)->primary($learner), 'admission_no' => (string) $learner->admission_no, 'last_attended_on' => $blueprint['last_attended_on'],
                'prepared_at' => $row->created_at->toISOString(), 'files' => $files];
            $encoded = $json($manifest);
            $verification = app(ExitSigning::class)->sign($encoded);
            if (! $zip->addFromString('manifest.json', $encoded)
                || ! $zip->addFromString('manifest.signature', $verification['signature']."\n")
                || ! $zip->addFromString('verification.json', $json(array_diff_key($verification, ['signature' => true])))) {
                throw new RuntimeException('The signed manifest could not be written.');
            }
            if (! $zip->close()) {
                throw new RuntimeException('The export archive could not be completed.');
            }
            $opened = false;
            $bytes = file_get_contents($scratch);
            if ($bytes === false || strlen($bytes) > (int) config('academic_transitions.maximum_export_bytes') || ! str_starts_with($bytes, "PK\x03\x04")) {
                throw new RuntimeException('The export archive could not be read.');
            }

            return compact('bytes', 'manifest', 'verification');
        } finally {
            if ($opened) {
                $zip->close();
            }
            if (is_file($scratch)) {
                unlink($scratch);
            }
        }
    }
}
