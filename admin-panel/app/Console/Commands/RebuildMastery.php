<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Academics\TeachingGroup;
use App\Models\Platform\School;
use App\Services\Growth\MasteryRecompute;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Throwable;

class RebuildMastery extends Command
{
    protected $signature = 'mastery:rebuild {school : Numeric school ID} {--group= : Optional owned teaching group ID}';

    protected $description = 'Rebuild derived mastery from canonical marks and frozen releases in one school';

    public function handle(CurrentSchool $schools, MasteryRecompute $mastery): int
    {
        $id = filter_var($this->argument('school'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $group = $this->option('group');
        if ($id === false || ($group !== null && filter_var($group, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)
            || ! School::query()->active()->whereKey($id)->exists()) {
            $this->error('Choose an active school and a valid owned group.');

            return self::FAILURE;
        }
        try {
            return $schools->run($id, function () use ($mastery, $group) {
                $gate = app(ModuleGate::class);
                $gate->forget();
                if (! $gate->allows('mastery')) {
                    $this->error('Enable the mastery module before rebuilding.');

                    return self::FAILURE;
                }
                $groups = TeachingGroup::query()->where('active', true)->when($group !== null, fn ($q) => $q->whereKey((int) $group))->pluck('id');
                if ($group !== null && $groups->isEmpty()) {
                    $this->error('The group is unavailable in this school.');

                    return self::FAILURE;
                }
                foreach ($groups as $groupId) {
                    $mastery->group((int) $groupId);
                }
                $this->info('Rebuilt '.$groups->count().' group contexts; derived counts contain no learner names.');

                return self::SUCCESS;
            });
        } catch (Throwable) {
            $this->error('Rebuild refused. Review the published definitions and retry this school/group.');

            return self::FAILURE;
        }
    }
}
