<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use Illuminate\Http\UploadedFile;
use Throwable;

/** One mandatory pipeline for Health files, regardless of the HTTP entrypoint. */
class HealthDocumentSafety
{
    public function __construct(private UploadScanner $scanner, private SafeImages $images) {}

    private function scan(string $bytes): void
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Document safety checks are temporarily unavailable.');
        }
        abort_unless(($result['clean'] ?? false) === true, 422, 'This document did not pass its safety checks.');
        abort_unless(is_string($result['engine'] ?? null) && strlen($result['engine']) > 0 && strlen($result['engine']) <= 120, 503);
    }

    /** Called only after current person/field authorization and file validation. */
    public function prepare(UploadedFile $document): array
    {
        $source = $document->getContent();
        $this->scan($source);
        if ($document->getMimeType() === 'application/pdf') {
            return ['bytes' => $source, 'mime' => 'application/pdf', 'name' => FileNames::forUpload($document)];
        }
        $bytes = $this->images->sanitize($document)['bytes'];
        $this->scan($bytes);
        $name = pathinfo(FileNames::forUpload($document), PATHINFO_FILENAME).'.jpg';

        return ['bytes' => $bytes, 'mime' => 'image/jpeg', 'name' => FileNames::sanitize($name)];
    }
}
