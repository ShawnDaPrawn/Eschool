<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\Registers;
use App\Services\Academics\Attendance\RegisterSettings;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (str_ends_with((string) $this->route()->getName(), '.settings')) {
            app(AcademicAuthority::class)->manage($this->user());
        } else {
            app(AcademicAuthority::class)->staff($this->user());
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        // HTML sends counts as strings; normalize valid integer syntax before
        // the strict same-count comparison with the decoded JSON/native rows.
        if (is_string($this->input('expected_row_count')) && preg_match('/^[0-9]{1,4}$/D', $this->input('expected_row_count'))) {
            $this->merge(['expected_row_count' => (int) $this->input('expected_row_count')]);
        }
        if (is_string($this->input('scope')) && preg_match('/^(homeroom|lesson):(\d+)$/D', $this->input('scope'), $match)) {
            $this->merge(['class_section_id' => $match[1] === 'homeroom' ? (int) $match[2] : null, 'timetable_lesson_id' => $match[1] === 'lesson' ? (int) $match[2] : null]);
        }
        if ($this->filled('payload') && is_string($this->input('payload')) && strlen($this->input('payload')) <= 1048576) {
            $rows = json_decode($this->input('payload'), true, 24);
        } elseif (is_array($this->input('records'))) {
            $rows = [];
            foreach ($this->input('records') as $id => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rows[] = ['learner_id' => $id, 'state' => $row['state'] ?? null, 'expected_revision' => $row['expected_revision'] ?? null, 'source' => 'tap'];
            }
        }
        if (isset($rows) && is_array($rows)) {
            $this->merge(['received_row_count' => count($rows), 'rows' => array_values(array_filter($rows, fn ($row) => ! is_array($row) || ($row['state'] ?? null) !== 'unmarked'))]);
        }
    }

    public function rules(): array
    {
        $name = (string) $this->route()->getName();
        if ($this->isMethodSafe()) {
            return ['on_date' => ['sometimes', 'date_format:Y-m-d'], 'session_year_id' => ['sometimes', 'integer', 'min:1'], 'register' => ['sometimes', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.settings')) {
            return RegisterSettings::rules();
        }
        if (str_ends_with($name, '.open')) {
            return Registers::targetRules() + ['scope' => ['required', 'regex:/^(homeroom|lesson):\d+$/D']];
        }
        $common = ['register_id' => ['required', 'integer', 'min:1'], 'payload' => ['sometimes', 'nullable', 'string', 'max:1048576'], 'expected_row_count' => ['required', 'integer', 'between:1,500'], 'received_row_count' => ['required', 'integer', 'same:expected_row_count']];
        if (str_ends_with($name, '.review')) {
            return $common + ['rows' => ['required', 'array', 'min:1', 'max:500'], 'rows.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'], 'rows.*.state' => ['required', 'in:present,absent,late,excused,first_half,second_half,holiday'], 'reason' => ['nullable', 'string', 'max:500']];
        }

        return $common + Registers::syncRules();
    }
}
