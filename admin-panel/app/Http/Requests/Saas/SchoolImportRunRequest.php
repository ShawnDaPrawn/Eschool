<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformSchoolImports;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** HQ imports on a school's behalf (SA.11); the service validates the same rules again. */
class SchoolImportRunRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'imports.run');
    }

    public function rules(): array
    {
        $file = ['file' => ['nullable', 'file', 'max:1024', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,text/tab-separated-values']];

        return match ((string) $this->route()?->getName()) {
            'saas.schools.imports.preview' => PlatformSchoolImports::previewRules() + $file,
            'saas.schools.imports.store' => PlatformSchoolImports::requestRules(),
            'saas.imports.cancel' => PlatformSchoolImports::reasonRules(),
            'saas.imports.apply' => PlatformSchoolImports::applyRules(),
            default => [],
        };
    }
}
