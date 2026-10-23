<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use App\Services\School\SchoolInformation;
use Illuminate\Foundation\Http\FormRequest;

class SchoolInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(SchoolInformation::class)->authorise($this->user(), edit: true);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET') || $this->isMethod('DELETE')) {
            return [];
        }

        return str_ends_with($this->route()->uri(), '/documents') ? SchoolInformation::documentRules() : SchoolInformation::rules();
    }
}
