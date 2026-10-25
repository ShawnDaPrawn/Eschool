<?php

declare(strict_types=1);

namespace App\Http\Requests\Wellbeing;

use App\Services\Wellbeing\WellbeingRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class WellbeingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware resolves fresh actor, school and record scope first.
        return true;
    }

    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.wellbeing.');
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->query->has('secret'), 422, 'Enter the private code in the form, never in a URL.');
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    public function rules(): array
    {
        $action = $this->action();
        if ($action === 'own-notices') {
            return ['page' => ['sometimes', 'integer', 'between:1,10000'], 'area' => ['sometimes', 'in:staff,reporter']];
        }
        if ($this->isMethod('GET')) {
            return ['selected' => ['sometimes', 'integer', 'min:1'], 'on_date' => [$action === 'case-professionals' ? 'required' : 'sometimes', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'between:1,10000'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'before' => ['sometimes', 'integer', 'min:1'],
                'from' => [$action === 'appointments' ? 'required' : 'sometimes', 'date_format:Y-m-d'], 'to' => [$action === 'appointments' ? 'required' : 'sometimes', 'date_format:Y-m-d', 'after_or_equal:from']];
        }
        if ($action === 'own-notice-read') {
            return [];
        }
        $secret = ['secret' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']];
        if (in_array($action, ['public.read', 'identified-read'], true)) {
            return $secret + ['before' => ['sometimes', 'integer', 'min:1']];
        }
        if (in_array($action, ['public.reply', 'identified-reply'], true)) {
            return $secret + WellbeingRules::rules('reporter-reply');
        }
        if ($action === 'identify') {
            return $secret + WellbeingRules::rules('identify');
        }

        return WellbeingRules::rules(match ($action) {
            'public.submit', 'identified-submit' => 'report-submit',
            'category-create', 'category-save' => 'category-save',
            'team-create', 'team-save', 'case-assignment-create', 'case-assignment-save' => 'assignment-save',
            'case-create' => 'case-open',
            'appointment-create', 'appointment-save' => 'appointment-save',
            'report-respond', 'case-entry', 'case-link', 'case-status' => $action,
        });
    }
}
