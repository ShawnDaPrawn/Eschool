<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Library\LibrarySchoolSetting;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Each school's switch for reading the platform library (Action Plan 3.3).
 * Off by default: until HQ switches it on for a school, that school's daily
 * practice uses only its own approved questions. The switch is a row of the
 * school's own (library_school_settings); switching one school never changes
 * another.
 */
class LibrarySchools
{
    public const PER_PAGE = 50;

    public static function switchRules(): array
    {
        return ['enabled' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    public function switch(User $actor, int $schoolId, array $input): bool
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::switchRules())->validate();
        $enabled = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN);
        $school = app(CurrentSchool::class)->acrossSchools(fn () => School::query()->findOrFail($schoolId));

        return DB::transaction(fn (): bool => app(CurrentSchool::class)->run((int) $school->id, function () use ($actor, $school, $enabled, $data): bool {
            $row = LibrarySchoolSetting::query()->lockForUpdate()->first();
            $before = (bool) ($row?->enabled ?? false);
            if ($before === $enabled) {
                return false;
            }
            $row ??= new LibrarySchoolSetting;
            $row->fill(['enabled' => $enabled, 'changed_by' => $actor->id])->save();
            Audit::record('library_school_switched', $school, ['enabled' => $before], ['enabled' => $enabled, 'reason' => trim((string) $data['reason']), 'changed_by' => $actor->id], (int) $school->id);

            return true;
        }));
    }

    /** Whether the current school reads the library. */
    public function enabled(): bool
    {
        return app(CurrentSchool::class)->isSet() && (bool) LibrarySchoolSetting::query()->value('enabled');
    }

    /** @return array{schools: list<array<string, mixed>>, page: int, lastPage: int} */
    public function list(int $page = 1): array
    {
        $page = max(1, min(100000, $page));

        return app(CurrentSchool::class)->acrossSchools(function () use ($page): array {
            $total = DB::table('schools')->count();
            $schools = DB::table('schools')->orderBy('name')->orderBy('id')->offset(($page - 1) * self::PER_PAGE)->limit(self::PER_PAGE)->get(['id', 'name']);
            $on = DB::table('library_school_settings')->whereIn('school_id', $schools->pluck('id'))->where('enabled', true)->pluck('school_id')->map(fn ($id) => (int) $id)->all();

            return ['schools' => $schools->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->name, 'enabled' => in_array((int) $s->id, $on, true)])->all(),
                'page' => $page, 'lastPage' => max(1, (int) ceil($total / self::PER_PAGE))];
        });
    }
}
