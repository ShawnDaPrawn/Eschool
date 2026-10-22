<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Services\Platform\Secrets\SecretSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Posts one message to FCM HTTP v1 and says what happened. The project id
 * and the service account come from SecretSettings: the environment
 * (Secret Manager) first, else the FCM settings page (getAccessToken() in
 * notification_helper.php caches the OAuth token).
 *
 * Network errors, 429 and 5xx answers throw, so the SendPush job retries;
 * other refusals (an expired device token, a bad message) are returned as
 * rejected and not retried.
 */
class FcmClient
{
    public function projectId(): ?string
    {
        // The settings row, else the project in FCM_SERVICE_ACCOUNT_JSON
        // (Secret Manager), as the security stream set it up.
        $projectId = trim((string) SecretSettings::fcmProjectId());

        return $projectId === '' ? null : $projectId;
    }

    public function accessToken(): ?string
    {
        $token = getAccessToken();

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * @param  array{message: array<string, mixed>}  $message
     * @return array{status: 'sent'|'rejected'|'not_configured', id?: string|null, error?: string}
     *
     * @throws RuntimeException|ConnectionException when worth retrying
     */
    public function send(array $message): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            // The line the helpers always wrote for a push without credentials.
            Log::error('Cannot send FCM notification: Access token is null');

            return ['status' => 'not_configured', 'error' => 'Push notifications are not configured (no FCM service account token)'];
        }
        $projectId = $this->projectId();
        if ($projectId === null) {
            return ['status' => 'not_configured', 'error' => 'Push notifications are not configured (no FCM project id)'];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($projectId).'/messages:send', $message);

        if ($response->successful()) {
            return ['status' => 'sent', 'id' => is_string($response->json('name')) ? $response->json('name') : null];
        }

        $error = 'HTTP '.$response->status();
        $detail = $response->json('error.details.0.errorCode') ?? $response->json('error.status');
        if (is_string($detail) && $detail !== '') {
            $error .= ' '.$detail;
        }

        if ($response->status() === 429 || $response->serverError()) {
            throw new RuntimeException('FCM answered '.$error);
        }

        return ['status' => 'rejected', 'error' => $error];
    }
}
