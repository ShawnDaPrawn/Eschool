<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\ParentFeePolicy;
use App\Services\Money\FeeItemConsents;
use Illuminate\Foundation\Http\FormRequest;

class ParentFeeItemConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(ParentFeePolicy::class)->authorize($this->user());

        return true;
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? ['page' => ['sometimes', 'integer', 'between:1,1000000'], 'per_page' => ['sometimes', 'integer', 'between:1,50']] : FeeItemConsents::rules();
    }
}
