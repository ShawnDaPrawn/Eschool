<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use App\Models\Subject;
use App\Services\Learning\Marking\Profiles\GenericProfile;
use App\Services\Learning\Marking\Profiles\MathematicsProfile;
use App\Services\Learning\Marking\Profiles\PhysicalSciencesProfile;
use Illuminate\Support\Str;

/**
 * Chooses the marking profile for a school subject (Action Plan 3.16) from its
 * name or the name of the published subject standard it follows, in English or
 * Afrikaans. Mathematical Literacy is not Mathematics; anything not recognised
 * uses the generic profile.
 */
class SubjectProfiles
{
    public function forSubject(int $subjectId): SubjectProfile
    {
        $subject = Subject::withTrashed()->with('standard')->find($subjectId);
        foreach (array_filter([$subject?->standard?->name, $subject?->name]) as $name) {
            $profile = self::match((string) $name);
            if (! $profile instanceof GenericProfile) {
                return $profile;
            }
        }

        return new GenericProfile;
    }

    public static function match(string $name): SubjectProfile
    {
        $name = trim((string) preg_replace('/[^a-z]+/', ' ', Str::lower(Str::ascii($name))));
        if (preg_match('/\b(literacy|geletterdheid)\b/', $name)) {
            return new GenericProfile;
        }
        if (preg_match('/^(technical |tegniese )?(mathematics|maths|math|wiskunde)( (home language|first additional language))?$/', $name)) {
            return new MathematicsProfile;
        }
        if (preg_match('/^(technical |tegniese )?(physical sciences?|fisiese wetenskappe?|physics|fisika)$/', $name)) {
            return new PhysicalSciencesProfile;
        }

        return new GenericProfile;
    }

    public static function key(string $key): SubjectProfile
    {
        return match ($key) {
            'mathematics' => new MathematicsProfile,
            'physical_sciences' => new PhysicalSciencesProfile,
            default => new GenericProfile,
        };
    }
}
