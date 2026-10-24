<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Settings;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use Illuminate\Support\Facades\Validator;

class RegisterSettings
{
    public static function rules(): array
    {
        return ['absence_cutoff' => ['required', 'date_format:H:i'], 'teaching_days' => ['required', 'array', 'min:1', 'max:7'], 'teaching_days.*' => ['integer', 'between:1,7', 'distinct']];
    }

    public function get(): array
    {
        $days = json_decode((string) schoolSetting('register_teaching_days'), true);

        return ['absence_cutoff' => schoolSetting('register_absence_cutoff') ?? '10:00', 'teaching_days' => is_array($days) ? array_map('intval', $days) : [1, 2, 3, 4, 5]];
    }

    public function save(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::rules())->validate();
        Settings::updateOrCreate(['type' => 'register_absence_cutoff'], ['message' => $data['absence_cutoff']]);
        Settings::updateOrCreate(['type' => 'register_teaching_days'], ['message' => json_encode($data['teaching_days'], JSON_THROW_ON_ERROR)]);

        return $this->get();
    }
}
