<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Services\Growth\RiskAuthority;
use App\Services\Growth\RiskQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Caseload renewals (Action Plan 1.20): only someone who administers risk
 * caseloads (a dated principal or grade head) may see or renew them. Like the
 * other private risk writes, a renewal is bound to the account and school
 * that showed the page and carries nothing in the query string.
 */
class CaseloadRenewalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private changes must be sent in the request body.');
    }

    public function authorize(): bool
    {
        app(RiskAuthority::class)->enabled(! $this->isMethodSafe());
        $config = app(RiskQueries::class)->configuration($this->user());
        abort_unless($config['can_manage'] || $config['grade_ids'] !== [], 403);
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id
                && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['year' => ['sometimes', 'integer', 'min:1']];
        }

        return [
            'year' => ['required', 'integer', 'min:1'],
            'fingerprint' => ['required', 'string', 'size:64'],
            'expected_account_id' => ['required', 'integer', 'min:1'],
            'expected_school_id' => ['required', 'integer', 'min:1'],
            'cases' => ['sometimes', 'array', 'max:5000'],
            'cases.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
