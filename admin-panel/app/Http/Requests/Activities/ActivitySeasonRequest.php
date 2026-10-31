<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Services\Activities\CommunityAuthority;
use Illuminate\Foundation\Http\FormRequest;

class ActivitySeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(CommunityAuthority::class)->manage($this->user());

        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'string', 'max:100'],
            'to' => ['required', 'string', 'max:64'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
