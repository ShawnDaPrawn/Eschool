<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Communication\Audience;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CommunicationAudiences
{
    public function owned(User $actor, int $id): Audience
    {
        $rules = app(CommunicationRules::class);
        $rules->staff($actor);
        $row = Audience::findOrFail($id);
        abort_unless((int) $row->created_by === (int) $actor->id || $rules->manages($actor), 404);

        return $row;
    }

    public function save(User $actor, ?int $id, array $data): array
    {
        $authority = function () use ($actor, $id) {
            app(CommunicationRules::class)->staff($actor);
            if ($id !== null) {
                $this->owned($actor, $id);
            }
        };

        return app(CommunicationOperations::class)->once($actor, 'audience', ['id' => $id] + $data, $authority, function () use ($actor, $id, $data) {
            SessionYear::findOrFail($data['session_year_id']);
            $row = $id === null ? new Audience : $this->owned($actor, $id);
            $kind = $id === null ? ($data['kind'] ?? Audience::FIXED) : $row->kind;
            abort_unless(($data['kind'] ?? $kind) === $kind, 422, __('A saved audience cannot change between a fixed list and a smart group.'));
            abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'], 409, __('The audience changed. Refresh before saving.'));
            if ($kind === Audience::SMART) {
                // Archival may retain a saved definition whose scopes are no longer usable.
                // New audiences and edits to the rules still need today's scope authority.
                $definition = $this->retainsArchivedDefinition($row, $data) ? $row->definition : $this->smartDefinition($actor, $data);
                $count = null;
            } else {
                $allowed = app(CommunicationRecipients::class)->allowed($actor, (int) $data['session_year_id'], app(AcademicAuthority::class)->today());
                $ids = array_map('intval', $data['user_ids'] ?? []);
                sort($ids);
                abort_if($data['active'] && $ids === [], 422, __('An active audience needs at least one current contact.'));
                abort_unless(array_diff($ids, array_keys($allowed)) === [], 403);
                $definition = ['user_ids' => $ids];
                $count = count($ids);
            }
            $row->fill(['created_by' => $row->created_by ?? $actor->id, 'kind' => $kind, 'name' => trim($data['name']), 'definition' => $definition, 'active' => $data['active'],
                'shared' => (bool) ($data['shared'] ?? $row->shared ?? true), 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('communication.audience_saved', $row, [], ['revision' => $row->revision, 'kind' => $kind, 'contact_count' => $count,
                'rule_count' => $kind === Audience::SMART ? count($definition['rules']) : null, 'active' => $row->active, 'shared' => $row->shared]);

            return ['id' => (int) $row->id, 'revision' => $row->revision];
        });
    }

    /**
     * A smart group's rules, each one an audience scope the actor may address
     * now: the same check a message to that scope gets.
     *
     * @return array{session_year_id: int, match: string, parents: bool, rules: list<array{kind: string, target_id: int}>}
     */
    private function smartDefinition(User $actor, array $data): array
    {
        $rules = $this->selectedRules($data);
        abort_if($rules === [] || count($rules) > Audience::MAX_RULES, 422, __('A smart group needs between one and ten rules.'));
        $year = (int) $data['session_year_id'];
        foreach ($rules as $rule) {
            abort_unless(in_array($rule['kind'], Audience::RULE_KINDS, true), 422);
            app(MessagePolicy::class)->scope($actor, $rule + ['session_year_id' => $year], app(AcademicAuthority::class)->today());
        }

        return ['session_year_id' => $year, 'match' => ($data['match'] ?? 'any') === 'all' ? 'all' : 'any', 'parents' => (bool) ($data['parents'] ?? false), 'rules' => $rules];
    }

    private function retainsArchivedDefinition(Audience $row, array $data): bool
    {
        if (! $row->exists || $data['active'] || (int) $data['session_year_id'] !== (int) $row->definition['session_year_id']) {
            return false;
        }
        $rules = $this->selectedRules($data);

        return $rules === [] || ($rules === $row->definition['rules']
            && ($data['match'] ?? 'any') === $row->definition['match']
            && (bool) ($data['parents'] ?? false) === $row->definition['parents']);
    }

    private function selectedRules(array $data): array
    {
        return collect($data['rules'] ?? [])->map(fn ($rule) => ['kind' => (string) $rule['kind'], 'target_id' => (int) $rule['target_id']])
            ->unique(fn ($rule) => $rule['kind'].':'.$rule['target_id'])->sortBy(fn ($rule) => $rule['kind'].':'.str_pad((string) $rule['target_id'], 15, '0', STR_PAD_LEFT))->values()->all();
    }

    /** May the actor see and use this saved audience in this year? */
    public function usable(User $actor, Audience $row, int $year, ?array $allowed = null, ?bool $manage = null): bool
    {
        $manage ??= app(CommunicationRules::class)->manages($actor);
        if ($manage || (int) $row->created_by === (int) $actor->id) {
            return true;
        }
        if (! $row->active || ! $row->shared) {
            return false;
        }
        if ($row->isSmart()) {
            if ((int) $row->definition['session_year_id'] !== $year) {
                return false;
            }
            try {
                foreach ($row->definition['rules'] as $rule) {
                    app(MessagePolicy::class)->scope($actor, $rule + ['session_year_id' => $year], app(AcademicAuthority::class)->today());
                }

                return true;
            } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                    throw $error;
                }

                return false;
            }
        }
        $allowed ??= app(CommunicationRecipients::class)->allowed($actor, $year, app(AcademicAuthority::class)->today());

        return array_diff($row->definition['user_ids'], array_keys($allowed)) === [];
    }

    /**
     * How many people a smart group reaches today (the preview before saving
     * or sending), or null when one of its rules names a group that is no
     * longer active: the group stays editable so the rule can be removed, and
     * sending still refuses it.
     */
    public function count(Audience $row): ?int
    {
        if (! $row->isSmart()) {
            return null;
        }
        try {
            return count(app(CommunicationRecipients::class)->smart($row->definition, app(AcademicAuthority::class)->today()));
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && $error->getStatusCode() !== 404) {
                throw $error;
            }

            return null;
        }
    }

    public function listing(User $actor, int $year): array
    {
        $allowed = app(CommunicationRecipients::class)->allowed($actor, $year, app(AcademicAuthority::class)->today());
        $manage = app(CommunicationRules::class)->manages($actor);

        return Audience::where(fn ($q) => $manage ? $q : $q->where(fn ($shared) => $shared->where('active', true)->where('shared', true))->orWhere('created_by', $actor->id))->orderBy('id')->get()
            ->filter(fn (Audience $row) => (! $row->isSmart() || (int) $row->definition['session_year_id'] === $year) && $this->usable($actor, $row, $year, $allowed, $manage))
            ->map(fn (Audience $row) => [
                'id' => (int) $row->id, 'name' => $row->name, 'revision' => $row->revision, 'active' => $row->active,
                'editable' => $manage || (int) $row->created_by === (int) $actor->id,
                'kind' => $row->kind, 'shared' => $row->shared,
                'user_ids' => $row->isSmart() ? [] : array_values(array_intersect($row->definition['user_ids'], array_keys($allowed))),
                'smart' => $row->isSmart() ? ['match' => $row->definition['match'], 'parents' => $row->definition['parents'], 'rules' => $row->definition['rules']] : null,
            ])->values()->all();
    }
}
