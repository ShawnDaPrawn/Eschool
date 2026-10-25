<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Services\Growth\TopicGraph;
use Illuminate\Foundation\Http\FormRequest;

class MasteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Scope middleware runs before idempotent replay.
    }

    public function rules(): array
    {
        $name = (string) $this->route()->getName();
        if (str_ends_with($name, '.configuration')) {
            return ['session_year_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.save-graph')) {
            return TopicGraph::rules();
        }
        if (str_ends_with($name, '.family')) {
            return ['subject_id' => ['required', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.graph')) {
            return ['group_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.roster')) {
            return ['page' => ['sometimes', 'integer', 'min:1']];
        }

        return [];
    }
}
