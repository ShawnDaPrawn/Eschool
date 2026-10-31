<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\Platform\Privacy\DataRequests;
use Illuminate\Foundation\Http\FormRequest;

/** The school's answer to a data request Navigate Ed passed to it (SA.11); the service checks again. */
class AnswerDataRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return DataRequests::isSchoolOfficer($this->user());
    }

    public function rules(): array
    {
        return DataRequests::answerRules();
    }
}
