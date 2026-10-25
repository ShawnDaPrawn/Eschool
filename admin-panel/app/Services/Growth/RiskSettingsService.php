<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\RiskSettings;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Events\RiskRulesConfigured;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RiskSettingsService
{
    public function values(): array
    {
        $row = RiskSettings::first();

        return ['academic_threshold_bp' => $row?->academic_threshold_bp ?? 6500, 'minimum_coverage_bp' => $row?->minimum_coverage_bp ?? 2500,
            'absence_threshold_bp' => $row?->absence_threshold_bp ?? 1000, 'minimum_registers' => $row?->minimum_registers ?? 10,
            'revision' => $row?->revision ?? 0, 'rule_version' => 'observed-counts-v1'];
    }

    public function configure(User $actor, array $input): array
    {
        app(RiskAuthority::class)->principal($actor, true);
        $data = Validator::make($input, ['academic_threshold_bp' => ['required', 'integer', 'between:1,10000'],
            'minimum_coverage_bp' => ['required', 'integer', 'between:1,10000'], 'absence_threshold_bp' => ['required', 'integer', 'between:1,10000'],
            'minimum_registers' => ['required', 'integer', 'between:1,30'], 'expected_revision' => ['required', 'integer', 'min:0']])->validate();

        return DB::transaction(function () use ($actor, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(RiskAuthority::class)->principal($actor, true);
            $row = RiskSettings::lockForUpdate()->first() ?? new RiskSettings;
            abort_unless((int) ($row->revision ?? 0) === (int) $data['expected_revision'], 409, 'Early-warning rules changed. Reload before saving.');
            unset($data['expected_revision']);
            $row->fill($data + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('growth.risk_rules_changed', $row, [], ['revision' => $row->revision]);
            Outbox::record(new RiskRulesConfigured(app(CurrentSchool::class)->id()));

            return $this->values();
        });
    }
}
