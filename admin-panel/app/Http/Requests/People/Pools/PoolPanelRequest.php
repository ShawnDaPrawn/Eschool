<?php

declare(strict_types=1);

namespace App\Http\Requests\People\Pools;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/** The existing pool contract, with safe recovery for native panel forms. */
class PoolPanelRequest extends PoolRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->action() === 'vet' && ! $this->has('checks')) {
            $this->merge(['checks' => []]);
        }
    }

    /** Native forms recover to their own workspace, never a last background GET. */
    public function recoveryUrl(): string
    {
        return match ($this->action()) {
            'invite' => route('people-pool-panel.create'),
            'vet' => route('people-pool-panel.detail', (int) $this->route('member')),
            default => route('people-pool-panel.bookings'),
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(redirect($this->recoveryUrl())->withErrors($validator)->withInput($this->except(['initial_password', '_token'])));
    }
}
