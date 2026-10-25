<?php

declare(strict_types=1);

namespace App\Http\Requests\Transfers;

use App\Services\Transfers\TransferEvidence;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Submit transfer changes in the request body.');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1'],
                'learner_id' => ['sometimes', 'integer', 'min:1'], 'guardian_id' => ['sometimes', 'integer', 'min:1'], 'selected' => ['sometimes', 'string', 'max:64']];
        }

        return self::writeRules($this->route()->getActionMethod());
    }

    public static function writeRules(string $op): array
    {
        return ['operation_id' => ['required', 'uuid']] + match ($op) {
            'prepare' => TransferEvidence::rules(),
            'consent' => ['revision' => ['required', 'integer', 'min:1'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'], 'version' => ['required', 'string', 'max:64'], 'confirmation' => ['required', 'accepted'], 'keep_photo_tags' => ['sometimes', 'boolean'], 'gallery_fingerprint' => ['required_if:keep_photo_tags,true', 'nullable', 'string', 'regex:/^[a-f0-9]{64}$/D']],
            'accept' => ['revision' => ['required', 'integer', 'min:1'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'], 'session_year_id' => ['required', 'integer', 'min:1'], 'class_section_id' => ['required', 'integer', 'min:1'], 'category_id' => ['required', 'integer', 'min:1'], 'confirmation' => ['required', 'accepted']],
            default => ['revision' => ['required', 'integer', 'min:1']],
        };
    }
}
