<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Saas\SupportAccess;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** The school's decision on Navigate Ed support access (SA.9): its Super Admin only; the service checks again. */
class SupportAccessDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        try {
            app(SupportAccess::class)->schoolAdmin($this->user());

            return true;
        } catch (HttpExceptionInterface) {
            return false;
        }
    }

    public function rules(): array
    {
        return [];
    }
}
