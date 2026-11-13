<?php

declare(strict_types=1);

namespace App\Http\Requests\SchoolBusiness;

use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\BusinessAuthority;
use App\Services\SchoolBusiness\ReenrolmentContracts;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReenrolmentPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless($this->user() !== null, 401);
        app(BusinessAuthority::class)->staff($this->user(), ReenrolmentContracts::MODULE, ReenrolmentContracts::PERMISSION, ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((string) $this->input('expected_account_id') === (string) $this->user()->id
                && (string) $this->input('expected_school_id') === (string) app(CurrentSchool::class)->id(), 403);
            abort_if(app(SessionYearContext::class)->isReadOnly(), 423, __('Return to the current school year before making changes.'));
        }

        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['message' => __('Review the highlighted fields.'), 'errors' => $validator->errors()], 422));
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'between:1,100000']];
        }
        $action = (string) $this->route('operation');

        return ReenrolmentContracts::rules($action) + [
            'expected_account_id' => ['required', 'integer', 'min:1'],
            'expected_school_id' => ['required', 'integer', 'min:1'],
        ] + match ($action) {
            'publish' => ['confirm_publication' => ['required', 'accepted']],
            'withdraw' => ['confirm_withdrawal' => ['required', 'accepted']],
            'remind' => ['confirm_reminders' => ['required', 'accepted']],
            default => [],
        };
    }
}
