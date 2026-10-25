<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationDocument;
use App\Models\Admissions\Intake;
use App\Models\User;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ApplicationDocuments
{
    public const DISK = 'admissions_private';

    public function __construct(private UploadScanner $scanner, private SafeImages $images) {}

    private function scan(string $bytes): string
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Document safety checks are temporarily unavailable. Your application remains saved.');
        }
        abort_unless(($result['clean'] ?? null) === true, 422, 'This document did not pass its safety checks.');
        abort_unless(is_string($result['engine'] ?? null) && strlen($result['engine']) > 0 && strlen($result['engine']) <= 120, 503, 'Document safety checks are temporarily unavailable.');

        return $result['engine'];
    }

    public function upload(int $applicationId, string $key, string $category, string $operation, UploadedFile $file): ApplicationDocument
    {
        $authority = app(IntakeAuthority::class);
        $row = $authority->owner($applicationId, $key, true);
        $school = app(CurrentSchool::class)->id();
        abort_unless($row->state === 'draft', 409, 'Submitted application documents remain unchanged for office review.');
        $intake = Intake::findOrFail($row->intake_id);
        $authority->open($intake);
        abort_unless(in_array($category, $intake->required_documents, true), 422, 'Choose a document category requested by this intake.');
        Validator::make(['document' => $file, 'operation_id' => $operation], ['document' => ['required', 'file', 'mimes:pdf,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:'.intdiv((int) config('admissions.maximum_document_bytes'), 1024)], 'operation_id' => ['required', 'uuid']])->validate();
        $source = $file->getContent();
        $digest = hash_hmac('sha256', $category.':'.$source, (string) config('app.key'));
        $existing = ApplicationDocument::where('application_id', $applicationId)->where('operation_id', $operation)->first();
        if ($existing !== null) {
            abort_unless(hash_equals($existing->source_digest, $digest), 409, 'This document retry has different contents.');
            $authority->owner($applicationId, $key, true);

            return $existing;
        }
        $scanner = $this->scan($source);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($source);
        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $bytes = $this->images->sanitize($file)['bytes'];
            $scanner = $this->scan($bytes);
            $mime = 'image/jpeg';
        } else {
            abort_unless($mime === 'application/pdf' && str_starts_with($source, '%PDF-'), 422, 'Choose a valid PDF or photo.');
            $bytes = $source;
        }
        $path = null;
        try {
            return DB::transaction(function () use ($applicationId, $key, $category, $operation, $digest, $bytes, $mime, $scanner, $school, &$path): ApplicationDocument {
                $row = Application::lockForUpdate()->findOrFail($applicationId);
                $fresh = app(IntakeAuthority::class)->owner($applicationId, $key, true);
                abort_unless($fresh->state === 'draft' && app(CurrentSchool::class)->id() === $school, 409);
                app(IntakeAuthority::class)->open(Intake::findOrFail($row->intake_id));
                $existing = ApplicationDocument::where('application_id', $applicationId)->where('operation_id', $operation)->first();
                if ($existing !== null) {
                    abort_unless(hash_equals($existing->source_digest, $digest), 409);

                    return $existing;
                }
                abort_if(ApplicationDocument::where('application_id', $applicationId)->count() >= (int) config('admissions.maximum_documents'), 422, 'Remove a document before adding another.');
                $uuid = (string) Str::uuid();
                $path = app(CurrentSchool::class)->storagePath('applications/'.$row->public_id).'/'.$uuid.'.enc';
                if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($bytes))) {
                    throw new RuntimeException('Private document storage is unavailable.');
                }
                app(IntakeAuthority::class)->owner($applicationId, $key, true);
                $document = ApplicationDocument::create(['application_id' => $applicationId, 'handle' => $uuid, 'operation_id' => $operation, 'source_digest' => $digest, 'category' => $category,
                    'path' => $path, 'mime' => $mime, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'scanner' => $scanner, 'scanned_at' => now()]);
                $row->update(['revision' => $row->revision + 1]);
                app(AdmissionEvents::class)->record($row, 'document_added', details: ['document_id' => (int) $document->id, 'category' => $category]);

                return $document;
            }, 3);
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }
    }

    public function ownerBytes(int $applicationId, string $key, string $handle): array
    {
        app(IntakeAuthority::class)->owner($applicationId, $key);
        $row = ApplicationDocument::where('application_id', $applicationId)->where('handle', $handle)->firstOrFail();
        $bytes = $this->bytes($row);
        app(IntakeAuthority::class)->owner($applicationId, $key);

        return ['bytes' => $bytes, 'mime' => $row->mime];
    }

    public function staffBytes(User $actor, int $applicationId, string $handle): array
    {
        app(IntakeAuthority::class)->application($actor, $applicationId);
        $row = ApplicationDocument::where('application_id', $applicationId)->where('handle', $handle)->firstOrFail();
        $bytes = $this->bytes($row);
        app(IntakeAuthority::class)->application($actor, $applicationId);

        return ['bytes' => $bytes, 'mime' => $row->mime];
    }

    private function bytes(ApplicationDocument $document): string
    {
        abort_unless((int) $document->school_id === app(CurrentSchool::class)->id(), 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($document->path));
        if (strlen($bytes) !== (int) $document->bytes || ! hash_equals($document->sha256, hash('sha256', $bytes))) {
            throw new RuntimeException('This private document is unavailable.');
        }

        return $bytes;
    }

    public function remove(int $applicationId, string $key, string $handle): void
    {
        app(IntakeAuthority::class)->owner($applicationId, $key, true);
        DB::transaction(function () use ($applicationId, $key, $handle): void {
            $row = Application::lockForUpdate()->findOrFail($applicationId);
            app(IntakeAuthority::class)->owner($applicationId, $key, true);
            abort_unless($row->state === 'draft', 409);
            $document = ApplicationDocument::where('application_id', $applicationId)->where('handle', $handle)->firstOrFail();
            $path = $document->path;
            $document->delete();
            $row->update(['revision' => $row->revision + 1]);
            app(AdmissionEvents::class)->record($row, 'document_removed');
            DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($path));
        }, 3);
    }
}
