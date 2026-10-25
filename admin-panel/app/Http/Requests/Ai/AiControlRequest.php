<?php

declare(strict_types=1);

namespace App\Http\Requests\Ai;

use App\Services\Ai\Operations\AiSchoolControls;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

class AiControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(AiSchoolControls::class)->actor($this->user());
        if (! $this->isMethodSafe()) {
            abort_unless($this->integer('expected_school_id') === app(CurrentSchool::class)->id() && $this->integer('expected_account_id') === (int) $this->user()->id, 403);
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
