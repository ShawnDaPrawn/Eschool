<?php

use App\Models\Platform\SchoolMembership;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Notifications\NotificationTypes;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Secrets\SecretSettings;
use App\Services\Platform\Tenancy\CurrentSchool;
use Google\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Push notifications through FCM HTTP v1, queued: each device (or topic) gets
 * its own SendPush job and notification_deliveries rows, so the request that
 * triggers a push no longer waits for Google. The messages are the ones these
 * helpers always sent: Android gets data only, iOS a notification block plus
 * the same data.
 */
function sendSimpleNotification($user, $title, $body, $type, $image, $userinfo)
{
    if ($type == 'chat') {
        $userDetails = $userinfo;
        $userinfo = json_encode($userDetails);

        $notification_data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'image' => $image,
            'sender_info' => $userinfo,
        ];

    } elseif ($type == 'fees-due') {
        $notification_data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'image' => $image,
            'child_id' => $userinfo,
        ];
    } else {
        $notification_data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'image' => $image,
        ];
    }

    $recipients = User::query()->whereIn('id', collect($user)->unique()->all())->get();
    if ($recipients->isEmpty()) {
        return;
    }
    $notifier = app(Notifier::class);
    $canonical = NotificationTypes::legacy((string) $type);
    $link = NotificationTypes::link($canonical);
    $notice = $notifier->makeNotice((string) $type, (string) $title, (string) $body, $link);
    $notice->forceFill(['delivery_payload' => $notification_data])->save();
    $notifier->assign($notice, $recipients->pluck('id')->all());
}

/** Kept for installed callers; recipient preferences require per-person delivery. */
function sendNotificationToTopic($title, $body, $type, $image, $userinfo)
{
    $role = match ((string) $userinfo) {
        'all' => null,
        'students' => 'Student',
        'parents' => 'Parent',
        'teachers' => 'Teacher',
        default => throw new InvalidArgumentException('Unknown notification audience'),
    };
    $members = SchoolMembership::query()->active()
        ->where('school_id', app(CurrentSchool::class)->id())->when($role !== null, fn ($query) => $query->where('role', $role))->pluck('user_id');
    sendSimpleNotification($members, $title, $body, $type, $image, null);
}

function getAccessToken()
{
    try {
        // FCM_SERVICE_ACCOUNT_JSON (Secret Manager) wins over the uploaded file.
        $credentials = SecretSettings::fcmCredentials();
        if ($credentials !== null) {
            return Cache::remember('fcm-access-token:'.md5((string) json_encode($credentials)), now()->addMinutes(50), function () use ($credentials) {
                $client = new Client;
                $client->setHttpClient(new GuzzleHttp\Client(['connect_timeout' => 5, 'timeout' => 10]));
                $client->setAuthConfig($credentials);
                $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

                return $client->fetchAccessTokenWithAssertion()['access_token'];
            });
        }

        $file_name = Settings::select('message')->where('type', 'service_account_file')->pluck('message')->first();

        if (! $file_name) {
            Log::error('Firebase service account file not configured');

            return null;
        }

        // The file is kept on the private local disk (storage/app). Installs that
        // uploaded it before still have it on the public disk until the FCM
        // settings are saved again, which moves it.
        $file_path = Storage::disk('local')->path($file_name);
        if (! file_exists($file_path)) {
            $file_path = Storage::disk('public')->path($file_name);
        }

        if (! file_exists($file_path)) {
            Log::error('Firebase service account file not found: '.$file_path);

            return null;
        }

        // Check if Google Client class exists
        if (! class_exists('Google\Client')) {
            Log::error('Google Client library not installed. Please install: composer require google/apiclient');

            return null;
        }

        // Tokens last an hour; reuse one instead of calling Google for every push.
        // Keyed on the file, so uploading a new service account takes effect at once.
        return Cache::remember('fcm-access-token:'.md5($file_path.'|'.filemtime($file_path)), now()->addMinutes(50), function () use ($file_path) {
            $client = new Client;
            $client->setHttpClient(new GuzzleHttp\Client(['connect_timeout' => 5, 'timeout' => 10]));
            $client->setAuthConfig($file_path);
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

            return $client->fetchAccessTokenWithAssertion()['access_token'];
        });
    } catch (Exception $e) {
        Log::error('Error getting Firebase access token: '.$e->getMessage());

        return null;
    }
}
