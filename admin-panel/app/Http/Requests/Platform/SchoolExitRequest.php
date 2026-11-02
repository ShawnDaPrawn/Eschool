<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Offboarding\SchoolExit;
use Illuminate\Foundation\Http\FormRequest;

/** A leaving school's Super Admin confirms receipt of its export (SA.11); the service checks again. */
class SchoolExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SchoolExit::class)->allowed($this->user());
    }

    public function rules(): array
    {
        return ['confirm' => ['accepted']];
    }
}
