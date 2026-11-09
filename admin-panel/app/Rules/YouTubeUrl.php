<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class YouTubeUrl implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return self::videoId($value) !== null;
    }

    /** Validate the entire URL, not just a prefix preceding attacker-controlled HTML. */
    public static function videoId(mixed $value): ?string
    {
        if (! is_string($value) || strlen($value) > 2048
            || preg_match('/[\x00-\x20\x7f<>"\'\\\\]/', $value)) {
            return null;
        }

        // Keep the scheme-less links accepted by existing clients.
        $url = preg_match('#^https?://#i', $value) ? $value : 'https://'.$value;
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        if (in_array($host, ['youtu.be', 'www.youtu.be', 'm.youtu.be'], true)) {
            $id = substr($path, 1);
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? $query['vi'] ?? null;
            } elseif (preg_match('#^/(?:embed|v|vi|user|shorts|live)/([A-Za-z0-9_-]{11})$#D', $path, $matches)) {
                $id = $matches[1];
            }
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : null;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('please_enter_valid_youtube_url');
    }
}
