<?php

declare(strict_types=1);

namespace App\Http\Requests\Leadership;

use App\Services\Leadership\MasteryImpact;
use Illuminate\Foundation\Http\FormRequest;

class MasteryImpactRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(MasteryImpact::class)->scope($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['term_id' => ['sometimes', 'integer', 'min:1'], 'policy_version' => ['sometimes', 'string', 'max:32', 'regex:/^[a-z0-9][a-z0-9.-]*$/']];
    }
}
