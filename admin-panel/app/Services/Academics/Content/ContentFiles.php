<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentSource;
use App\Models\User;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ContentFiles
{
    public const DISK = 'academic_private';

    public const DOCUMENTS = ['pdf', 'docx', 'pptx', 'xlsx', 'txt', 'md'];

    public const MEDIA = ['wav', 'mp3', 'mp4', 'm4a', 'webm', 'ogg'];

    public static function rules(): array
    {
        return ['document' => ['required', 'file', 'max:'.intdiv((int) config('academic_content.maximum_source_bytes'), 1024)]];
    }

    public function inspect(UploadedFile $file): array
    {
        Validator::make(['document' => $file], self::rules())->validate();
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = [
            'pdf' => ['application/pdf'], 'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'pptx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], 'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'txt' => ['text/plain'], 'md' => ['text/plain', 'text/markdown'], 'wav' => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave'],
            'mp3' => ['audio/mpeg'], 'mp4' => ['video/mp4', 'audio/mp4'], 'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
            'webm' => ['video/webm', 'audio/webm'], 'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
        ];
        $mime = $file->getMimeType();
        abort_unless(isset($allowed[$extension]) && in_array($mime, $allowed[$extension], true), 422, __('Choose a PDF, modern Office document, text, audio or video file.'));
        $body = $file->getContent();
        abort_if($body === '' || strlen($body) > (int) config('academic_content.maximum_source_bytes'), 422, __('The file exceeds its safe size limit.'));

        return ['name' => FileNames::forUpload($file), 'mime' => $mime, 'extension' => $extension, 'bytes' => strlen($body), 'sha256' => hash('sha256', $body), 'body' => $body];
    }

    public function write(ContentItem $item, User $actor, array $file): ContentSource
    {
        $path = app(CurrentSchool::class)->storagePath('content-sources').'/'.$item->id.'/'.Str::uuid().'.enc';
        $disk = Storage::disk(self::DISK);
        if (! $disk->put($path, Crypt::encryptString($file['body']))) {
            throw new RuntimeException('content_storage_unavailable');
        }
        try {
            return ContentSource::create(array_diff_key($file, ['body' => true]) + ['path' => $path, 'content_item_id' => $item->id, 'uploaded_by' => $actor->id, 'scan_state' => 'pending']);
        } catch (Throwable $e) {
            $disk->delete($path);
            throw $e;
        }
    }

    public function validPath(ContentSource $source): bool
    {
        $prefix = app(CurrentSchool::class)->storagePath('content-sources').'/'.$source->content_item_id.'/';

        return is_string($source->path) && str_starts_with($source->path, $prefix) && str_ends_with($source->path, '.enc') && Str::isUuid(substr($source->path, strlen($prefix), -4));
    }

    public function read(ContentSource $source): string
    {
        abort_unless($this->validPath($source), 409, __('The private source needs restoration.'));
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($source->path) && $disk->size($source->path) <= (int) config('academic_content.maximum_source_bytes') * 3, 409, __('The private source needs restoration.'));
        try {
            $bytes = Crypt::decryptString($disk->get($source->path));
        } catch (Throwable) {
            abort(409, __('The private source needs restoration.'));
        }
        abort_unless(strlen($bytes) === $source->bytes && hash_equals($source->sha256, hash('sha256', $bytes)), 409, __('The private source needs restoration.'));

        return $bytes;
    }

    /** Cleanup only a definitely rolled-back source, never an uncertain commit. */
    public function discardUncommitted(ContentSource $source): void
    {
        try {
            if (! ContentSource::where('path', $source->path)->exists() && $this->validPath($source)) {
                Storage::disk(self::DISK)->delete($source->path);
            }
        } catch (Throwable) { /* A private encrypted orphan is safer than deleting an uncertain commit. */
        }
    }
}
