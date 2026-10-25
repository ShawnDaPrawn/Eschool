<?php

declare(strict_types=1);

namespace App\Http\Requests\Gallery;

use App\Http\Controllers\Panel\Gallery\GalleryPanelController;
use App\Services\Gallery\GalleryPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GalleryPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(GalleryPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['group_id' => ['sometimes', 'integer', 'min:1'], 'before' => ['sometimes', 'integer', 'min:1'], 'thumbnail' => ['sometimes', 'boolean'], 'expected_account_id' => ['sometimes', 'integer', 'min:1'], 'expected_school_id' => ['sometimes', 'integer', 'min:1']];
        }
        $operation = (string) $this->route('operation');
        $rules = GalleryRequest::writeRules($operation);
        if ($operation === 'upload') {
            $rules['scope'] = ['required', 'in:group'];
        }

        return $rules + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(GalleryPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
