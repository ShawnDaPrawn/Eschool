<?php

declare(strict_types=1);

namespace App\Services\Ai\Operations;

use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AiPlatformCosts
{
    public function actor(User $actor): User
    {
        abort_if(app(CurrentSchool::class)->isImpersonating(), 403);
        $fresh = User::where('status', 1)->findOrFail($actor->id);
        abort_if($fresh->password_change_required, 403);
        abort_unless(PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists()
            && PlatformAiCostGrant::where('user_id', $fresh->id)->where('active', true)->where('expires_at', '>', now()->utc())->exists(), 403);

        return $fresh;
    }

    public function report(User $actor, string $from, string $to, int $page = 1): array
    {
        $this->actor($actor);
        $dates = Validator::make(['from' => $from, 'to' => $to], ['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from']])->validate();
        abort_unless(CarbonImmutable::parse($dates['from'])->diffInDays(CarbonImmutable::parse($dates['to'])) <= 366, 422);
        $page = max(1, min(100000, $page));
        $result = app(CurrentSchool::class)->acrossSchools(function () use ($actor, $dates, $page): array {
            $this->actor($actor);
            // Deliberately query only aggregate ledger columns. No flag/session/learner joins.
            $rows = DB::table('ai_usage')->join('schools', 'schools.id', '=', 'ai_usage.school_id')->whereBetween('usage_date', [$dates['from'], $dates['to']])
                ->selectRaw('schools.id AS school_id, schools.name AS school_name, billing_currency, SUM(cost_cents) AS cost_cents, SUM(reserved_cost_cents) AS reserved_cost_cents, SUM(CASE WHEN unit = ? THEN used_units ELSE 0 END) AS used_tokens, SUM(CASE WHEN unit = ? THEN reserved_units ELSE 0 END) AS reserved_tokens', ['tokens', 'tokens'])
                ->groupBy('schools.id', 'schools.name', 'billing_currency')->orderBy('schools.id')->orderBy('billing_currency')->offset(($page - 1) * 50)->limit(51)->get()->map(fn ($row) => (array) $row)->all();
            $this->actor($actor);

            return ['rows' => array_slice($rows, 0, 50), 'has_more' => count($rows) > 50, 'page' => $page, ...$dates];
        });
        $this->actor($actor);

        return $result;
    }
}
