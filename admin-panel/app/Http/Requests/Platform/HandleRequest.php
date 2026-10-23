<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class HandleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : ['handle' => ['required', 'string', 'min:3', 'max:20']];
    }
}
