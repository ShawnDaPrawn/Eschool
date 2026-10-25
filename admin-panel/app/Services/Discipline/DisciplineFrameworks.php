<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Discipline\DisciplineFramework;
use App\Models\Discipline\DisciplineFrameworkVersion;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\DisciplineChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DisciplineFrameworks
{
    public function save(User $actor, int $year, array $input): array
    {
        app(DisciplineAuthority::class)->manage($actor, $year, true);
        $data = Validator::make($input, ['title' => ['required', 'string', 'max:150'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'counter_scope' => ['required', 'in:same_infraction,all_infractions'], 'reset' => ['required', 'in:school_year,term,rolling_days'],
            'rolling_days' => ['nullable', 'integer', 'between:1,365', 'required_if:reset,rolling_days'],
            'levels' => ['required', 'array', 'list', 'min:2', 'max:8'], 'levels.*' => ['array:code,name,threshold,kind,sanctions'], 'levels.*.code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/', 'distinct'],
            'levels.*.name' => ['required', 'string', 'max:150'], 'levels.*.threshold' => ['required', 'integer', 'between:1,1000'],
            'levels.*.kind' => ['required', 'in:sanction,hearing'], 'levels.*.sanctions' => ['present', 'array', 'max:30'],
            'levels.*.sanctions.*' => ['string', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
            'sanctions' => ['required', 'array', 'list', 'min:1', 'max:30'], 'sanctions.*' => ['array:code,name,kind'], 'sanctions.*.code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/', 'distinct'],
            'sanctions.*.name' => ['required', 'string', 'max:150'], 'sanctions.*.kind' => ['required', 'in:notice,detention,restorative'],
            'infractions' => ['required', 'array', 'list', 'min:1', 'max:100'], 'infractions.*' => ['array:code,name,base_level'], 'infractions.*.code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/', 'distinct'],
            'infractions.*.name' => ['required', 'string', 'max:150'], 'infractions.*.base_level' => ['required', 'string', 'max:40']])->validate();
        $levels = $data['levels'];
        $codes = array_column($data['sanctions'], 'code');
        $prior = 0;
        foreach ($levels as $i => $level) {
            abort_unless((int) $level['threshold'] > $prior && ($i !== 0 || (int) $level['threshold'] === 1), 422, 'Levels must have increasing occurrence thresholds starting at one.');
            $prior = (int) $level['threshold'];
            abort_unless(count(array_unique($level['sanctions'])) === count($level['sanctions']), 422, 'Choose each sanction only once per level.');
            abort_unless($level['kind'] === ($i === count($levels) - 1 ? 'hearing' : 'sanction'), 422, 'Only the last level is a principal hearing.');
            abort_unless(array_diff($level['sanctions'], $codes) === [], 422, 'Choose sanctions defined by this framework.');
            abort_unless($level['kind'] === 'hearing' ? $level['sanctions'] === [] : $level['sanctions'] !== [], 422, 'Sanction levels require choices; hearings cannot impose an automatic sanction.');
        }
        foreach ($data['infractions'] as $infraction) {
            abort_unless(in_array($infraction['base_level'], array_column($levels, 'code'), true), 422, 'Choose a defined base level.');
        }

        return DB::transaction(function () use ($actor, $year, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(DisciplineAuthority::class)->manage($actor, $year, true);
            $row = DisciplineFramework::where('session_year_id', $year)->lockForUpdate()->first() ?? new DisciplineFramework(['session_year_id' => $year]);
            abort_unless((int) ($row->revision ?? 0) === (int) $data['expected_revision'], 409, 'The framework changed. Reload before publishing.');
            $definition = $data;
            unset($definition['expected_revision'],$definition['title']);
            $row->fill(['title' => $data['title'], 'definition' => $definition, 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            DisciplineFrameworkVersion::create(['framework_id' => $row->id, 'actor_id' => $actor->id, 'revision' => $row->revision, 'definition' => ['title' => $row->title] + $definition, 'created_at' => now()]);
            Outbox::record(new DisciplineChanged(app(CurrentSchool::class)->id(), $year));
            Audit::record('discipline.framework_published', $row, [], ['revision' => $row->revision]);

            return ['id' => (int) $row->id, 'session_year_id' => $year, 'title' => $row->title, 'revision' => $row->revision, 'definition' => $definition];
        });
    }

    public function read(User $actor, int $year): array
    {
        app(DisciplineAuthority::class)->actor($actor);
        app(DisciplineAuthority::class)->year($year);
        $row = DisciplineFramework::where('session_year_id', $year)->first();

        return ['session_year_id' => $year, 'framework' => $row === null ? null : ['id' => (int) $row->id, 'title' => $row->title, 'revision' => $row->revision, 'definition' => $row->definition]];
    }
}
