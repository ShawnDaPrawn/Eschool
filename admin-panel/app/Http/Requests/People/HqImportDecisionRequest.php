<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\SchoolImports;
use App\Services\Platform\Saas\PlatformSchoolImports;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A school leader's decision on an import Navigate Ed prepared (SA.11): staff
 * who may import at least one kind; the service checks they are the approver.
 */
class HqImportDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app(SchoolImports::class)->allowed($this->user()) !== [];
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : PlatformSchoolImports::reasonRules();
    }
}
