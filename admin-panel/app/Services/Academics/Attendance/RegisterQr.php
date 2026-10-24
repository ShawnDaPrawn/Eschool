<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A signed identifier, never an attendance authorization or a name/DOB payload. */
class RegisterQr
{
    public function issue(Students $learner): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['purpose' => 'academic_register', 'school' => app(CurrentSchool::class)->id(), 'learner' => (int) $learner->id, 'version' => (int) $learner->attendance_qr_version], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return 'NEAR1.'.$payload.'.'.hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public function learner(string $token): Students
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== 'NEAR1' || ! hash_equals(hash_hmac('sha256', $parts[1], (string) config('app.key')), $parts[2])) {
            $this->invalid();
        }
        try {
            $decoded = base64_decode(strtr($parts[1], '-_', '+/'), true);
            $data = json_decode($decoded === false ? '' : $decoded, true, 8, JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            $this->invalid();
        }
        abort_unless(is_array($data) && ($data['purpose'] ?? null) === 'academic_register' && ($data['school'] ?? null) === app(CurrentSchool::class)->id(), 404);
        $learner = Students::findOrFail($data['learner'] ?? 0);
        abort_unless((int) $learner->attendance_qr_version === ($data['version'] ?? null), 409, __('This register QR code has been replaced.'));

        return $learner;
    }

    public function rotate(User $actor, int $id): string
    {
        app(ReleasedMarks::class)->learner($actor, $id);

        return DB::transaction(function () use ($actor, $id) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $learner = app(ReleasedMarks::class)->learner($actor, $id);
            $before = (int) $learner->attendance_qr_version;
            SchoolTable::query('students')->where('id', $id)->increment('attendance_qr_version');
            Audit::record('academics.register_qr_rotated', $learner, ['version' => $before], ['version' => $before + 1]);

            return $this->issue($learner->fresh());
        });
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['qr_token' => __('This register QR code is invalid.')]);
    }
}
