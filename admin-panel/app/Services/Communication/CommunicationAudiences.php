<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Communication\Audience;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;

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
            $allowed = app(CommunicationRecipients::class)->allowed($actor, (int) $data['session_year_id'], app(AcademicAuthority::class)->today());
            $ids = array_map('intval', $data['user_ids']);
            sort($ids);
            abort_if($data['active'] && $ids === [], 422, __('An active audience needs at least one current contact.'));
            abort_unless(array_diff($ids, array_keys($allowed)) === [], 403);
            $row = $id === null ? new Audience : $this->owned($actor, $id);
            abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'], 409, __('The audience changed. Refresh before saving.'));
            $row->fill(['created_by' => $row->created_by ?? $actor->id, 'name' => trim($data['name']), 'definition' => ['user_ids' => $ids], 'active' => $data['active'], 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('communication.audience_saved', $row, [], ['revision' => $row->revision, 'contact_count' => count($ids), 'active' => $row->active]);

            return ['id' => (int) $row->id, 'revision' => $row->revision];
        });
    }

    public function listing(User $actor, int $year): array
    {
        $allowed = app(CommunicationRecipients::class)->allowed($actor, $year, app(AcademicAuthority::class)->today());
        $manage = app(CommunicationRules::class)->manages($actor);

        return Audience::where(fn ($q) => $manage ? $q : $q->where('active', true)->orWhere('created_by', $actor->id))->orderBy('id')->get()->filter(fn ($row) => $manage || (int) $row->created_by === (int) $actor->id || array_diff($row->definition['user_ids'], array_keys($allowed)) === [])->map(fn ($row) => [
            'id' => (int) $row->id, 'name' => $row->name, 'revision' => $row->revision, 'active' => $row->active,
            'editable' => $manage || (int) $row->created_by === (int) $actor->id,
            'user_ids' => array_values(array_intersect($row->definition['user_ids'], array_keys($allowed))),
        ])->values()->all();
    }
}
