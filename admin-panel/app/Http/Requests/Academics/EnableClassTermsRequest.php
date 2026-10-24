<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TermPolicy;
use Illuminate\Foundation\Http\FormRequest;

class EnableClassTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(TermPolicy::class)->authorise($this->user(), 'edit');
        abort_unless($this->user()->can('assign-class-subject'), 403);

        return true;
    }

    public function rules(): array
    {
        return ['session_year_id' => ['required', 'integer', 'min:1'], 'class_id' => ['required', 'integer', 'min:1']];
    }
}
