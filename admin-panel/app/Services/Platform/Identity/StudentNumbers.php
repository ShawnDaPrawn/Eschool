<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Students;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use OverflowException;

class StudentNumbers
{
    /** Existing school login names take precedence over additive identifiers. */
    public function resolveLogin(string $login): ?User
    {
        $user = User::acrossSchools()->where('email', trim($login))->first();
        if ($user !== null) {
            return $user;
        }
        $userId = DB::table('learner_identifiers')->where('student_number', trim($login))->value('user_id');

        return $userId === null ? null : User::acrossSchools()->find($userId);
    }

    public static function checkDigit(string $payload): string
    {
        $sum = 0;
        foreach (array_reverse(str_split($payload)) as $index => $digit) {
            $n = (int) $digit * ($index % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    public static function valid(string $number): bool
    {
        return preg_match('/^[0-9]{10}$/D', $number) === 1 && self::checkDigit(substr($number, 0, 9)) === $number[9];
    }

    public function assign(Students $learner): string
    {
        return DB::transaction(function () use ($learner) {
            // A human keeps one number across schools. The user lock also
            // serialises concurrent enrolments of this same person.
            User::acrossSchools()->withTrashed()->whereKey($learner->user_id)->lockForUpdate()->firstOrFail();
            $number = DB::table('learner_identifiers')->where('user_id', $learner->user_id)->value('student_number');
            if ($number === null) {
                DB::table('platform_counters')->insertOrIgnore(['name' => 'student_number', 'value' => 0]);
                $sequence = (int) DB::table('platform_counters')->where('name', 'student_number')->lockForUpdate()->value('value') + 1;
                if ($sequence > 9999999) {
                    throw new OverflowException('Student number sequence exhausted.');
                }
                DB::table('platform_counters')->where('name', 'student_number')->update(['value' => $sequence]);
                $firstDate = Students::acrossSchools()->withTrashed()->where('user_id', $learner->user_id)->min('admission_date') ?? now()->toDateString();
                $payload = date('y', strtotime((string) $firstDate)).str_pad((string) $sequence, 7, '0', STR_PAD_LEFT);
                $number = $payload.self::checkDigit($payload);
                DB::table('learner_identifiers')->insert([
                    'user_id' => $learner->user_id, 'student_number' => $number, 'first_enrolled_on' => $firstDate,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            // A projection on school learner rows; never exposed by v1's model.
            Students::acrossSchools()->withTrashed()->where('user_id', $learner->user_id)->update(['student_number' => $number]);
            $learner->setAttribute('student_number', $number);

            return (string) $number;
        });
    }
}
