<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The HQ audit log (SA.3): who at Navigate Ed did what to which school, when,
 * and why. It reads audit_log rows that are platform actions, never a
 * school's own history: an action named platform_* (or ai.platform_*), a
 * change to a platform staff identity or admin grant, or anything done by a
 * platform staff account. Values that look like secrets are never shown.
 * See engineering/specs/platform/hq-audit-log.md.
 */
class PlatformAuditLog
{
    /** The most rows one export writes; narrow the filters for more. */
    public const EXPORT_LIMIT = 5000;

    public const PER_PAGE = 50;

    /** HQ works in South African time; rows are stored in the app's timezone. */
    public const TIMEZONE = 'Africa/Johannesburg';

    /** Labels for the platform actions HQ staff see most. Others are headlined. */
    private const LABELS = [
        'platform_school_created' => 'School set up',
        'platform_school_package_changed' => 'Package changed',
        'platform_school_status_changed' => 'School status changed',
        'platform_school_module_changed' => 'Module switched',
        'platform_audit_exported' => 'Audit log exported',
        'platform_announcement_published' => 'Announcement published',
        'platform_announcement_withdrawn' => 'Announcement withdrawn',
        'ai.platform_cost_access_changed' => 'AI cost access changed',
    ];

    /** Keys left out of the summary: who changed it is already the actor. */
    private const HIDDEN_KEYS = ['changed_by', 'reason'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * @param  array{school?: int|null, action?: string|null, actor?: int|null, from?: string|null, to?: string|null}  $filters
     */
    public function search(array $filters, int $page = 1): LengthAwarePaginator
    {
        return $this->currentSchool->acrossSchools(function () use ($filters, $page): LengthAwarePaginator {
            $paginator = $this->query($filters)->paginate(self::PER_PAGE, ['audit_log.*'], 'page', $page);
            $paginator->setCollection(collect($this->present($paginator->getCollection()->all())));

            return $paginator;
        });
    }

    /**
     * Rows for the CSV export, newest first, at most EXPORT_LIMIT. The export
     * is itself an audited platform action.
     *
     * @param  array{school?: int|null, action?: string|null, actor?: int|null, from?: string|null, to?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function export(array $filters, int $actorId): array
    {
        return $this->currentSchool->acrossSchools(function () use ($filters, $actorId): array {
            $rows = $this->present($this->query($filters)->limit(self::EXPORT_LIMIT)->get(['audit_log.*'])->all());
            Audit::record('platform_audit_exported', null, [], ['filters' => array_filter($filters, fn ($v) => $v !== null && $v !== ''), 'rows' => count($rows), 'changed_by' => $actorId]);

            return $rows;
        });
    }

    /**
     * Choices for the filters: schools, actions seen in the platform log, and
     * platform staff.
     *
     * @return array{schools: array<int, string>, actions: array<string, string>, actors: array<int, string>}
     */
    public function choices(): array
    {
        return $this->currentSchool->acrossSchools(function (): array {
            $actions = $this->scope(DB::table('audit_log'))->distinct()->orderBy('action')->pluck('action')
                ->mapWithKeys(fn (string $action) => [$action => $this->label($action)])->all();

            return [
                'schools' => DB::table('schools')->orderBy('name')->pluck('name', 'id')->all(),
                'actions' => $actions,
                'actors' => DB::table('users')->whereIn('id', $this->staffIds())->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])
                    ->mapWithKeys(fn ($user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name).' · '.$user->email])->all(),
            ];
        });
    }

    public function label(string $action): string
    {
        return __(self::LABELS[$action] ?? Str::headline(str_replace('.', ' ', $action)));
    }

    /**
     * Validated filters from a request's query string; anything unusable is dropped.
     *
     * @param  array<string, mixed>  $input
     * @return array{school: int|null, action: string|null, actor: int|null, from: string|null, to: string|null}
     */
    public static function filters(array $input): array
    {
        $date = function (mixed $value): ?string {
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return null;
            }
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $value);

            return $parsed !== false && $parsed->format('Y-m-d') === $value ? $value : null;
        };
        $id = fn (mixed $value): ?int => is_numeric($value) && (int) $value > 0 ? (int) $value : null;
        $action = $input['action'] ?? null;

        return [
            'school' => $id($input['school'] ?? null),
            'action' => is_string($action) && preg_match('/^[a-z0-9_.]{1,100}$/', $action) ? $action : null,
            'actor' => $id($input['actor'] ?? null),
            'from' => $date($input['from'] ?? null),
            'to' => $date($input['to'] ?? null),
        ];
    }

    /** @param  array{school?: int|null, action?: string|null, actor?: int|null, from?: string|null, to?: string|null}  $filters */
    private function query(array $filters): Builder
    {
        return $this->scope(DB::table('audit_log'))
            ->when($filters['school'] ?? null, fn (Builder $q, int $school) => $q->where('audit_log.school_id', $school))
            ->when($filters['action'] ?? null, fn (Builder $q, string $action) => $q->where('audit_log.action', $action))
            ->when($filters['actor'] ?? null, fn (Builder $q, int $actor) => $q->where('audit_log.actor_id', $actor))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->where('audit_log.created_at', '>=', CarbonImmutable::parse($from, self::TIMEZONE)->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->where('audit_log.created_at', '<', CarbonImmutable::parse($to, self::TIMEZONE)->addDay()->startOfDay()->setTimezone(config('app.timezone'))))
            ->orderByDesc('audit_log.created_at')->orderByDesc('audit_log.id');
    }

    /** Only platform actions: never a school's own history. */
    private function scope(Builder $query): Builder
    {
        $staff = $this->staffIds();

        return $query->where(function (Builder $q) use ($staff): void {
            $q->where('audit_log.action', 'like', 'platform\_%')
                ->orWhere('audit_log.action', 'like', 'ai.platform\_%')
                ->orWhereIn('audit_log.subject_type', [(new PlatformStaffIdentity)->getMorphClass(), (new PlatformAdminGrant)->getMorphClass()])
                ->when($staff !== [], fn (Builder $inner) => $inner->orWhereIn('audit_log.actor_id', $staff));
        });
    }

    /** @return list<int> */
    private function staffIds(): array
    {
        return DB::table('platform_staff_identities')->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<object>  $rows
     * @return list<array<string, mixed>>
     */
    private function present(array $rows): array
    {
        $schools = DB::table('schools')->whereIn('id', array_filter(array_map(fn ($row) => $row->school_id, $rows)))->pluck('name', 'id');
        $actors = DB::table('users')->whereIn('id', array_filter(array_map(fn ($row) => $row->actor_id, $rows)))->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id');

        return array_map(function (object $row) use ($schools, $actors): array {
            $before = is_string($row->before) ? (array) json_decode($row->before, true) : [];
            $after = is_string($row->after) ? (array) json_decode($row->after, true) : [];
            $actor = $row->actor_id !== null ? $actors->get($row->actor_id) : null;

            return [
                'id' => (int) $row->id,
                'at' => CarbonImmutable::parse($row->created_at, config('app.timezone'))->setTimezone(self::TIMEZONE),
                'school_id' => $row->school_id !== null ? (int) $row->school_id : null,
                'school' => $row->school_id !== null ? ($schools[$row->school_id] ?? __('Removed school')) : __('Platform'),
                'actor' => $actor !== null ? trim($actor->first_name.' '.$actor->last_name) : ($row->actor_type === 'system' ? __('Operator console') : __('Unknown')),
                'actor_email' => $actor?->email,
                'action' => $row->action,
                'label' => $this->label($row->action),
                'subject' => $row->subject_type !== null ? class_basename($row->subject_type).($row->subject_id !== null ? ' #'.$row->subject_id : '') : null,
                'changes' => $this->changes($before, $after),
                'reason' => is_string($after['reason'] ?? null) ? $after['reason'] : null,
                'ip' => $row->ip,
                'request_id' => $row->request_id,
            ];
        }, $rows);
    }

    /**
     * "key: before → after" for each value that is shown.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    private function changes(array $before, array $after): array
    {
        $lines = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (in_array($key, self::HIDDEN_KEYS, true)) {
                continue;
            }
            $secret = (bool) preg_match('/password|token|secret|hash|key_material|otp/i', (string) $key);
            $old = array_key_exists($key, $before) ? ($secret ? '••••' : $this->scalar($before[$key])) : null;
            $new = array_key_exists($key, $after) ? ($secret ? '••••' : $this->scalar($after[$key])) : null;
            $lines[] = $old === null ? $key.': '.$new : ($new === null ? $key.': '.$old.' → —' : $key.': '.$old.' → '.$new);
        }

        return $lines;
    }

    private function scalar(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '—',
            is_scalar($value) => Str::limit((string) $value, 120),
            default => Str::limit((string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 120),
        };
    }
}
