<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Services\Platform\Tenancy\CurrentSchool;

/** Recover exact reference-only operation responses without persisting secrets. */
class ClassroomSecrets
{
    public function value(string $purpose, string $id): string
    {
        return hash_hmac('sha256', 'foundation-classroom/'.$purpose.'/'.app(CurrentSchool::class)->id().'/'.$id, (string) config('app.key'));
    }

    public function credential(string $purpose, string $id): string
    {
        $prefix = $purpose === 'lease' ? 'NECL1' : 'NEF1';

        return $prefix.'.'.app(CurrentSchool::class)->id().'.'.$id.'.'.$this->value($purpose, $id);
    }

    public function reference(string $credential, string $prefix): array
    {
        abort_unless(strlen($credential) < 160 && preg_match('/^'.preg_quote($prefix, '/').'\.([1-9][0-9]*)\.([a-f0-9-]{36})\.([a-f0-9]{64})$/D', $credential, $parts) === 1, 401);
        abort_unless((int) $parts[1] === app(CurrentSchool::class)->id(), 403);

        return [$parts[2], hash('sha256', $parts[3])];
    }
}
