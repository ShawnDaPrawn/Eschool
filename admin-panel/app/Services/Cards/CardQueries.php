<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Parents;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CardQueries
{
    public function configuration(User $actor): array
    {
        $fresh = app(CardAccess::class)->actor($actor);
        $policy = app(ProfilePolicy::class);

        return ['can_manage' => $policy->permission($fresh, CardAccess::MANAGE), 'can_manage_readers' => $policy->permission($fresh, CardAccess::READERS),
            'writable' => app(ModuleGate::class)->writable('cards'), 'types' => ['learner', 'staff', 'guardian']];
    }

    public function listing(User $actor, bool $managed = false, int $page = 1): array
    {
        $access = app(CardAccess::class);
        $fresh = $managed ? $access->manage($actor, false, false) : $access->actor($actor);
        $query = Card::query()->orderBy('user_id')->orderBy('issued_at');
        if (! $managed) {
            $people = [(int) $fresh->id];
            if (in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true)) {
                $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
                $people = array_merge($people, app(GuardianService::class)
                    ->childrenFor((int) $fresh->id, $parent)->pluck('user_id')->map(fn ($id) => (int) $id)->all());
            }
            // Check only the account's candidate identities, not every school card.
            $allowed = [];
            foreach (Card::whereIn('user_id', $people)->select(['id', 'user_id', 'type'])->cursor() as $card) {
                try {
                    $access->person((int) $card->user_id, $card->type);
                    $access->owner($fresh, $card);
                    $allowed[] = $card->id;
                } catch (HttpException|ModelNotFoundException) {
                }
            }
            $query->whereIn('id', $allowed);
        }
        $rows = $query->offset(($page - 1) * 25)->limit(26)->get();

        return ['items' => $rows->take(25)->map(fn ($card) => app(Cards::class)->metadata($card))->values()->all(), 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }

    public function read(User $actor, string $id): array
    {
        $access = app(CardAccess::class);
        $access->actor($actor);
        $card = Card::findOrFail($id);
        $access->owner($actor, $card);
        $access->person((int) $card->user_id, $card->type);

        return app(Cards::class)->metadata($card);
    }

    public function people(User $actor, string $type, string $search = '', int $page = 1): array
    {
        app(CardAccess::class)->manage($actor, false, false);
        $query = User::where('status', 1)->orderBy('id');
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%'));
        }
        $rows = $query->offset(($page - 1) * 25)->limit(26)->get();
        $people = [];
        foreach ($rows->take(25) as $person) {
            try {
                app(CardAccess::class)->person((int) $person->id, $type);
                $people[] = ['id' => (int) $person->id, 'name' => $person->full_name, 'type' => $type];
            } catch (HttpException|ModelNotFoundException) {
            }
        }

        return ['items' => $people, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }
}
