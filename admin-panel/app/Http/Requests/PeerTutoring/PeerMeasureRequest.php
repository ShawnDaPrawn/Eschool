<?php

declare(strict_types=1);

namespace App\Http\Requests\PeerTutoring;

use App\Services\PeerTutoring\PeerMeasure;
use Illuminate\Foundation\Http\FormRequest;

/** The school measure (Action Plan 4.7): a read with no inputs. */
class PeerMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(app(PeerMeasure::class)->available($this->user()), 403);

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->query->count() > 0, 422);
    }
}
