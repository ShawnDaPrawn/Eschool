<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\Reports\ReportBatches;
use App\Services\Academics\Reports\ReportDesigns;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $name = (string) $this->route()->getName();
        if (str_ends_with($name, '.roster')) {
            return ['session_year_id' => ['required', 'integer', 'min:1'], 'class_section_id' => ['required', 'integer', 'min:1'], 'term_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        }
        if ($this->isMethodSafe()) {
            return ['session_year_id' => [str_ends_with($name, '.batches') || str_ends_with($name, '.comments') ? 'required' : 'sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,10000']];
        }
        if (str_ends_with($name, '.templates')) {
            return ReportDesigns::rules();
        }
        if (str_ends_with($name, '.comment-banks')) {
            return ReportDesigns::bankRules();
        }
        if (str_ends_with($name, '.preview')) {
            return ReportBatches::selectionRules();
        }
        if (str_ends_with($name, '.prepare')) {
            return ReportBatches::selectionRules() + ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];
        }
        if (str_ends_with($name, '.comment')) {
            return ReportBatches::commentRules();
        }
        if (str_ends_with($name, '.read')) {
            return [];
        }

        return ReportBatches::actionRules();
    }
}
