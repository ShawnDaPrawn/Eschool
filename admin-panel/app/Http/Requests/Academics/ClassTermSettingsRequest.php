<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TermPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ClassTermSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(TermPolicy::class)->authoriseClassSettings($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['session_year_id' => ['required', 'integer', 'min:1']];
    }
}
