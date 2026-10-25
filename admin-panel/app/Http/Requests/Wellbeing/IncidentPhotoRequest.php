<?php

declare(strict_types=1);

namespace App\Http\Requests\Wellbeing;

use App\Services\Platform\Files\SafeImages;
use Illuminate\Foundation\Http\FormRequest;

class IncidentPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->query->count() > 0, 422, 'Use the private form, never URL parameters.');
    }

    public function validationData(): array
    {
        return $this->getInputSource()->all() + $this->allFiles();
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }
        $secret = ['secret' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']];

        return $secret + (str_ends_with((string) $this->route()->getName(), 'photo-read')
            ? ['photo_id' => ['required', 'uuid']]
            : SafeImages::rules() + ['operation_id' => ['required', 'uuid']]);
    }
}
