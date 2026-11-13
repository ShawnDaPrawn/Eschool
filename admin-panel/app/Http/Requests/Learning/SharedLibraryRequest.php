<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Services\Learning\Library\SharedLibrary;
use App\Services\Learning\Library\SharedLibraryBrowse;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The shared library pages and API (Action Plan 5.4). Any signed-in school
 * account reaches the services, which decide who may do what (teachers,
 * leaders, content leads) and refuse everyone else; they validate the same
 * rules again.
 */
class SharedLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'index', 'items' => SharedLibraryBrowse::filterRules(),
            'item' => ['group' => ['nullable', 'integer', 'min:1']],
            'rate' => SharedLibraryBrowse::rateRules(),
            'copy' => SharedLibraryBrowse::copyRules(),
            'contribute' => SharedLibrary::contributeRules(),
            'decline' => SharedLibrary::declineRules(),
            'settings' => SharedLibrary::settingsRules(),
            default => [],
        };
    }

    /** An unticked checkbox sends nothing: the panel's confirmations and switches read it as "no". */
    protected function prepareForValidation(): void
    {
        if ($this->route()?->getActionMethod() === 'settings' && ! $this->expectsJson()) {
            $this->merge(['sharing_enabled' => $this->boolean('sharing_enabled'), 'leader_approval' => $this->boolean('leader_approval')]);
        }
    }
}
