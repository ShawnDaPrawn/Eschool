<?php

declare(strict_types=1);

namespace App\Http\Requests\Reading;

use App\Services\Reading\ReadingAccess;
use App\Services\Reading\ReadingSchool;
use Illuminate\Foundation\Http\FormRequest;

/** The school's reading library page (Action Plan 4.4): school leaders only. ReadingSchool checks again. */
class ReadingPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(ReadingAccess::class)->mayLead($this->user());
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'reading-library.hidden' => ReadingSchool::hideRules(),
            'reading-library.settings' => ReadingSchool::settingsRules(),
            default => [],
        };
    }
}
