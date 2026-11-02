<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Models\Learning\PastPaperVersion;
use App\Services\Learning\PastPaperMemos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/** Saves a structured memo's mark points from the panel (Action Plan 3.15). */
class ExamMemoRequest extends FormRequest
{
    private ?PastPaperVersion $version = null;

    public function authorize(): bool
    {
        $this->version = app(PastPaperMemos::class)->editable($this->user(), (int) $this->route('group'), (string) $this->route('version'));

        return true;
    }

    /** The form shows blank rows for new mark points; a row left blank is not a mark point. */
    protected function prepareForValidation(): void
    {
        $questions = $this->input('questions');
        if (! is_array($questions)) {
            return;
        }
        foreach ($questions as $i => $question) {
            if (is_array($question) && is_array($question['points'] ?? null)) {
                $questions[$i]['points'] = array_filter($question['points'], fn ($point) => ! is_array($point)
                    || array_filter(array_intersect_key($point, array_flip(['earns', 'marks', 'rule_value', 'rule_tolerance', 'rule_unit'])), fn ($v) => trim((string) $v) !== '') !== []);
            } elseif (is_array($question) && ! array_key_exists('points', $question)) {
                $questions[$i]['points'] = [];
            }
        }
        $this->merge(['questions' => $questions]);
    }

    public function rules(): array
    {
        return PastPaperMemos::rules();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->version === null) {
                return;
            }
            try {
                app(PastPaperMemos::class)->normalize($this->version, $validator->validated());
            } catch (ValidationException $error) {
                foreach ($error->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        }];
    }
}
