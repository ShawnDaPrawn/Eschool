<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Academics\TeachingGroup;
use App\Models\Platform\School;
use App\Models\Term;
use App\Services\Academics\MarkDefinitions;
use App\Services\Academics\MarkReconciliation;
use App\Services\Academics\MarkSnapshots;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ReconcileMarks extends Command
{
    protected $signature = 'marks:reconcile {school : Active school ID} {term : School term ID} {--sheet= : Private local CSV path} {--refresh : Recompute current snapshots before comparing}';

    protected $description = 'Compare term snapshots with a school sheet without changing marks; outputs JSON evidence';

    public function handle(): int
    {
        try {
            $school = School::active()->findOrFail((int) $this->argument('school'));

            return app(CurrentSchool::class)->run($school, function () {
                $term = Term::findOrFail((int) $this->argument('term'));
                if ($this->option('refresh')) {
                    TeachingGroup::where('session_year_id', $term->session_year_id)->orderBy('id')->get()->unique(fn ($g) => $g->subject_id.':'.$g->grade_level_id)->each(function ($group) use ($term) {
                        if ($term->coversGrade((int) $group->gradeLevel->sort_order) && app(MarkDefinitions::class)->plans($group)->contains('term_id', $term->id)) {
                            app(MarkSnapshots::class)->recompute((int) $group->id, (int) $term->id);
                        }
                    });
                }
                $result = app(MarkReconciliation::class)->compare((int) $term->id, (string) $this->option('sheet'));
                $this->line(json_encode($result, JSON_THROW_ON_ERROR));

                return array_sum($result['counts']) === $result['counts']['matched'] ? self::SUCCESS : self::FAILURE;
            });
        } catch (ValidationException $error) {
            $this->error($error->validator->errors()->first());
        } catch (ModelNotFoundException) {
            $this->error('School or term not found in the selected school.');
        }

        return self::FAILURE;
    }
}
