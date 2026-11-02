<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Stages\SchoolStages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Settings, School stages (Action Plan 4.1). SchoolStages checks both again. */
class SchoolStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SchoolStages::mayManage($this->user());
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : [
            'stages' => ['required', 'array', 'min:1', 'max:'.count(SchoolStages::DEFAULTS)],
            'stages.*' => ['required', 'string', Rule::in(array_keys(SchoolStages::STAGES))],
        ];
    }
}
