<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TeachingGroupPolicy;
use App\Services\Academics\TeachingGroups;
use Illuminate\Foundation\Http\FormRequest;

class TeachingGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operation = $this->isMethodSafe() && ! str_contains((string) $this->route()?->getName(), 'allocations') ? 'read' : 'manage';
        app(TeachingGroupPolicy::class)->authorise($this->user(), $operation, $this->route('group') === null ? null : (int) $this->route('group'));

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['subject_id' => ['sometimes', 'integer', 'min:1'], 'grade_level_id' => ['sometimes', 'integer', 'min:1'],
                'session_year_id' => ['sometimes', 'integer', 'min:1'], 'on' => ['sometimes', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100']];
        }
        $rules = $this->route('kind') === null ? TeachingGroups::groupRules() : TeachingGroups::datedRules($this->route('kind'));
        if ($this->isMethod('PUT')) {
            $rules['revision'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
