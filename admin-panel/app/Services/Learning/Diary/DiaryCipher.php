<?php

declare(strict_types=1);

namespace App\Services\Learning\Diary;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Encrypts a learner's diary text with a key of their own: HKDF-SHA256 over a
 * root secret, bound to the school and the user. Another learner's key cannot
 * read it. The root is DIARY_KEY, or APP_KEY until DIARY_KEY is set; every
 * ciphertext names the root it was made with ("<root ref>.<payload>"), so
 * setting DIARY_KEY later keeps older entries readable while new text uses it.
 * Nothing here logs or returns plaintext to anyone but the caller that already
 * holds the owner's identity.
 */
class DiaryCipher
{
    private const PURPOSE = 'navigateed.learner-diary.v1';

    public function encrypt(int $userId, ?string $plain): ?string
    {
        if ($plain === null) {
            return null;
        }
        [$ref, $root] = $this->roots()[0];

        return $ref.'.'.$this->encrypter($root, $userId)->encryptString($plain);
    }

    public function decrypt(int $userId, ?string $cipher): ?string
    {
        if ($cipher === null) {
            return null;
        }
        [$ref, $payload] = array_pad(explode('.', $cipher, 2), 2, '');
        foreach ($this->roots() as [$candidate, $root]) {
            if (hash_equals($candidate, $ref)) {
                return $this->encrypter($root, $userId)->decryptString($payload);
            }
        }

        throw new RuntimeException('This diary text was encrypted with a key that is no longer configured.');
    }

    private function encrypter(string $root, int $userId): Encrypter
    {
        $school = app(CurrentSchool::class)->id();
        if (! $school || $userId < 1) {
            throw new RuntimeException('A diary key needs a school and an owner.');
        }

        return new Encrypter(hash_hkdf('sha256', $root, 32, self::PURPOSE.':'.$school.':'.$userId), 'aes-256-gcm');
    }

    /** @return list<array{0: string, 1: string}> [ref, root bytes], the one used for new text first. */
    private function roots(): array
    {
        $roots = [];
        foreach ([config('services.diary.key'), config('app.key')] as $configured) {
            $bytes = $this->bytes((string) $configured);
            if ($bytes !== null) {
                $roots[] = [substr(hash('sha256', self::PURPOSE.':ref:'.$bytes), 0, 12), $bytes];
            }
        }
        if ($roots === []) {
            throw new RuntimeException('DIARY_KEY (or APP_KEY) must hold at least 32 bytes.');
        }

        return $roots;
    }

    private function bytes(string $configured): ?string
    {
        if (str_starts_with($configured, 'base64:')) {
            $configured = base64_decode(substr($configured, 7), true) ?: '';
        }

        return strlen($configured) >= 32 ? $configured : null;
    }
}
