<?php

declare(strict_types=1);

namespace App\Http\Requests\Transfers;

use App\Http\Controllers\Panel\Transfers\TransferPanelController;
use App\Services\Transfers\TransferPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransferPanelRequest extends TransferRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if ($this->routeIs('school-transfers.prepare')) {
            $this->merge(['report_ids' => $this->input('report_ids', []), 'document_ids' => $this->input('document_ids', []), 'to_school_id' => $this->input('to_school_id')]);
        }
    }

    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('school-transfers.'));
    }

    public function authorize(): bool
    {
        return app(TransferPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        $common = ['lane' => ['sometimes', 'in:outgoing,incoming,receipts'], 'record' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1'], 'choice_page' => ['sometimes', 'integer', 'min:1'],
            'choice_kind' => ['sometimes', 'in:learners,destinations,guardians,reports,documents,years,rooms,categories'], 'learner_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'guardian_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        if ($this->isMethodSafe()) {
            return $common;
        }

        return self::writeRules($this->operation()) + $common + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(TransferPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
