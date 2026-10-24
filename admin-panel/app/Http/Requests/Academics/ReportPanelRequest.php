<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Models\Academics\ReportCard;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Reports\ReportBatches;
use App\Services\Academics\Reports\ReportDesigns;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

class ReportPanelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('term_id') === '' || $this->input('term_id') === '0') {
            $this->merge(['term_id' => null]);
        }
        if ($this->input('subject_id') === '' || $this->input('subject_id') === '0') {
            $this->merge(['subject_id' => null]);
        }
        if (is_array($this->input('design.sections'))) {
            $this->merge(['design' => array_replace($this->input('design'), ['sections' => array_values(array_filter($this->input('design.sections'), fn ($value) => $value !== '' && $value !== null))])]);
        }
    }

    /** Fresh scope checks run before any cached panel mutation is replayed. */
    public function authorize(): bool
    {
        app(AcademicAuthority::class)->staff($this->user());
        $name = (string) $this->route()->getName();
        $policy = app(ReportPolicy::class);
        if (str_ends_with($name, '.templates') || str_ends_with($name, '.banks')) {
            app(AcademicAuthority::class)->manage($this->user());
        } elseif (str_ends_with($name, '.prepare') || str_ends_with($name, '.preview')) {
            app(ReportBatches::class)->selection($this->user(), $this->all());
        } elseif ($this->route('card') !== null) {
            $card = ReportCard::with('batch')->findOrFail((int) $this->route('card'));
            if (str_ends_with($name, '.pdf')) {
                abort_unless($this->hasValidSignature() && (int) $this->query('school') === app(CurrentSchool::class)->id() && (int) $this->query('reader') === (int) $this->user()->id, 403);
                $policy->batch($this->user(), (int) $card->report_batch_id);
            } else {
                $policy->comment($this->user(), $card, $this->input('subject_id') === null ? null : (int) $this->input('subject_id'));
            }
        } elseif ($this->route('batch') !== null) {
            $batch = $policy->batch($this->user(), (int) $this->route('batch'));
            if (in_array($this->route('action'), ['grade-sign', 'principal-sign'], true)) {
                $policy->sign($this->user(), $batch, $this->route('action') === 'grade-sign' ? 'grade_head' : 'principal');
            }
        }

        return true;
    }

    public function rules(): array
    {
        $context = ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'class_section_id' => ['sometimes', 'integer', 'min:1'], 'term_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'batch_id' => ['sometimes', 'integer', 'min:1'], 'template_id' => ['sometimes', 'integer', 'min:1'], 'bank_id' => ['sometimes', 'integer', 'min:1'], 'comment_page' => ['sometimes', 'integer', 'between:1,10000'], 'batch_page' => ['sometimes', 'integer', 'between:1,10000']];
        if ($this->isMethodSafe()) {
            return $context;
        }
        $name = (string) $this->route()->getName();
        $operation = ['operation_id' => ['required', 'uuid']];
        if (str_ends_with($name, '.templates')) {
            return ReportDesigns::rules() + $operation + $context;
        }
        if (str_ends_with($name, '.banks')) {
            return ReportDesigns::bankRules() + $operation + $context;
        }
        if (str_ends_with($name, '.preview')) {
            return ReportBatches::selectionRules() + $context;
        }
        if (str_ends_with($name, '.prepare')) {
            return ReportBatches::selectionRules() + ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']] + $operation + $context;
        }
        if (str_ends_with($name, '.comment')) {
            return ReportBatches::commentRules() + $operation + $context;
        }
        if (str_ends_with($name, '.review-comment')) {
            return ReportBatches::commentRules() + $context;
        }

        return ReportBatches::actionRules() + $operation + $context;
    }
}
