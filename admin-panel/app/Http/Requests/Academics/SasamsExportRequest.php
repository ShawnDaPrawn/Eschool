<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\AcademicAuthority;
use Illuminate\Foundation\Http\FormRequest;

class SasamsExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(AcademicAuthority::class)->manage($this->user());

        return true;
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : ['layout' => ['required', 'file', 'max:1024'], 'template' => ['nullable', 'file', 'max:2048']];
    }
}
