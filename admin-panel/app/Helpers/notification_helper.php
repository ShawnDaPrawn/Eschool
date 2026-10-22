<?php

use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Notifications\PushQueue;
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

    // The users' current device tokens; users on neither platform get nothing.
    $devices = User::where('fcm_id', '!=', '')->whereIn('id', $user)->whereIn('device_type', ['android', 'ios'])->get(['id', 'fcm_id', 'device_type']);

    foreach ($devices as $device) {
        if ($device->device_type === 'android') {
            $message = [
                'message' => [
                    'token' => $device->fcm_id,
                    'data' => $notification_data,
                ],
            ];
        } else {
            $message = [
                'message' => [
                    'token' => $device->fcm_id,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $notification_data,
                ],
            ];
        }

        PushQueue::toDevice((string) $type, (int) $device->id, (string) $device->fcm_id, $message);
    }
}

function sendNotificationToTopic($title, $body, $type, $image, $userinfo)
{
    $notification_data = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'title' => $title,
        'body' => $body,
        'type' => $type,
        'image' => $image,
    ];

    $topicMap = [
        'all' => ['allAndroid', 'allIOS'],
        'students' => ['studentsAndroid', 'studentsIOS'],
        'parents' => ['parentsAndroid', 'parentsIOS'],
        'teachers' => ['teachersAndroid', 'teachersIOS'],
    ];

    foreach ($topicMap[$userinfo] as $topic) {
        if (str_contains($topic, 'Android')) {
            $message = [
                'message' => [
                    'topic' => $topic,
                    'data' => $notification_data,
                ],
            ];
        } else {
            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $notification_data,
                ],
            ];
        }

        PushQueue::toTopic((string) $type, $topic, $message);
    }
}

function getAccessToken()
{
    try {
        // FCM_SERVICE_ACCOUNT_JSON (Secret Manager) wins over the uploaded file.
        $credentials = SecretSettings::fcmCredentials();
        if ($credentials !== null) {
            return Cache::remember('fcm-access-token:'.md5((string) json_encode($credentials)), now()->addMinutes(50), function () use ($credentials) {
                $client = new \Google\Client();
                $client->setHttpClient(new \GuzzleHttp\Client(['connect_timeout' => 5, 'timeout' => 10]));
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
