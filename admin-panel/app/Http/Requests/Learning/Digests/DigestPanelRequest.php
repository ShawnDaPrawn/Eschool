<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning\Digests;

use App\Http\Controllers\Panel\Learning\Digests\DigestPanelController;
use App\Services\Learning\Digests\DigestPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DigestPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DigestPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? [] : DigestRequest::writeRules((string) $this->route('operation'));
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(app(DigestPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
