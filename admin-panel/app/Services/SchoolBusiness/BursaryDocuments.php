<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\SchoolBusiness\BursaryApplication;
use App\Models\SchoolBusiness\BursaryDocument;
use App\Models\SchoolBusiness\BursaryEvent;
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

/**
 * Bursary supporting documents, the admissions pattern: scanned before
 * storage (photos are re-encoded and scanned again), encrypted on a private
 * disk under the school's own folder, checked by size and hash on every
 * read, and only for the applying guardian or the bursary committee.
 */
class BursaryDocuments
{
    public const DISK = 'business_private';

    public const CATEGORIES = ['income_proof', 'bank_statement', 'expenses', 'other'];

    public function __construct(private UploadScanner $scanner, private SafeImages $images) {}

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'document' => ['required', 'file', 'mimes:pdf,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:'.intdiv((int) config('school_business.maximum_document_bytes'), 1024)]];
    }

    private function scan(string $bytes): string
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Document safety checks are temporarily unavailable. Your application remains saved.');
        }
        abort_unless($result['clean'] === true, 422, 'This document did not pass its safety checks.');
        abort_unless($result['engine'] !== '' && strlen($result['engine']) <= 120, 503, 'Document safety checks are temporarily unavailable.');

        return $result['engine'];
    }

    public function upload(User $actor, string $publicId, array $input, UploadedFile $file): array
    {
        $bursaries = app(Bursaries::class);
        $bursaries->owned($actor, $publicId, true);
        $data = Validator::make($input + ['document' => $file], self::rules())->validate();
        $source = $file->getContent();
        $digest = hash_hmac('sha256', $data['category'].':'.$source, (string) config('app.key'));
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
        $school = app(CurrentSchool::class)->id();
        $path = null;
        try {
            return DB::transaction(function () use ($actor, $publicId, $data, $digest, $bytes, $mime, $scanner, $school, &$path): array {
                $application = app(Bursaries::class)->owned($actor, $publicId, true);
                abort_unless($application->state === 'draft' && app(CurrentSchool::class)->id() === $school, 409, 'Documents are added before the application is sent.');
                $existing = BursaryDocument::where('application_id', $application->id)->where('operation_id', $data['operation_id'])->first();
                if ($existing !== null) {
                    abort_unless(hash_equals($existing->source_digest, $digest), 409, 'This document retry has different contents.');

                    return $this->describe($existing);
                }
                abort_if(BursaryDocument::where('application_id', $application->id)->count() >= (int) config('school_business.maximum_documents'), 422, 'Remove a document before adding another.');
                $handle = (string) Str::uuid();
                $path = app(CurrentSchool::class)->storagePath('bursaries/'.$application->public_id).'/'.$handle.'.enc';
                if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($bytes))) {
                    throw new RuntimeException('Private document storage is unavailable.');
                }
                $document = BursaryDocument::create(['application_id' => $application->id, 'handle' => $handle, 'operation_id' => $data['operation_id'], 'source_digest' => $digest,
                    'category' => $data['category'], 'path' => $path, 'mime' => $mime, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'scanner' => $scanner, 'scanned_at' => now('UTC')]);
                $application->update(['revision' => $application->revision + 1]);
                app(Bursaries::class)->owned($actor, $publicId, true);
                BursaryEvent::create(['application_id' => $application->id, 'actor_id' => $actor->id, 'action' => 'document_added', 'created_at' => now('UTC')]);

                return $this->describe($document) + ['revision' => $application->revision];
            }, 3);
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }
    }

    private function describe(BursaryDocument $document): array
    {
        return ['id' => $document->handle, 'category' => $document->category, 'mime' => $document->mime, 'bytes' => $document->bytes];
    }

    /** Bytes for the applying guardian, or for the committee ($staff). */
    public function read(User $actor, string $publicId, string $handle, bool $staff): array
    {
        $bursaries = app(Bursaries::class);
        $application = $staff ? $bursaries->staffApplication($actor, $publicId) : $bursaries->owned($actor, $publicId);
        abort_unless(Str::isUuid($handle), 404);
        $document = BursaryDocument::where('application_id', $application->id)->where('handle', $handle)->firstOrFail();
        abort_unless((int) $document->school_id === app(CurrentSchool::class)->id(), 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($document->path));
        if (strlen($bytes) !== $document->bytes || ! hash_equals($document->sha256, hash('sha256', $bytes))) {
            throw new RuntimeException('This private document is unavailable.');
        }
        $staff ? $bursaries->staffApplication($actor, $publicId) : $bursaries->owned($actor, $publicId);

        return ['bytes' => $bytes, 'mime' => $document->mime, 'name' => $document->handle.($document->mime === 'application/pdf' ? '.pdf' : '.jpg')];
    }

    /** Erase one application's document bytes and rows (retention). */
    public function erase(BursaryApplication $application): void
    {
        $prefix = app(CurrentSchool::class)->storagePath('bursaries').'/';
        foreach (BursaryDocument::where('application_id', $application->id)->get() as $document) {
            if (! str_starts_with($document->path, $prefix) || str_contains($document->path, '..')) {
                throw new RuntimeException('Private document retention needs review.');
            }
            // Bytes first: a failed delete keeps the row so the next run retries.
            if (Storage::disk(self::DISK)->exists($document->path) && ! Storage::disk(self::DISK)->delete($document->path)) {
                throw new RuntimeException('Private document retention is temporarily unavailable.');
            }
            $document->delete();
        }
    }
}
