<?php

declare(strict_types=1);

namespace App\Http\Requests\Wellbeing;

use App\Services\Platform\Files\SafeImages;
use App\Services\Wellbeing\WellbeingRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PublicIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The stateless boundary has checked the public school and module.
        return true;
    }

    public function validationData(): array
    {
        return $this->getInputSource()->all() + $this->allFiles();
    }

    public function rules(): array
    {
        $secret = ['secret' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']];

        return match ($this->route()->getName()) {
            'public.wellbeing.submit' => WellbeingRules::rules('report-submit'),
            'public.wellbeing.photo-upload' => $secret + SafeImages::rules() + ['operation_id' => ['required', 'uuid']],
            'public.wellbeing.photo-read' => $secret + ['photo_id' => ['required', 'uuid']],
            'public.wellbeing.reply' => $secret + WellbeingRules::rules('reporter-reply'),
            'public.wellbeing.follow-up' => $secret + ['before' => ['sometimes', 'integer', 'min:1']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        // The boundary returns a bounded private draft without a redirect/session.
        throw new ValidationException($validator);
    }
}
