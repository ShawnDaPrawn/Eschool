<?php

use App\Http\Controllers\Api\V2\Platform\NotificationPreferenceController;
use App\Http\Controllers\Api\V2\Platform\QuietHoursController;
use App\Http\Controllers\Api\V2\Platform\TextMessagingController;
use App\Http\Controllers\Panel\PersonalNotificationsController;
use App\Http\Middleware\PanelRecordContext;
use App\Http\Middleware\PrivateDomainResponse;
use Illuminate\Support\Facades\Route;

// Included within the existing authenticated school/language panel group.
Route::prefix('my-school')->middleware([PrivateDomainResponse::class, PanelRecordContext::class])->group(function (): void {
    Route::get('inbox', [PersonalNotificationsController::class, 'index'])->name('panel.inbox.index');
    Route::get('inbox/private', [PersonalNotificationsController::class, 'privateInbox'])->name('panel.inbox.private');
    Route::post('inbox/private/{notice}/read', [PersonalNotificationsController::class, 'markRead'])->whereNumber('notice')->name('panel.inbox.read');
    Route::get('inbox/{notice}', [PersonalNotificationsController::class, 'show'])->whereNumber('notice')->name('panel.inbox.show');
    Route::get('preferences/notifications', [PersonalNotificationsController::class, 'preferences'])->name('panel.preferences.notifications');
    Route::post('preferences/notifications', [NotificationPreferenceController::class, 'update'])->name('panel.preferences.notifications.update');
    Route::get('preferences/quiet-hours', [PersonalNotificationsController::class, 'quiet'])->name('panel.preferences.quiet');
    Route::post('preferences/quiet-hours', [QuietHoursController::class, 'update'])->name('panel.preferences.quiet.update');
    Route::get('preferences/texts', [PersonalNotificationsController::class, 'texts'])->name('panel.preferences.texts');
    Route::post('preferences/texts', [TextMessagingController::class, 'update'])->middleware('throttle:text-messaging')->name('panel.preferences.texts.update');
    Route::post('preferences/texts/code', [TextMessagingController::class, 'code'])->middleware('throttle:text-messaging')->name('panel.preferences.texts.code');
    Route::post('preferences/texts/confirm', [TextMessagingController::class, 'confirm'])->middleware('throttle:text-messaging')->name('panel.preferences.texts.confirm');
});
