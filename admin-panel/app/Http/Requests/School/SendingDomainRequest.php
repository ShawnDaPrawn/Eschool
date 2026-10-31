<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use App\Services\Platform\Mail\SendingDomains;
use Illuminate\Foundation\Http\FormRequest;

/** Settings > Email from your domain (Action Plan 2.2); SendingDomains checks the same right again. */
class SendingDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(SendingDomains::class)->authorise($this->user());

        return true;
    }

    public function rules(): array
    {
        return $this->isMethod('POST') && $this->route()?->getName() === 'sending-domain.store' ? SendingDomains::rules() : [];
    }
}
