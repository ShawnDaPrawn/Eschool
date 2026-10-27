<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Money\FeePlan;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\FeePlanPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use Illuminate\Support\Facades\Validator;

final class FeePlans
{
    public const MAX_CENTS = 100000000000;

    public function save(User $actor, array $input, ?int $id = null): FeePlan
    {
        app(FeePlanPolicy::class)->authorize($actor);
        $data = Validator::make($input, self::saveRules())->validate();
        $data['target_id'] = $data['scope'] === 'all' ? null : (int) ($data['target_id'] ?? 0);
        $data['plan_id'] = $id;
        $data['amount_cents'] = Money::fromDecimalString($data['amount'], $data['currency'])->cents;
        abort_if($data['amount_cents'] > self::MAX_CENTS, 422, 'The annual plan amount is too large.');
        $receipt = app(FeePlanOperations::class)->once($actor, 'save', $data, function () use ($data, $id): array {
            $year = SessionYear::lockForUpdate()->findOrFail($data['session_year_id']);
            $this->year($year);
            $this->target($data['scope'], $data['target_id']);
            $row = $id === null ? new FeePlan : FeePlan::lockForUpdate()->findOrFail($id);
            abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'], 409, 'This fee plan changed. Review it before saving again.');
            $before = $row->exists ? $row->only(['name', 'amount_cents', 'currency', 'scope', 'target_id', 'state', 'session_year_id', 'revision']) : [];
            $row->fill(array_intersect_key($data, array_flip(['name', 'amount_cents', 'currency', 'scope', 'target_id', 'state', 'session_year_id'])));
            $row->applies_to = app(FeePlanRules::class)->normalize($data['applies_to'] ?? $row->applies_to ?? []);
            $row->payment_options = app(FeePaymentSchedules::class)->normalize($data['payment_options'] ?? $row->payment_options, $year);
            $row->default_payment_option = $data['default_payment_option'] ?? $row->default_payment_option ?? $row->payment_options[0]['key'];
            abort_unless(in_array($row->default_payment_option, array_column($row->payment_options, 'key'), true), 422, 'Choose a configured default payment option.');
            $row->revision = (int) ($row->revision ?? 0) + 1;
            $row->save();
            Audit::record('money.fee_plan_saved', $row, $before, $row->only(['name', 'amount_cents', 'currency', 'scope', 'target_id', 'state', 'session_year_id', 'revision']));

            return ['id' => (int) $row->id, 'revision' => $row->revision];
        });

        return FeePlan::findOrFail($receipt['id']);
    }

    public function copy(User $actor, int $id, array $input): FeePlan
    {
        app(FeePlanPolicy::class)->authorize($actor);
        $data = Validator::make($input, self::copyRules())->validate();
        $data['plan_id'] = $id;
        $receipt = app(FeePlanOperations::class)->once($actor, 'copy', $data, function () use ($data, $id): array {
            $source = FeePlan::lockForUpdate()->findOrFail($id);
            abort_unless($source->revision === (int) $data['revision'], 409, 'This fee plan changed. Review it before copying again.');
            $year = SessionYear::lockForUpdate()->findOrFail($data['session_year_id']);
            $this->year($year);
            $originalYear = SessionYear::lockForUpdate()->findOrFail($source->session_year_id);
            abort_unless(substr((string) $year->start_date, 0, 10) > substr((string) $originalYear->end_date, 0, 10), 422, 'Choose a later, non-overlapping school year.');
            $this->target($source->scope, $source->target_id);
            $amount = Money::ofCents($source->amount_cents, $source->currency);
            $next = $amount->plus($amount->percent($data['uplift_percent']));
            abort_if($next->cents > self::MAX_CENTS, 422, 'The copied plan amount is too large.');
            $row = FeePlan::create($source->only(['scope', 'target_id', 'currency', 'applies_to', 'default_payment_option']) + ['payment_options' => app(FeePaymentSchedules::class)->normalize($source->payment_options, $year)] + ['name' => $data['name'], 'session_year_id' => $year->id, 'amount_cents' => $next->cents, 'state' => 'draft', 'revision' => 1]);
            Audit::record('money.fee_plan_copied', $row, [], ['source_id' => $source->id, 'source_revision' => $source->revision, 'amount_cents' => $next->cents]);

            return ['id' => (int) $row->id, 'revision' => 1];
        });

        return FeePlan::findOrFail($receipt['id']);
    }

    public static function saveRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'string', 'regex:/\A\d{1,9}(?:\.\d{1,2})?\z/D'],
            'currency' => ['required', 'string', 'regex:/\A[A-Z]{3}\z/D'], 'scope' => ['required', 'in:all,grade,class'],
            'target_id' => ['nullable', 'integer', 'min:1', 'required_unless:scope,all', 'prohibited_if:scope,all'], 'state' => ['required', 'in:draft,active,archived'], 'applies_to' => ['sometimes', 'array', 'max:20'], 'payment_options' => ['sometimes', 'array', 'min:1', 'max:6'], 'default_payment_option' => ['sometimes', 'string', 'max:32']];
    }

    public static function copyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'],
            'uplift_percent' => ['required', 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/D']];
    }

    public function year(SessionYear $year): void
    {
        $start = $year->getRawOriginal('start_date');
        $end = $year->getRawOriginal('end_date');
        abort_unless(is_string($start) && is_string($end) && preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $start) && preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $end) && $start <= $end, 422, 'Configure the school year dates first.');
    }

    private function target(string $scope, ?int $id): void
    {
        if ($scope === 'grade') {
            GradeLevel::findOrFail($id);
        } elseif ($scope === 'class') {
            ClassSchool::lockForUpdate()->findOrFail($id);
        }
    }
}
