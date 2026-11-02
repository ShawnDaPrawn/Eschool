<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Models\Learning\PastPaperVersion;
use App\Services\Learning\PastPaperMemos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
        // A whole paper can have 2,820 controls. Decode one bounded field before
        // the normal per-question validation; never fall back to native rows if
        // a supplied payload is malformed or oversized.
        if ($this->exists('memo_payload')) {
            $payload = $this->input('memo_payload');
            $decoded = is_string($payload) && strlen($payload) <= 1048576
                ? json_decode($payload, true, 8) : null;
            if (is_array($decoded)) {
                // Match TrimStrings / ConvertEmptyStringsToNull for native forms.
                array_walk_recursive($decoded, function (&$value) {
                    if (is_string($value)) {
                        $value = Str::trim($value);
                        $value = $value === '' ? null : $value;
                    }
                });
            }
            $this->merge(['questions' => $decoded]);
        }
        $questions = $this->input('questions');
        if (! is_array($questions)) {
            return;
        }
        foreach ($questions as $i => $question) {
            if (is_array($question) && is_array($question['points'] ?? null)) {
                $questions[$i]['points'] = array_filter($question['points'], fn ($point) => ! is_array($point)
                    || array_filter(array_intersect_key($point, array_flip(['earns', 'marks', 'rule_value', 'rule_tolerance', 'rule_unit'])), fn ($v) => is_scalar($v) ? trim((string) $v) !== '' : $v !== null) !== []);
            } elseif (is_array($question) && ! array_key_exists('points', $question)) {
                $questions[$i]['points'] = [];
            }
        }
        $this->merge(['questions' => $questions]);
    }

    public function rules(): array
    {
        return ['memo_payload' => ['sometimes', 'string', 'json', 'max:1048576']] + PastPaperMemos::rules();
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
