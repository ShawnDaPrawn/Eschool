<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Wiring for the platform kernel (app/Services/Platform): queued delivery,
 * mail webhooks, the outbox, the audit log and money. Settings are in
 * config/platform.php.
 */
class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // SNS posts bounce, complaint and delivery notices from many addresses;
        // a burst after a bulk send stays well under this.
        RateLimiter::for('mail-webhook', fn (Request $request) => Limit::perMinute(600)->by('mail-webhook:'.$request->ip()));
    }
}
