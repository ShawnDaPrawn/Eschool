<?php

declare(strict_types=1);

namespace App\Http\Requests\People\Pools;

use App\Services\People\Pools\PoolAccess;
use App\Services\People\Pools\PoolBookings;
use App\Services\People\Pools\PoolMembers;
use Illuminate\Foundation\Http\FormRequest;

/** Staff pool requests: authority first, then the action's rules (the services check both again). */
class PoolRequest extends FormRequest
{
    public function action(): string
    {
        $name = (string) $this->route()->getName();

        return substr($name, strrpos($name, '.') + 1);
    }

    public function authorize(): bool
    {
        $action = $this->action();
        $permission = match ($action) {
            'vet' => PoolAccess::VET,
            'detail' => [PoolAccess::MANAGE, PoolAccess::VET],
            default => PoolAccess::MANAGE,
        };
        app(PoolAccess::class)->manager($this->user(), in_array($action, ['invite', 'vet', 'book', 'cancel'], true), $permission);

        return true;
    }

    public function rules(): array
    {
        $action = $this->action();

        return in_array($action, ['book', 'cancel', 'bookings'], true) ? PoolBookings::rules($action) : PoolMembers::rules($action);
    }
}
