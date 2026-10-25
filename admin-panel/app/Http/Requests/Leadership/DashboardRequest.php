<?php

declare(strict_types=1);

namespace App\Http\Requests\Leadership;

use App\Services\Leadership\LeadershipAuthority;
use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(LeadershipAuthority::class)->principal($this->user(), (int) schoolSetting('session_year'));

        return true;
    }

    public function rules(): array
    {
        return ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'weeks' => ['sometimes', 'integer', 'min:1', 'max:12'], 'through' => ['sometimes', 'nullable', 'date_format:Y-m-d']];
    }
}
