<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\Attendance\Registers;
use App\Services\Academics\Attendance\RegisterSettings;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $name = (string) $this->route()->getName();
        if (str_ends_with($name, '.open')) {
            return Registers::targetRules();
        }
        if (str_ends_with($name, '.sync')) {
            return Registers::syncRules();
        }
        if (str_ends_with($name, '.settings') && ! $this->isMethodSafe()) {
            return RegisterSettings::rules();
        }

        return ['on_date' => ['sometimes', 'date_format:Y-m-d'], 'session_year_id' => ['sometimes', 'integer', 'min:1']];
    }
}
