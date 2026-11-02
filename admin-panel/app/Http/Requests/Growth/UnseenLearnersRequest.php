<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Services\Growth\PositiveContacts;
use App\Services\Growth\RiskAuthority;
use App\Services\Growth\UnseenLearners;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "No child unseen" (Action Plan 4.5). Reading needs a current grade head,
 * a dated principal or an open named-adult request; naming an adult needs a
 * grade head or principal; the number of school days is the principal's.
 * Writes are bound to the account and school that showed the page and carry
 * nothing in the query string. The services check everything again.
 */
class UnseenLearnersRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private changes must be sent in the request body.');
    }

    public function authorize(): bool
    {
        $risk = app(RiskAuthority::class);
        $risk->enabled(! $this->isMethodSafe());
        $operation = $this->operation();
        if ($operation === 'configure') {
            $risk->principal($this->user(), true);
        } else {
            $access = app(UnseenLearners::class)->access($this->user());
            if ($operation === 'assign') {
                abort_unless($access['principal'] || $access['grade_ids'] !== [], 403);
            }
        }
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id
                && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        }

        return true;
    }

    public function operation(): string
    {
        return match ($this->route()?->getName()) {
            'risk-interventions.unseen.assign' => 'assign',
            'risk-interventions.unseen.configure' => 'configure',
            default => 'index',
        };
    }

    public function rules(): array
    {
        $identity = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];

        return match ($this->operation()) {
            'assign' => $identity + ['adult_user_id' => ['required', 'integer', 'min:1'], 'grade' => ['nullable', 'integer', 'min:1']],
            'configure' => $identity + ['school_days' => ['required', 'integer', 'between:'.PositiveContacts::MIN_SCHOOL_DAYS.','.PositiveContacts::MAX_SCHOOL_DAYS]],
            default => ['grade' => ['sometimes', 'nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1']],
        };
    }
}
