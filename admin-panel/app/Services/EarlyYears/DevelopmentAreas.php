<?php

declare(strict_types=1);

namespace App\Services\EarlyYears;

use App\Models\EarlyYears\DevelopmentArea;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Each school's development areas for observations (Action Plan 4.3). A school
 * starts with the six Early Learning and Development Areas of South Africa's
 * National Curriculum Framework for children from birth to four, which also
 * fit Grade R, and can rename, add or switch them off.
 */
class DevelopmentAreas
{
    public const DEFAULTS = [
        ['Well-being', 'Health, physical development, feelings and safety'],
        ['Identity and belonging', 'Knowing who they are, their family, culture and friends'],
        ['Communication', 'Listening, talking, early reading and early writing'],
        ['Exploring mathematics', 'Numbers, shapes, patterns, sorting and measuring'],
        ['Creativity', 'Art, music, movement, play and imagination'],
        ['Knowledge and understanding of the world', 'Nature, people, places, things and how they work'],
    ];

    /** The school's areas, with the defaults added the first time a school has none. */
    public function all(bool $activeOnly = false): Collection
    {
        if (! DevelopmentArea::query()->exists()) {
            DB::transaction(function (): void {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                if (DevelopmentArea::query()->exists()) {
                    return;
                }
                foreach (self::DEFAULTS as $position => [$name, $description]) {
                    DevelopmentArea::create(['name' => $name, 'description' => $description, 'position' => $position + 1, 'active' => true, 'revision' => 1]);
                }
            });
        }

        return DevelopmentArea::query()->when($activeOnly, fn ($q) => $q->where('active', true))->orderBy('position')->orderBy('id')->get();
    }

    public function save(User $actor, ?int $id, array $input): DevelopmentArea
    {
        app(EarlyYearsAuthority::class)->manage($actor, true);
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:80', Rule::unique('early_years_development_areas', 'name')->where('school_id', app(CurrentSchool::class)->id())->ignore($id)],
            'description' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'active' => ['required', 'boolean'],
            'revision' => [$id === null ? 'nullable' : 'required', 'integer', 'min:0'],
        ])->validate();
        $this->all();

        return DB::transaction(function () use ($actor, $id, $data): DevelopmentArea {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(EarlyYearsAuthority::class)->manage($actor, true);
            $row = $id === null ? new DevelopmentArea(['revision' => 0]) : DevelopmentArea::lockForUpdate()->findOrFail($id);
            abort_unless($id === null || (int) $row->revision === (int) $data['revision'], 409, __('This development area changed. Reload before saving.'));
            if (! (bool) $data['active'] && $row->active) {
                abort_if(DevelopmentArea::where('active', true)->whereKeyNot($row->id ?? 0)->doesntExist(), 422, __('Keep at least one development area switched on.'));
            }
            $before = $row->exists ? ['name' => $row->name, 'active' => $row->active, 'position' => $row->position] : [];
            $row->fill(['name' => trim($data['name']), 'description' => $data['description'] ?? null, 'active' => (bool) $data['active'],
                'position' => $data['position'] ?? ($row->position ?? ((int) DevelopmentArea::max('position') + 1)), 'revision' => (int) $row->revision + 1])->save();
            Audit::record('early_years.area_saved', $row, $before, ['name' => $row->name, 'active' => $row->active, 'position' => $row->position]);

            return $row;
        });
    }
}
