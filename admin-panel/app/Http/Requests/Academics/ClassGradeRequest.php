<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\ClassGradePolicy;
use App\Services\Academics\AcademicReferences;
use Illuminate\Foundation\Http\FormRequest;

class ClassGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(ClassGradePolicy::class)->authorise($this->user(), edit: ! $this->isMethod('GET'));

        return true;
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : AcademicReferences::mappingRules();
    }
}
