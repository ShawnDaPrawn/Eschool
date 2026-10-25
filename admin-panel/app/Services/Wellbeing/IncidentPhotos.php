<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\User;
use App\Models\Wellbeing\IncidentPhoto;
use App\Models\Wellbeing\IncidentReport;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** No source filename, reporter/device/IP field, generic file id or URL. */
class IncidentPhotos
{
    public const DISK = 'wellbeing_private';

    public const LIMIT = 5;

    public function __construct(private IncidentReporting $reports, private WellbeingAccess $access, private WellbeingOperations $operations, private SafeImages $images, private UploadScanner $scanner) {}

    private function context(int $school): void
    {
        abort_unless(app(CurrentSchool::class)->id() === $school && ! app(CurrentSchool::class)->isAcrossSchools() && ! app(CurrentSchool::class)->isImpersonating(), 403);
    }

    private function scan(string $bytes): array
    {
        try {
            $receipt = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Photo safety checks are temporarily unavailable. Your report remains available.');
        }
        abort_unless(($receipt['clean'] ?? null) === true, 422, 'This photo did not pass its safety checks.');
        abort_unless(is_string($receipt['engine'] ?? null) && strlen($receipt['engine']) > 0 && strlen($receipt['engine']) <= 128 && (($receipt['signature'] ?? null) === null || (is_string($receipt['signature']) && strlen($receipt['signature']) <= 256)), 503, 'Photo safety checks are temporarily unavailable.');

        return $receipt;
    }

    private function metadata(IncidentPhoto $photo): array
    {
        return ['id' => $photo->id, 'mime' => 'image/jpeg', 'bytes' => $photo->bytes, 'width' => $photo->width, 'height' => $photo->height,
            'thumbnail_base64' => base64_encode($photo->thumbnail), 'created_at' => $photo->created_at->toISOString(), 'report_revision' => $photo->report_revision];
    }

    public function upload(string $secret, UploadedFile $file, string $operation, ?User $actor = null): array
    {
        $context = app(CurrentSchool::class);
        $school = $context->school();
        abort_unless($school !== null, 403);
        $schoolId = $context->id();
        $authorized = false;
        $path = null;
        try {
            $id = $this->reports->authorizedReportId($secret, $actor, true);
            $authorized = true;
            $this->context($schoolId);
            Validator::make(['photo' => $file, 'operation_id' => $operation], SafeImages::rules() + ['operation_id' => ['required', 'uuid']])->validate();
            $bytes = $file->getContent();
            $digest = $this->operations->digest('incident-photo:'.$bytes);
            $old = IncidentPhoto::where('incident_report_id', $id)->where('operation_id', $operation)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->source_digest, $digest), 409, 'This photo request changed. Read the current report before adding a different photo.');
                $this->reports->authorizedReportId($secret, $actor, true);

                return $this->metadata($old);
            }
            abort_if(IncidentPhoto::where('incident_report_id', $id)->count() >= self::LIMIT, 422, 'This report has reached its photo limit. Add further information in a follow-up.');
            $sourceScan = $this->scan($bytes);
            $this->context($schoolId);
            $this->reports->authorizedReportId($secret, $actor, true);
            $image = $this->images->sanitize($file);
            $cleanScan = $this->scan($image['bytes']);
            $this->context($schoolId);
            $this->reports->authorizedReportId($secret, $actor, true);
            $result = $this->operations->locked(function () use ($id, $secret, $actor, $operation, $digest, $image, $sourceScan, $cleanScan, $schoolId, &$path) {
                $this->context($schoolId);
                abort_unless($this->reports->authorizedReportId($secret, $actor, true) === $id, 403);
                $old = IncidentPhoto::where('incident_report_id', $id)->where('operation_id', $operation)->first();
                if ($old !== null) {
                    abort_unless(hash_equals($old->source_digest, $digest), 409, 'This photo request changed.');

                    return $this->metadata($old);
                }
                abort_if(IncidentPhoto::where('incident_report_id', $id)->count() >= self::LIMIT, 422, 'This report has reached its photo limit.');
                $report = IncidentReport::whereKey($id)->lockForUpdate()->firstOrFail();
                $uuid = (string) Str::uuid();
                $path = app(CurrentSchool::class)->storagePath('incident-photos').'/'.$uuid.'.enc';
                if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($image['bytes']))) {
                    throw new RuntimeException('Private photo storage is temporarily unavailable.');
                }
                $this->context($schoolId);
                $this->reports->authorizedReportId($secret, $actor, true);
                $report->forceFill(['revision' => $report->revision + 1])->save();
                $photo = IncidentPhoto::create(['id' => $uuid, 'incident_report_id' => $id, 'operation_id' => $operation, 'source_digest' => $digest,
                    'sha256' => hash('sha256', $image['bytes']), 'path' => $path, 'bytes' => strlen($image['bytes']), 'width' => $image['width'], 'height' => $image['height'],
                    'thumbnail' => $image['thumbnail'], 'scan_receipt' => ['source' => $sourceScan, 'sanitized' => $cleanScan], 'report_revision' => $report->revision]);
                $this->operations->event($report->anonymous ? null : $actor, 'wellbeing.photo_added', $report);
                $this->context($schoolId);
                $this->reports->authorizedReportId($secret, $actor, true);

                return $this->metadata($photo);
            });

            return $result;
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        } finally {
            if ($authorized && ($context->idOrNull() !== $schoolId || $context->isAcrossSchools() || $context->isImpersonating())) {
                $context->set($school);
            }
        }
    }

    public function reporter(string $secret, ?User $actor = null): array
    {
        $id = $this->reports->authorizedReportId($secret, $actor);
        $rows = IncidentPhoto::where('incident_report_id', $id)->orderBy('created_at')->orderBy('id')->limit(self::LIMIT)->get();
        $result = $rows->map(fn ($row) => $this->metadata($row))->all();
        $this->reports->authorizedReportId($secret, $actor);

        return $result;
    }

    public function staff(User $actor, int $report): array
    {
        $this->access->report($actor, $report);
        $rows = IncidentPhoto::where('incident_report_id', $report)->orderBy('created_at')->orderBy('id')->limit(self::LIMIT)->get();
        $result = $rows->map(fn ($row) => $this->metadata($row))->all();
        $this->access->report($actor, $report);

        return $result;
    }

    private function bytes(int $report, string $photo): array
    {
        $row = IncidentPhoto::where('incident_report_id', $report)->findOrFail($photo);
        abort_unless($row->path === app(CurrentSchool::class)->storagePath('incident-photos').'/'.$row->id.'.enc', 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($row->path));
        abort_unless(hash_equals($row->sha256, hash('sha256', $bytes)), 404);

        return ['bytes' => $bytes, 'mime' => 'image/jpeg', 'name' => 'report-photo.jpg'];
    }

    public function reporterDownload(string $secret, string $photo, ?User $actor = null): array
    {
        $id = $this->reports->authorizedReportId($secret, $actor);
        $result = $this->bytes($id, $photo);
        $this->reports->authorizedReportId($secret, $actor);

        return $result;
    }

    public function staffDownload(User $actor, int $report, string $photo): array
    {
        $this->access->report($actor, $report);
        $result = $this->bytes($report, $photo);
        $this->access->report($actor, $report);

        return $result;
    }
}
