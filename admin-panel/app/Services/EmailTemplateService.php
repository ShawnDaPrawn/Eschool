<?php

declare(strict_types=1);

namespace App\Services;

class EmailTemplateService
{
    /**
     * Fills the {$name} / {name} placeholders of a stored email template with
     * HTML-escaped values. Templates are edited in the admin panel, so they are
     * never compiled as Blade (which would run any PHP written into them).
     * Placeholders without a value are left as they are.
     */
    public static function render(string $template, array $data): string
    {
        return preg_replace_callback('/\{\$?(\w+)\s*\}/', static function (array $match) use ($data) {
            if (! array_key_exists($match[1], $data) || ! is_scalar($data[$match[1]]) && $data[$match[1]] !== null) {
                return $match[0];
            }

            return e((string) $data[$match[1]]);
        }, $template);
    }
}
