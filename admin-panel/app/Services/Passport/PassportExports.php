<?php

declare(strict_types=1);

namespace App\Services\Passport;

use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Downloading the passport (Action Plan 4.10). Only the learner themself may,
 * and only from their 18th birthday by the school's calendar (its timezone).
 * Guardians and staff never export (open question in the spec; default no).
 * Each export is audited, with the format only: never the LURITS number or
 * the date of birth, which never leaves this class.
 */
class PassportExports
{
    public const FORMATS = ['json', 'pdf'];

    public const FORMAT_VERSION = 'navigateed-learner-passport-v1';

    /** For the learner's own view: whether they may download it today. */
    public function availability(User $learner): array
    {
        return ['available' => $this->adult($learner), 'formats' => self::FORMATS];
    }

    /** @return array{filename: string, mime: string, bytes: string} */
    public function export(User $actor, string $format): array
    {
        abort_unless(in_array($format, self::FORMATS, true), 422, 'Choose JSON or PDF.');
        $authority = app(PassportAuthority::class);
        $access = $authority->resolve($actor, $authority->ownLearnerId($actor));
        abort_unless($access['viewer'] === 'learner', 403);
        abort_unless($this->adult($access['actor']), 403, 'You can download your passport from your 18th birthday.');
        $passport = app(LearnerPassport::class)->build($access);
        unset($passport['export'], $passport['viewer']);
        $generated = now()->utc()->toIso8601String();
        $bytes = $format === 'json'
            ? json_encode(['format' => self::FORMAT_VERSION, 'generated_at' => $generated, 'passport' => $passport], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : Pdf::loadView('passport.pdf', ['passport' => $passport, 'generated' => $generated])->setPaper('a4')
                ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans'])->output();
        Audit::record('passport.exported', $access['learner'], [], ['format' => $format, 'schools' => count($passport['schools']), 'transfers' => count($passport['transfers'])]);

        return ['filename' => 'learner-passport.'.$format, 'mime' => $format === 'json' ? 'application/json' : 'application/pdf', 'bytes' => $bytes];
    }

    /**
     * Whether the learner has turned 18 today, in the school's timezone. An
     * unknown or unreadable date of birth counts as under 18. A 29 February
     * birthday turns 18 on 1 March in a year without one.
     */
    private function adult(User $learner): bool
    {
        $dob = User::query()->whereKey($learner->id)->value('dob');
        if ($dob === null || trim((string) $dob) === '') {
            return false;
        }
        try {
            $born = CarbonImmutable::parse((string) $dob)->startOfDay();
        } catch (Throwable) {
            return false;
        }

        return $born->addYearsWithOverflow(18)->toDateString() <= app(AcademicAuthority::class)->today();
    }
}
