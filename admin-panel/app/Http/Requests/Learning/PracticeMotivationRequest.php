<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Services\Learning\Motivation\MotivationAuthority;
use App\Services\Learning\Motivation\MotivationSettings;
use Illuminate\Foundation\Http\FormRequest;

/** /practice-motivation (Action Plan 3.13). MotivationSettings checks the right again. */
class PracticeMotivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(MotivationAuthority::class)->mayManage($this->user());
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : MotivationSettings::rules();
    }
}
