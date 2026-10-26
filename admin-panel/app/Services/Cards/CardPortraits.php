<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardPortrait;
use App\Models\Cards\CardScanLog;
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

class CardPortraits
{
    public const DISK = 'cards_private';

    public function __construct(private UploadScanner $scanner, private SafeImages $images) {}

    private function authorize(User $actor, int $person, string $type, bool $write = false): void
    {
        $a = app(CardAccess::class);
        $a->actor($actor, $write);
        $a->person($person, $type);
        $a->owner($actor, new Card(['user_id' => $person, 'type' => $type]));
    }

    private function scan(string $bytes): void
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Portrait safety checks are temporarily unavailable.');
        }
        abort_unless(($result['clean'] ?? null) === true && is_string($result['engine'] ?? null) && $result['engine'] !== '', 422, 'This portrait did not pass its safety checks.');
    }

    public function upload(User $actor, int $person, string $type, string $operation, UploadedFile $file): array
    {
        $this->authorize($actor, $person, $type, true);
        Validator::make(['photo' => $file], SafeImages::rules())->validate();
        $source = $file->getContent();
        $input = ['operation_id' => $operation, 'person' => $person, 'type' => $type, 'source_hash' => hash_hmac('sha256', $source, (string) config('app.key'))];
        $this->scan($source);
        $image = $this->images->sanitize($file);
        $bytes = $image['thumbnail'];
        $this->scan($bytes);
        $path = null;
        try {
            return app(CardOperations::class)->once('user', (string) $actor->id, 'portrait.upload', $input,
                fn () => $this->authorize($actor, $person, $type, true), function () use ($person, $actor, $bytes, &$path) {
                    $portrait = CardPortrait::where('user_id', $person)->lockForUpdate()->first();
                    $old = $portrait?->path;
                    $path = app(CurrentSchool::class)->storagePath('card-portraits').'/'.Str::uuid().'.enc';
                    if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($bytes))) {
                        throw new RuntimeException('The private card portrait could not be stored.');
                    }
                    $values = ['uploaded_by' => $actor->id, 'path' => $path, 'digest' => hash('sha256', $bytes), 'size' => strlen($bytes), 'revision' => ($portrait?->revision ?? 0) + 1];
                    if ($portrait === null) {
                        $portrait = CardPortrait::create(['user_id' => $person] + $values);
                    } else {
                        $portrait->update($values);
                    }
                    if ($old !== null && $old !== $path) {
                        DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($old));
                    }

                    return ['person_id' => $person, 'portrait_id' => $portrait->id, 'revision' => (int) $portrait->revision];
                });
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }
    }

    private function bytes(CardPortrait $portrait): string
    {
        $prefix = app(CurrentSchool::class)->storagePath('card-portraits').'/';
        abort_unless(str_starts_with($portrait->path, $prefix) && preg_match('/^[a-f0-9-]{36}[.]enc$/D', substr($portrait->path, strlen($prefix))) === 1, 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($portrait->path));
        abort_unless(hash_equals($portrait->digest, hash('sha256', $bytes)), 404);

        return $bytes;
    }

    public function owner(User $actor, int $person, string $type): array
    {
        $this->authorize($actor, $person, $type);
        $portrait = CardPortrait::where('user_id', $person)->firstOrFail();

        return ['bytes' => $this->bytes($portrait), 'mime' => 'image/jpeg', 'revision' => (int) $portrait->revision];
    }

    /** A delegated gate snapshot receives only a small, private identity portrait. */
    public function offline(string $credential, Card $card): array
    {
        app(CardReaders::class)->authenticate($credential, 'gate', true);
        app(CardAccess::class)->active($card);
        $portrait = CardPortrait::where('user_id', $card->user_id)->firstOrFail();
        $bytes = $this->bytes($portrait);
        $source = imagecreatefromstring($bytes);
        abort_unless($source !== false, 503, 'The private portrait is unavailable.');
        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, 128 / max($width, $height));
        $thumbnail = imagescale($source, max(1, (int) floor($width * $ratio)), max(1, (int) floor($height * $ratio)));
        imagedestroy($source);
        abort_unless($thumbnail !== false, 503);
        ob_start();
        try {
            $ok = imagejpeg($thumbnail, null, 70);
            $small = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($thumbnail);
        }
        abort_unless($ok && is_string($small) && strlen($small) <= 65536, 503);
        app(CardReaders::class)->authenticate($credential, 'gate', true);
        app(CardAccess::class)->active($card->fresh());
        abort_unless((int) $portrait->revision === (int) CardPortrait::whereKey($portrait->id)->value('revision'), 409);

        return ['bytes' => $small, 'revision' => (int) $portrait->revision];
    }

    /** The scanner may display only its own freshly accepted scan's portrait. */
    public function scanned(string $credential, string $scanId): array
    {
        $device = app(CardReaders::class)->authenticate($credential, 'gate');
        $scan = CardScanLog::where('device_id', $device->id)->where('outcome', 'accepted')
            ->where('scanned_at', '>', now()->utc()->subMinute())->findOrFail($scanId);
        $card = Card::findOrFail($scan->card_id);
        app(CardAccess::class)->active($card);
        $portrait = CardPortrait::where('user_id', $card->user_id)->firstOrFail();

        return ['bytes' => $this->bytes($portrait), 'mime' => 'image/jpeg', 'revision' => (int) $portrait->revision];
    }
}
