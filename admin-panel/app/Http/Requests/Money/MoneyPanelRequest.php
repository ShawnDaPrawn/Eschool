<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class MoneyPanelRequest extends FormRequest
{
    abstract protected function permission(): string;

    abstract protected function returnRoute(): string;

    public function authorize(): bool
    {
        $actor = app(MoneyPolicy::class)->authorize($this->user(), $this->permission());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id
                && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_unless($this->query->count() === 0, 422);
        }

        return true;
    }

    protected function bindingRules(): array
    {
        return ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function readRules(): array
    {
        return ['year_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'], 'exceptions_only' => ['sometimes', 'boolean'], 'invoices_page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Family billing');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
