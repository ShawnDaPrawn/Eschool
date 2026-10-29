<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;

/** Extracts the configured NDEF profile; the caller must still verify SUN online. */
final class VendorPaymentTapUri
{
    public static function parse(string $value): array
    {
        $url = parse_url($value);
        $base = parse_url((string) config('app.url'));
        abort_unless(strlen($value) <= 1024 && is_array($url) && is_array($base)
            && ($url['scheme'] ?? null) === ($base['scheme'] ?? null)
            && ($url['host'] ?? null) === ($base['host'] ?? null)
            && ($url['port'] ?? null) === ($base['port'] ?? null)
            && ! isset($url['user']) && ! isset($url['pass']) && ! isset($url['query'])
            && ($url['path'] ?? '') === rtrim($base['path'] ?? '', '/').'/card-tap'
            && isset($url['fragment']) && count(explode('&', $url['fragment'])) === 4, 422, 'Read this school’s current encoded identity card.');
        parse_str($url['fragment'], $fields);
        abort_unless(count($fields) === 4 && ($fields['s'] ?? null) === (string) app(CurrentSchool::class)->id(), 403);
        $data = Validator::make($fields, ['c' => ['required', 'uuid'], 'e' => ['required', 'regex:/\A[a-fA-F0-9]{32}\z/D'], 'm' => ['required', 'regex:/\A[a-fA-F0-9]{16}\z/D']])->validate();

        return ['card_id' => $data['c'], 'picc' => $data['e'], 'mac' => $data['m']];
    }
}
