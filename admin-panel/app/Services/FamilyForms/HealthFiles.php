<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\ProfileFile;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/** Reuses encrypted profile attachments, with current family purpose checked at every stage. */
class HealthFiles
{
    public function __construct(private UploadScanner $scanner, private SafeImages $images) {}

    public function authorizeField(User $actor, int $learner, string $key, bool $write = false): User
    {
        $person = app(HealthForms::class)->person($actor, $learner, $write);
        $field = ProfileFieldDefinition::where('profile_type', 'learner')->where('key', $key)->where('type', 'file')
            ->whereHas('category', fn ($q) => $q->where('key', 'health'))->firstOrFail();
        $policy = app(ProfilePolicy::class);
        abort_unless($policy->field($policy->resolve($actor, $person, 'learner'), $field, write: $write), 404);

        return $person;
    }

    private function scan(string $bytes): void
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Document safety checks are temporarily unavailable.');
        }
        abort_unless(($result['clean'] ?? false) === true && is_string($result['engine'] ?? null) && $result['engine'] !== '', 422, 'This document did not pass its safety checks.');
    }

    public function upload(User $actor, int $learner, string $key, UploadedFile $file): array
    {
        $person = $this->authorizeField($actor, $learner, $key, true);
        Validator::make(['document' => $file], ProfileFiles::rules())->validate();
        $this->scan($file->getContent());
        $path = null;
        try {
            if ($file->getMimeType() !== 'application/pdf') {
                $bytes = $this->images->sanitize($file)['bytes'];
                $this->scan($bytes);
                $path = tempnam(sys_get_temp_dir(), 'health-image-');
                abort_unless($path !== false && file_put_contents($path, $bytes) === strlen($bytes), 503, 'The document could not be prepared.');
                $file = new UploadedFile($path, 'health-document.jpg', 'image/jpeg', null, true);
            }
            app(ProfileFiles::class)->upload($actor, $person, 'learner', $key, $file, fn () => $this->authorizeField($actor, $learner, $key, true));

            return app(HealthForms::class)->read($actor, $learner);
        } finally {
            if (is_string($path)) {
                @unlink($path);
            }
        }
    }

    private function reference(User $actor, int $learner, string $token): User
    {
        $person = app(HealthForms::class)->person($actor, $learner);
        $file = ProfileFile::where('user_id', $person->id)->findOrFail(Str::beforeLast($token, '.'));
        $definition = ProfileFieldDefinition::findOrFail($file->definition_id);
        $this->authorizeField($actor, $learner, $definition->key);
        $value = ProfileFieldValue::where('user_id', $person->id)->where('definition_id', $definition->id)->firstOrFail();
        abort_unless($value->encrypted && is_string($value->value)
            && json_decode(Crypt::decryptString($value->value), true, flags: JSON_THROW_ON_ERROR) === $file->id
            && hash_equals($file->id.'.'.$file->extension, $token), 404);

        return $person;
    }

    public function read(User $actor, int $learner, string $token): array
    {
        $person = $this->reference($actor, $learner, $token);
        $content = app(ProfileFiles::class)->read($actor, $person, $token);
        $this->reference($actor, $learner, $token);

        return $content;
    }
}
