<?php

declare(strict_types=1);

namespace App\Http\Requests\SignIn;

use App\Services\Platform\SignIn\ProviderLinks;
use App\Services\Platform\SignIn\ProviderSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A school's Google or Microsoft sign-in settings (Action Plan 5.9): its Super Admin only. */
class ProviderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app(ProviderLinks::class)->isSchoolAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'allowed_domains' => ['nullable', 'string', 'max:2000'],
            'allowed_tenants' => ['nullable', 'string', 'max:2000'],
            'require_hosted_domain' => ['sometimes', 'boolean'],
            'staff_access' => ['required', Rule::in(['all', 'some', 'none'])],
            'staff_roles' => ['nullable', 'array', 'max:100'],
            'staff_roles.*' => ['string', 'max:100'],
            'learners_allowed' => ['sometimes', 'boolean'],
            'learner_stages' => ['nullable', 'array'],
            'learner_stages.*' => [Rule::in(ProviderSettings::LEARNER_STAGES)],
        ];
    }

    /** @return array<string, mixed> */
    public function choices(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            'allowed_domains' => (string) $this->input('allowed_domains', ''),
            'allowed_tenants' => (string) $this->input('allowed_tenants', ''),
            'require_hosted_domain' => $this->boolean('require_hosted_domain'),
            'staff_access' => (string) $this->input('staff_access'),
            'staff_roles' => (array) $this->input('staff_roles', []),
            'learners_allowed' => $this->boolean('learners_allowed'),
            'learner_stages' => (array) $this->input('learner_stages', []),
        ];
    }
}
