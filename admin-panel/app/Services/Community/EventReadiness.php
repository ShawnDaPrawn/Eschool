<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Event;
use App\Models\EventReadinessLink;
use App\Models\FamilyForms\ConsentRecipient;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Commerce\TicketCatalogues;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\FamilyFormQueries;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\BusinessAuthority;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Explicit event associations; source consent and admissions authority is never inherited from event-list. */
class EventReadiness
{
    public static function rules(): array
    {
        return ['revision' => 'required|integer|min:0', 'consent_request_id' => 'nullable|uuid', 'ticket_event_id' => 'nullable|integer|min:1', 'transport_notes' => 'nullable|string|max:2000'];
    }

    public function authorize(User $actor, bool $write = false): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating() && $context->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        abort_unless(app(BusinessAuthority::class)->holds($fresh, $write ? 'event-edit' : 'event-list'), 403);
        if ($write) {
            abort_if(app(SessionYearContext::class)->isReadOnly(), 423);
        }

        return $fresh;
    }

    public function read(User $actor, int $id, int $page = 1): array
    {
        $this->authorize($actor);
        $event = Event::findOrFail($id);
        $link = EventReadinessLink::where('event_id', $id)->first();
        $consent = null;
        $ticket = null;
        $participants = null;
        $ready = null;
        if ($link?->consent_request_id) {
            $consent = app(FamilyFormQueries::class)->managed($actor, $link->consent_request_id, $page);
            $participants = ConsentRecipient::where('request_id', $link->consent_request_id)->count();
            $ready = collect($consent['recipients'])->where('effective', true)->count();
        }
        if ($link?->ticket_event_id) {
            app(TicketCatalogues::class)->finance($actor);
            $record = TicketEvent::findOrFail($link->ticket_event_id);
            $ticket = ['id' => $record->id, 'name' => $record->name, 'status' => $record->status,
                'starts_at' => $record->starts_at->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d H:i'),
                'ends_at' => $record->ends_at->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d H:i'),
                'issued' => Ticket::whereIn('type_id', TicketType::where('event_id', $record->id)->select('id'))->count()];
        }
        $this->authorize($actor);

        return compact('event', 'link', 'consent', 'ticket', 'participants', 'ready', 'page');
    }

    public function choices(User $actor, ?int $group, int $page, int $ticketPage = 1, string $ticketSearch = ''): array
    {
        $this->authorize($actor, true);
        $groups = [];
        $consents = ['items' => [], 'next_page' => null];
        $tickets = [];
        $ticketNextPage = null;
        $canLinkTickets = false;
        try {
            $config = app(FamilyFormQueries::class)->configuration($actor);
            $groups = collect($config['groups'])->pluck('name', 'id')->all();
            if ($group !== null) {
                abort_unless(array_key_exists($group, $groups), 404);
                $consents = app(FamilyFormQueries::class)->listing($actor, group: $group, page: $page);
            }
        } catch (HttpExceptionInterface $error) {
            if ($group !== null || ! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }
        }
        try {
            app(TicketCatalogues::class)->finance($actor);
            $canLinkTickets = true;
            $records = TicketEvent::query()->when($ticketSearch !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($ticketSearch, '%_\\').'%'))
                ->orderByDesc('id')->skip(($ticketPage - 1) * 25)->take(26)->get();
            $ticketNextPage = $records->count() > 25 ? $ticketPage + 1 : null;
            $tickets = $records->take(25)->mapWithKeys(fn (TicketEvent $event) => [
                $event->id => $event->name.' · '.$event->starts_at->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d H:i').' · '.__($event->status),
            ])->all();
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }
        }

        return compact('groups', 'consents', 'tickets', 'canLinkTickets', 'ticketPage', 'ticketNextPage', 'ticketSearch');
    }

    public function save(User $actor, int $id, array $input): EventReadinessLink
    {
        $this->authorize($actor, true);
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            $this->authorize($actor, true);
            $event = Event::lockForUpdate()->findOrFail($id);
            abort_unless((int) $event->session_year_id === (int) schoolSetting('session_year'), 423);
            $link = EventReadinessLink::where('event_id', $id)->lockForUpdate()->first() ?? new EventReadinessLink(['event_id' => $id]);
            abort_unless(($link->revision ?? 0) === (int) $data['revision'], 409, __('These event links changed. Refresh and review the saved associations before retrying.'));
            // Current source authority is required to replace or detach any existing association.
            if ($link->consent_request_id) {
                app(Consents::class)->managedRequest($actor, $link->consent_request_id);
            }
            if ($link->ticket_event_id) {
                app(TicketCatalogues::class)->finance($actor);
            }
            if (! empty($data['consent_request_id'])) {
                $consent = app(Consents::class)->managedRequest($actor, $data['consent_request_id']);
                abort_unless((int) $consent->session_year_id === (int) $event->session_year_id, 422, __('The consent request must belong to the event’s school year.'));
            }
            if (array_key_exists('ticket_event_id', $data)) {
                if ($data['ticket_event_id'] !== null || $link->ticket_event_id) {
                    app(TicketCatalogues::class)->finance($actor);
                }
                if ($data['ticket_event_id'] !== null) {
                    TicketEvent::findOrFail($data['ticket_event_id']);
                }
            }
            $before = $link->exists ? $link->only(['consent_request_id', 'ticket_event_id', 'revision']) : [];
            $link->fill($data + ['updated_by' => $actor->id]);
            $link->revision = (int) $data['revision'] + 1;
            $link->save();
            Audit::record('events.readiness_links_saved', $link, $before, $link->only(['consent_request_id', 'ticket_event_id', 'revision']));

            return $link;
        });
    }
}
