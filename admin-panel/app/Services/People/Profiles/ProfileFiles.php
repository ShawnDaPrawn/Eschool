<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\ProfileFile;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Private ciphertext, served only after fresh person and field authorization. */
class ProfileFiles
{
    public const DISK = 'profile_private';

    public const MAX_KB = 5120;

    public static function rules(): array
    {
        return ['document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'extensions:pdf,png,jpg,jpeg', 'max:'.self::MAX_KB]];
    }

    private function field(User $actor, User $person, string $profile, string $key, bool $write = false, bool $lock = false): ProfileFieldDefinition
    {
        $policy = app(ProfilePolicy::class);
        $context = $policy->resolve($actor, $person, $profile);
        $query = ProfileFieldDefinition::query()->where('profile_type', $profile)->where('key', $key)->where('type', 'file')->whereHas('category');
        if ($lock) {
            $query->lockForUpdate();
        }
        $field = $query->firstOrFail();
        abort_unless($policy->field($context, $field, write: $write), 404);

        return $field;
    }

    public function upload(User $actor, User $person, string $profile, string $key, UploadedFile $document, ?Closure $authorize = null): void
    {
        $authorize?->__invoke();
        $definition = $this->field($actor, $person, $profile, $key, write: true);
        Validator::make(['document' => $document], self::rules())->validate();
        $health = $definition->category()->where('key', 'health')->exists();
        $prepared = $health ? app(HealthDocumentSafety::class)->prepare($document)
            : ['bytes' => $document->getContent(), 'mime' => $document->getMimeType(), 'name' => FileNames::forUpload($document)];
        $id = (string) Str::uuid();
        $path = app(CurrentSchool::class)->storagePath('profile-files').'/'.$id.'.enc';
        try {
            DB::transaction(function () use ($actor, $person, $profile, $key, $id, $path, $authorize, $prepared, $health) {
                $authorize?->__invoke();
                $freshPerson = User::query()->lockForUpdate()->findOrFail($person->id);
                $field = $this->field($actor, $freshPerson, $profile, $key, write: true, lock: true);
                abort_unless($field->category()->where('key', 'health')->exists() === $health, 409, 'The document field changed. Refresh before uploading.');
                $bytes = $prepared['bytes'];
                if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($bytes))) {
                    throw new RuntimeException('The profile file could not be stored.');
                }
                $mime = $prepared['mime'];
                $extension = match ($mime) {
                    'application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg', default => throw new RuntimeException('Unsupported profile file.')
                };
                ProfileFile::query()->create(['id' => $id, 'user_id' => $person->id, 'definition_id' => $field->id,
                    'name' => $prepared['name'], 'path' => $path, 'mime' => $mime, 'extension' => $extension, 'size' => strlen($bytes)]);
                ProfileFieldValue::query()->updateOrCreate(['user_id' => $person->id, 'definition_id' => $field->id],
                    ['value' => Crypt::encryptString(json_encode($id, JSON_THROW_ON_ERROR)), 'encrypted' => true]);
                Audit::record('profile.file_uploaded', $person, [], ['field_key' => $key, 'file_id' => $id], schoolId: app(CurrentSchool::class)->id());
                Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'profile.files'));
                $authorize?->__invoke();
            });
        } catch (Throwable $error) {
            Storage::disk(self::DISK)->delete($path);
            throw $error;
        }
    }

    /** Called by ProfileForms only after filtering permitted definitions. */
    public function metadata(User $person, ProfileFieldDefinition $field, string $id): array
    {
        $file = ProfileFile::query()->where('user_id', $person->id)->where('definition_id', $field->id)->findOrFail($id);
        $token = $file->id.'.'.$file->extension;

        return ['id' => $token, 'name' => $file->name, 'mime' => $file->mime, 'size' => $file->size,
            'url' => route('profile-files.person', ['person' => $person->id, 'file' => $token])];
    }

    /** @return array{bytes: string, mime: string, name: string, token: string} */
    public function read(User $actor, User $person, string $token): array
    {
        $id = Str::beforeLast($token, '.');
        $file = ProfileFile::query()->where('user_id', $person->id)->findOrFail($id);
        abort_unless(hash_equals($file->id.'.'.$file->extension, $token), 404);
        $definition = ProfileFieldDefinition::query()->findOrFail($file->definition_id);
        $field = $this->field($actor, $person, $definition->profile_type, $definition->key);
        $value = ProfileFieldValue::query()->where('user_id', $person->id)->where('definition_id', $field->id)->first();
        abort_if($value === null || $value->value === null || ! $value->encrypted, 404);
        // Replaced or cleared files cannot be reopened through an old URL.
        $current = json_decode(Crypt::decryptString($value->value), true, flags: JSON_THROW_ON_ERROR);
        abort_unless(is_string($current) && hash_equals($file->id, $current), 404);
        abort_unless($file->path === app(CurrentSchool::class)->storagePath('profile-files').'/'.$file->id.'.enc', 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($file->path));
        Audit::record('profile.file_viewed', $person, [], ['field_key' => $field->key, 'file_id' => $file->id], schoolId: app(CurrentSchool::class)->id());

        return ['bytes' => $bytes, 'mime' => $file->mime, 'name' => $file->name, 'token' => $token];
    }
}
