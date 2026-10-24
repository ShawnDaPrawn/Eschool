<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TermPolicy;
use App\Services\Academics\TermCalendar;
use Illuminate\Foundation\Http\FormRequest;

class TermRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operation = match ($this->method()) {
            'POST' => 'create', 'PUT', 'PATCH' => 'edit', 'DELETE' => 'delete', default => null,
        };
        app(TermPolicy::class)->authorise($this->user(), $operation);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('DELETE')) {
            return [];
        }
        $year = ['session_year_id' => ['required', 'integer', 'min:1']];

        return $this->isMethod('GET')
            ? $year + ['grade' => ['sometimes', 'integer', 'min:0', 'max:12']]
            : $year + TermCalendar::rules();
    }
}
