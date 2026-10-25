<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\CardKey;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class CardSigningKeys
{
    public function current(): CardKey
    {
        return DB::transaction(function () {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $now = now()->utc();
            $key = CardKey::whereNull('revoked_at')->where('starts_at', '<=', $now)->where('sign_until', '>', $now)->orderByDesc('starts_at')->first();
            if ($key !== null) {
                return $key;
            }
            $pair = sodium_crypto_sign_keypair();
            $row = CardKey::create(['public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'secret_key' => base64_encode(sodium_crypto_sign_secretkey($pair)),
                'starts_at' => $now, 'sign_until' => $now->copy()->addDay(), 'verify_until' => $now->copy()->addDay()->addMinute()]);
            sodium_memzero($pair);

            return $row;
        }, 3);
    }

    public function forVerification(string $id, int $issued): CardKey
    {
        $key = CardKey::whereNull('revoked_at')->where('verify_until', '>', now()->utc())->findOrFail($id);
        abort_unless($key->starts_at->getTimestamp() <= $issued && $issued < $key->sign_until->getTimestamp(), 403);

        return $key;
    }
}
