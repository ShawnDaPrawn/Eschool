<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Models\Academics\CurriculumPlan;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Academics\CurriculumPlans;
use Illuminate\Foundation\Http\FormRequest;

class CurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $name = (string) $this->route()?->getName();
        $operation = str_ends_with($name, '.publish') ? 'publish' : ($this->isMethod('GET') ? 'read' : 'manage');
        $policy = app(CurriculumPolicy::class);
        $policy->authorise($this->user(), $operation);
        if ($this->route('plan') !== null) {
            $policy->authorise($this->user(), $operation, CurriculumPlan::query()->findOrFail($this->route('plan')));
        }

        return true;
    }

    public function rules(): array
    {
        $name = (string) $this->route()?->getName();
        if (str_ends_with($name, '.copy')) {
            return ['session_year_id' => ['required', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.publish')) {
            return ['revision' => ['required', 'integer', 'min:1']];
        }
        if ($this->isMethod('GET')) {
            return ['subject_id' => ['sometimes', 'integer', 'min:1'], 'grade_level_id' => ['sometimes', 'integer', 'min:1'],
                'session_year_id' => ['sometimes', 'integer', 'min:1'], 'state' => ['sometimes', 'in:draft,published'],
                'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100']];
        }
        $rules = CurriculumPlans::rules();
        if ($this->isMethod('PUT')) {
            $rules['revision'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
