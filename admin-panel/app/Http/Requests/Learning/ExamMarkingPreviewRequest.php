<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Services\Learning\Marking\PaperMarking;
use App\Services\Learning\PastPaperLibrary;
use Illuminate\Foundation\Http\FormRequest;

/** A teacher's "mark this answer against the memo" preview (Action Plan 3.16); nothing is stored. */
class ExamMarkingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The staff paper: a teacher of the group, in this school, with the licence current.
        app(PastPaperLibrary::class)->version($this->user(), (int) $this->route('group'), (string) $this->route('version'), true);

        return true;
    }

    public function rules(): array
    {
        return PaperMarking::rules();
    }
}
