<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\Intervention;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

/** Also runs before a historical-year refusal may reflect this user's draft. */
class RiskPanelScope
{
    public function authorize(Request $r): bool
    {
        abort_if(! $r->isMethodSafe() && $r->query->count() > 0, 422, 'Private changes must be sent in the request body.');
        $a = app(RiskAuthority::class);
        $config = app(RiskQueries::class)->configuration($r->user());
        if (! $r->isMethodSafe()) {
            abort_unless((int) $r->input('expected_account_id') === (int) $r->user()->id && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        }
        $op = substr((string) $r->route()->getName(), strlen('risk-interventions.'));
        $year = $this->id($r->input('session_year_id'), $config['session_year_id']);
        $a->year($year);
        $id = $this->id($r->route('intervention') ?? $r->input('intervention'));
        if ($op === 'configure') {
            $a->principal($r->user());

            return true;
        }
        if ($op === 'transfer') {
            $row = Intervention::select(Interventions::META)->findOrFail($id);
            $a->administer($r->user(), (int) $row->learner_id, (int) $row->session_year_id);

            return true;
        }
        if ($id > 0 && $r->input('mode') !== 'assignments') {
            app(Interventions::class)->authorized($r->user(), $id, ! $r->isMethodSafe());

            return true;
        }
        $learner = $this->id($r->route('learner') ?? $r->input('learner_id'));
        if (in_array($op, ['assign', 'revoke'], true) || $r->input('mode') === 'assignments') {
            abort_unless($config['can_manage'] || $config['grade_ids'] !== [], 403);
            if ($learner > 0) {
                $a->administer($r->user(), $learner, $year);
            }

            return true;
        }
        if ($op === 'create' && $learner > 0) {
            $a->caseActor($r->user(), $learner, $year, $a->currentGrade($learner, $year));
        }

        return true;
    }

    private function id(mixed $v, int $default = 0): int
    {
        return (is_int($v) || (is_string($v) && ctype_digit($v))) && (int) $v > 0 ? (int) $v : $default;
    }
}
