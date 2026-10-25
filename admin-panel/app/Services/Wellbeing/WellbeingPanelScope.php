<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Also invoked before historical-year refusals may reflect a private draft. */
class WellbeingPanelScope
{
    public function operation(Request $request): string
    {
        return Str::after((string) $request->route()->getName(), 'wellbeing.');
    }

    public function configurationOperation(string $operation): bool
    {
        return in_array($operation, ['category-create', 'category-save', 'team-create', 'team-save', 'case-create', 'case-assignment-create', 'case-assignment-save'], true);
    }

    private function identifier(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        abort_unless((is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value > 0, 422);

        return (int) $value;
    }

    public function authorize(Request $request): bool
    {
        $access = app(WellbeingAccess::class);
        abort_unless($request->user() instanceof User, 403);
        $operation = $this->operation($request);
        $write = ! $request->isMethodSafe() && $operation !== 'case-choices';
        $actor = $access->actor($request->user(), $write);
        $body = $request->isJson() ? $request->json()->all() : $request->request->all();
        if (! $request->isMethodSafe()) {
            abort_if($request->query->count() > 0, 422, 'Private form values belong in the request body.');
            abort_unless($this->identifier($body['expected_account_id'] ?? null) === (int) $actor->id
                && $this->identifier($body['expected_school_id'] ?? null) === app(CurrentSchool::class)->id(), 403);
        }
        $mode = $request->isMethodSafe() ? $request->query('mode', $access->professionalAvailable($actor) ? 'clinical' : 'configuration')
            : ($this->configurationOperation($operation) ? 'configuration' : 'clinical');
        abort_unless(in_array($mode, ['configuration', 'clinical'], true), 422);
        if ($mode === 'configuration') {
            $access->configure($actor, $write);
        } else {
            $access->professional($actor, $write);
            $case = $this->identifier($request->route('case') ?? ($request->isMethodSafe() ? $request->query('case') : ($body['case'] ?? null)));
            $report = $this->identifier($request->route('report') ?? ($request->isMethodSafe() ? $request->query('report') : ($body['report'] ?? null)));
            if ($case !== null) {
                $access->case($actor, $case, $write);
            }
            if ($report !== null) {
                $access->report($actor, $report, $write);
            }
        }

        return true;
    }
}
