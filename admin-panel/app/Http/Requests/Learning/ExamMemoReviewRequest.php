<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Models\Learning\PastPaperMemo;
use App\Models\Learning\PastPaperVersion;
use App\Services\Learning\PastPaperMemos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Marks a structured memo as reviewed (Action Plan 3.15). */
class ExamMemoReviewRequest extends FormRequest
{
    private ?PastPaperVersion $version = null;

    public function authorize(): bool
    {
        $this->version = app(PastPaperMemos::class)->editable($this->user(), (int) $this->route('group'), (string) $this->route('version'));

        return true;
    }

    public function rules(): array
    {
        return PastPaperMemos::reviewRules();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->version === null) {
                return;
            }
            $memo = PastPaperMemo::where('past_paper_version_id', $this->version->id)->first();
            $problems = $memo === null ? [__('Save the mark points before reviewing the memo.')] : app(PastPaperMemos::class)->problems($this->version, $memo->mark_points);
            foreach ($problems as $problem) {
                $validator->errors()->add('memo', $problem);
            }
        }];
    }
}
