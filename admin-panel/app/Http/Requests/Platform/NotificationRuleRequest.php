<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Notifications\NotificationRules;
use Illuminate\Validation\Rule;

class NotificationRuleRequest extends ModuleRequest
{
    public function rules(): array
    {
        $rules = ['portal' => ['sometimes', Rule::in(['learner', 'parent', 'teacher', 'admin'])]];
        if ($this->isMethod('GET')) {
            return $rules;
        }

        return [
            'portal' => ['required', Rule::in(['learner', 'parent', 'teacher', 'admin'])],
            'type' => ['required', 'string', 'max:80'],
            'channel' => ['required', Rule::in(NotificationRules::CHANNELS)],
            'mode' => ['required', Rule::in(NotificationRules::MODES)],
        ];
    }
}
