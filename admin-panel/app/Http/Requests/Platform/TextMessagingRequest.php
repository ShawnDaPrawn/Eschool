<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\UrgentMessaging;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Your own SMS and WhatsApp at the current school (Action Plan 2.7): your
 * number's verification and your choice per notice. Guardians and staff
 * only; learners are never texted. Every action is about the signed-in
 * person: no route names anyone else.
 */
class TextMessagingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || (int) $user->status !== 1) {
            return false;
        }
        $messaging = app(UrgentMessaging::class);
        $portals = array_merge($messaging->portals(UrgentMessaging::GUARDIAN), $messaging->portals(UrgentMessaging::STAFF));

        return array_intersect(app(NotificationRules::class)->portals($user), $portals) !== [];
    }

    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'update' => [
                'type' => ['required', 'string', 'max:80'],
                'sms' => ['required', 'boolean'],
                'whatsapp' => ['required', 'boolean'],
            ],
            'confirm' => ['code' => ['required', 'string', 'max:12']],
            default => [],
        };
    }
}
