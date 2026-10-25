<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\FamilyForms\ConsentRecipient;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\FamilyForms\ConsentResponse;
use App\Models\Parents;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FamilyFormQueries
{
    public function configuration(User $actor): array
    {
        $fresh = app(FamilyFormAccess::class)->actor($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $children = [];
        if (in_array('Parent', $roles, true)) {
            $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
            $rows = app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->orderBy('id')->limit(201)->get();
            abort_if($rows->count() > 200, 422, 'Narrow the family roster.');
            foreach ($rows as $row) {
                try {
                    app(FamilyFormAccess::class)->guardian($fresh, (int) $row->id);
                    $children[] = ['id' => (int) $row->id, 'person_id' => (int) $row->user_id, 'name' => $row->user->full_name];
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    // Retained links cannot create a new current family reader.
                }
            }
        }
        $groups = [];
        if (array_diff($roles, ['Parent', 'Student']) !== []) {
            try {
                foreach (app(CommunityAuthority::class)->visible($fresh, (int) schoolSetting('session_year'), true) as $group) {
                    if ($group->active) {
                        $groups[] = ['id' => (int) $group->id, 'name' => $group->name, 'session_year_id' => (int) $group->session_year_id];
                    }
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // A health-only role does not inherit group authority.
            }
        }

        foreach ($children as $child) {
            app(FamilyFormAccess::class)->guardian($fresh, $child['id']);
        }
        foreach ($groups as $group) {
            app(CommunityAuthority::class)->groupStaff($fresh, $group['id']);
        }
        app(FamilyFormAccess::class)->actor($fresh);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $fresh->id, 'children' => $children, 'groups' => $groups, 'writable' => app(ModuleGate::class)->writable('family_forms'),
            'signature_name' => $fresh->full_name, 'timezone' => app(CurrentSchool::class)->school()->timezone, 'session_year_id' => (int) schoolSetting('session_year')];
    }

    private function summary(ConsentRequest $request): array
    {
        return ['id' => $request->id, 'title' => $request->title, 'activity_group_id' => (int) $request->activity_group_id,
            'session_year_id' => (int) $request->session_year_id, 'status' => $request->status,
            'event_starts_at' => $request->event_starts_at->toIso8601String(), 'event_ends_at' => $request->event_ends_at->toIso8601String(),
            'respond_by' => $request->respond_by->toIso8601String()];
    }

    public function listing(User $actor, ?int $learner = null, ?int $group = null, int $page = 1): array
    {
        app(FamilyFormAccess::class)->actor($actor);
        abort_unless($page > 0 && $page <= 100000 && (($learner === null) !== ($group === null)), 422, 'Choose one family learner or managed group.');
        $query = ConsentRequest::query();
        if ($learner !== null) {
            app(FamilyFormAccess::class)->guardian($actor, $learner);
            $query->whereIn('id', ConsentRecipient::where('learner_id', $learner)->select('request_id'));
        } else {
            app(CommunityAuthority::class)->groupStaff($actor, $group);
            $query->where('activity_group_id', $group);
        }
        $rows = $query->orderByDesc('created_at')->orderByDesc('id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            if ($learner !== null) {
                app(FamilyFormAccess::class)->guardian($actor, $learner);
            } else {
                app(Consents::class)->managedRequest($actor, $row->id);
            }
            $items[] = $this->summary($row);
        }

        return ['items' => $items, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }

    public function managed(User $actor, string $id, int $page = 1): array
    {
        $request = app(Consents::class)->managedRequest($actor, $id);
        abort_unless($page > 0 && $page <= 100000, 422);
        $rows = ConsentRecipient::where('request_id', $id)->orderBy('learner_id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $recipient) {
            $name = __('Former participant');
            $current = false;
            try {
                $learner = app(FamilyFormAccess::class)->learner((int) $recipient->learner_id, false);
                $name = $learner->user->full_name;
                $current = true;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Historical audit references do not grant a current private profile.
            }
            $response = ConsentResponse::where('recipient_id', $recipient->id)->where('revision', $recipient->revision)->first();
            $items[] = ['learner_id' => (int) $recipient->learner_id, 'name' => $name, 'current' => $current,
                'revision' => $recipient->revision, 'decision' => $response?->decision, 'signed_at' => $response?->signed_at?->toIso8601String(),
                'effective' => app(Consents::class)->effective($actor, $id, (int) $recipient->learner_id)];
        }
        app(Consents::class)->managedRequest($actor, $id);

        return $this->summary($request) + ['wording' => $request->wording, 'fingerprint' => $request->fingerprint, 'revision' => $request->revision,
            'recipients' => $items, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }
}
