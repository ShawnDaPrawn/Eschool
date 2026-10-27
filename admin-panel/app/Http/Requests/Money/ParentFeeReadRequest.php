<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\ParentFeePolicy;
use Illuminate\Foundation\Http\FormRequest;

class ParentFeeReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(ParentFeePolicy::class)->authorize($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'charges_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'receipts_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'credits_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']];
    }
}
