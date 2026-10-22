<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use App\Services\Panel\GlobalSearch;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /search?q=. Any panel user may search; what each person finds depends on
 * their permissions (App\Services\Panel\GlobalSearch).
 */
class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
        ];
    }

    public function term(): string
    {
        $q = $this->input('q');

        return is_string($q) ? mb_substr(trim($q), 0, GlobalSearch::MAX_LENGTH) : '';
    }
}
