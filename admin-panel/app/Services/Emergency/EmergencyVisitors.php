<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencySetting;
use App\Models\Emergency\EmergencyVisitor;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Visitor sign-in at the gate: by a guardian card scanned at a gate reader
 * where the school uses ID cards, otherwise by manual entry. The people still
 * signed in today make the live on-site list for roll call. A visitor's
 * personal details are encrypted, and erased after the school's retention
 * period (default 30 days, see purge()); only the times and the way they
 * signed in remain, for counts.
 */
class EmergencyVisitors
{
    public const DEFAULT_RETENTION_DAYS = 30;

    public const MAX_RETENTION_DAYS = 90;

    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'sign_in' => $operation + ['method' => ['required', 'string', 'in:manual,card'], 'scan_id' => ['nullable', 'required_if:method,card', 'uuid'],
                'name' => ['nullable', 'required_if:method,manual', 'string', 'max:120'], 'phone' => ['nullable', 'string', 'max:30'], 'organisation' => ['nullable', 'string', 'max:120'],
                'reason' => ['required', 'string', 'max:300'], 'host' => ['nullable', 'string', 'max:120']],
            'sign_out' => $operation + ['revision' => ['required', 'integer', 'min:1']],
            'retention' => $operation + ['revision' => ['required', 'integer', 'min:0'], 'visitor_retention_days' => ['required', 'integer', 'between:1,'.self::MAX_RETENTION_DAYS]],
        };
    }

    public function summary(EmergencyVisitor $visitor): array
    {
        return ['id' => $visitor->id, 'method' => $visitor->method, 'name' => $visitor->name, 'phone' => $visitor->phone, 'organisation' => $visitor->organisation,
            'reason' => $visitor->reason, 'host' => $visitor->host, 'signed_in_at' => $visitor->signed_in_at->toIso8601String(),
            'signed_out_at' => $visitor->signed_out_at?->toIso8601String(), 'revision' => $visitor->revision];
    }

    public function signIn(User $actor, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('sign_in'));
        $authorize = fn () => app(EmergencyAccess::class)->responder($actor);

        $receipt = app(EmergencyOperations::class)->once($actor, 'visitor.sign_in', $data, $authorize, function () use ($actor, $data) {
            $person = null;
            if ($data['method'] === 'card') {
                [$scan, , $person] = app(EmergencyScans::class)->guardianCard((string) $data['scan_id']);
                abort_if(EmergencyVisitor::where('scan_id', $scan->id)->exists(), 409, 'This card scan was already used to sign someone in.');
            }
            $visitor = EmergencyVisitor::create([
                'method' => $data['method'], 'user_id' => $person?->id, 'scan_id' => $data['method'] === 'card' ? $data['scan_id'] : null,
                'name' => $person?->full_name ?? trim((string) $data['name']), 'phone' => $data['phone'] ?? null, 'organisation' => $data['organisation'] ?? null,
                'reason' => $data['reason'], 'host' => $data['host'] ?? null, 'signed_in_at' => now()->utc(), 'signed_in_by' => $actor->id, 'revision' => 1,
            ]);
            EmergencyOperations::event($actor, 'visitor.signed_in', null, $visitor->id, ['method' => $visitor->method]);

            return $this->receipt($visitor);
        });

        return $this->current($receipt);
    }

    public function signOut(User $actor, string $id, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('sign_out'));
        $authorize = function () use ($actor, $id) {
            app(EmergencyAccess::class)->responder($actor);
            EmergencyVisitor::findOrFail($id);
        };

        $receipt = app(EmergencyOperations::class)->once($actor, 'visitor.sign_out', ['visitor_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $visitor = EmergencyVisitor::findOrFail($id);
            abort_unless($visitor->revision === $data['revision'] && $visitor->signed_out_at === null && $visitor->redacted_at === null, 409, 'This visitor has already signed out.');
            $visitor->forceFill(['signed_out_at' => now()->utc(), 'signed_out_by' => $actor->id, 'revision' => $visitor->revision + 1])->save();
            EmergencyOperations::event($actor, 'visitor.signed_out', null, $visitor->id);

            return $this->receipt($visitor);
        });

        return $this->current($receipt);
    }

    /**
     * What an operation keeps as its receipt: the visitor's id and state, never
     * their details, so a replay after the purge cannot bring them back.
     */
    private function receipt(EmergencyVisitor $visitor): array
    {
        return ['id' => $visitor->id, 'revision' => $visitor->revision, 'signed_out' => $visitor->signed_out_at !== null];
    }

    /** The visitor as they are now: after the purge their details are gone. */
    private function current(array $receipt): array
    {
        return $this->summary(EmergencyVisitor::findOrFail($receipt['id']));
    }

    /** Everyone signed in today and not yet signed out: who to account for in a roll call. */
    public function onSite(): array
    {
        return EmergencyVisitor::whereNull('signed_out_at')->whereNull('redacted_at')->where('signed_in_at', '>=', $this->startOfSchoolDay())
            ->orderBy('signed_in_at')->get()->map(fn (EmergencyVisitor $visitor) => $this->summary($visitor))->all();
    }

    public function retentionDays(): int
    {
        return (int) (EmergencySetting::query()->value('visitor_retention_days') ?? self::DEFAULT_RETENTION_DAYS);
    }

    public function settings(): array
    {
        $setting = EmergencySetting::query()->first();

        return ['visitor_retention_days' => (int) ($setting?->visitor_retention_days ?? self::DEFAULT_RETENTION_DAYS), 'revision' => (int) ($setting?->revision ?? 0)];
    }

    public function changeRetention(User $actor, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('retention'));
        $authorize = fn () => app(EmergencyAccess::class)->manager($actor);

        return app(EmergencyOperations::class)->once($actor, 'settings.retention', $data, $authorize, function () use ($actor, $data) {
            $setting = EmergencySetting::query()->first();
            if ((int) ($setting?->revision ?? 0) !== $data['revision']) {
                throw ValidationException::withMessages(['revision' => __('Someone else changed these settings. Reload the page and try again.')]);
            }
            $before = ['visitor_retention_days' => (int) ($setting?->visitor_retention_days ?? self::DEFAULT_RETENTION_DAYS)];
            $setting ??= new EmergencySetting(['revision' => 0]);
            $setting->forceFill(['visitor_retention_days' => $data['visitor_retention_days'], 'changed_by' => $actor->id, 'revision' => (int) $setting->revision + 1])->save();
            Audit::record('emergency.visitor_retention_changed', $setting, $before, ['visitor_retention_days' => $setting->visitor_retention_days]);

            return $this->settings();
        });
    }

    /**
     * Erase the personal details of visitors who signed in longer ago than the
     * school's retention period. Runs for every school, whether or not the
     * module is still switched on (emergency:purge-visitors, daily).
     */
    public function purge(): int
    {
        $before = now()->utc()->subDays($this->retentionDays());
        $count = 0;
        EmergencyVisitor::whereNull('redacted_at')->where('signed_in_at', '<', $before)->orderBy('signed_in_at')->chunkById(200, function ($visitors) use (&$count) {
            foreach ($visitors as $visitor) {
                DB::transaction(fn () => $visitor->forceFill(['name' => null, 'phone' => null, 'organisation' => null, 'reason' => null, 'host' => null,
                    'user_id' => null, 'signed_out_at' => $visitor->signed_out_at ?? $visitor->signed_in_at, 'redacted_at' => now()->utc(), 'revision' => $visitor->revision + 1])->save());
                $count++;
            }
        });

        return $count;
    }

    public function cardsAvailable(): bool
    {
        return app(ModuleGate::class)->allows('cards');
    }

    private function startOfSchoolDay(): CarbonImmutable
    {
        return CarbonImmutable::now((string) (app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg'))->startOfDay()->utc();
    }
}
