<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TermPolicy;
use Illuminate\Foundation\Http\FormRequest;

class TermTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $apply = str_ends_with($this->path(), '/apply');
        $operation = $this->isMethod('GET') || ($apply && ! $this->boolean('apply')) ? 'list' : 'create';
        app(TermPolicy::class)->authorise($this->user(), $operation);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        return ['session_year_id' => ['required', 'integer', 'min:1']]
            + (str_ends_with($this->path(), '/apply') ? ['apply' => ['sometimes', 'boolean']]
                : ['name' => ['required', 'string', 'max:120']]);
    }
}
