<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\RotateBrowserSessionIdentity;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [RotateBrowserSessionIdentity::class],
        Logout::class => [RotateBrowserSessionIdentity::class],
        CurrentDeviceLogout::class => [RotateBrowserSessionIdentity::class],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
