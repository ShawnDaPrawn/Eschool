<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Exceptions\ApiV2ExceptionRenderer;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Capability negotiation protects legacy editors; it never grants authority. */
class ActivityMessageCapability
{
    public function requireSupport(bool $supported): void
    {
        if (! $supported) {
            throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(409,
                __('Update your app to work with activity group messages.'),
                headers: ['Cache-Control' => 'private, no-store']));
        }
    }
}
