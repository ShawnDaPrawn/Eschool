<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\SubjectStandardPolicy;
use Illuminate\Foundation\Http\FormRequest;

class SubjectStandardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operation = $this->isMethod('GET') ? ($this->route('subject') !== null ? 'list' : null)
            : ($this->isMethod('PUT') ? 'edit' : 'create');
        app(SubjectStandardPolicy::class)->authorise($this->user(), $operation);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['framework' => ['sometimes', 'nullable', 'in:CAPS,IEB'], 'grade' => ['sometimes', 'nullable', 'integer', 'between:0,12'],
                'q' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100']];
        }
        if ($this->isMethod('PUT')) {
            return ['standard_id' => ['present', 'nullable', 'integer', 'min:1']];
        }

        return ['standard_id' => ['required', 'integer', 'min:1'], 'medium_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:Theory,Practical'], 'bg_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']];
    }
}
