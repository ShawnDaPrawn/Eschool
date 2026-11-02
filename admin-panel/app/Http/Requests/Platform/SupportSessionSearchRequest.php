<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The people search in a support session (SA.9): a read sent as a POST so the
 * words never travel in a URL, a log line or a browser's history. Only inside
 * a session support.session has opened; it changes nothing.
 */
class SupportSessionSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CurrentSchool::class)->isSupportSession();
    }

    public function rules(): array
    {
        return ['q' => ['required', 'string', 'min:3', 'max:191']];
    }
}
