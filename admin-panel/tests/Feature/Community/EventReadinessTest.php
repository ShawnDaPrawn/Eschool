<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Commerce\TicketEvent;
use App\Models\Event;
use App\Models\EventReadinessLink;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\FamilyForms\Consents;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class EventReadinessTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private Event $event;

    private ConsentRequest $consent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        Queue::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        config(['modules.family_forms' => ['always_on' => true], 'modules.groups' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $year = $this->schoolA->ids['session_year'];
        $this->event = new Event;
        $this->event->forceFill(['session_year_id' => $year, 'title' => 'Synthetic linked event', 'type' => 'single', 'start_date' => '2026-02-20', 'description' => 'Office event'])->save();
        $group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Private consent group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $this->schoolA->ids['tara_user'], 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic assignment', 'revision' => 1]);
        $learner = $this->schoolA->ids['asha'];
        Enrolment::create(['learner_id' => $learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic consent roster', 'revision' => 1]);
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $learner, 'guardian_user_id' => $guardian->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($guardian, Students::findOrFail($learner), 'approve', config('identity.consent_version'));
        $created = app(Consents::class)->create($this->admin, ['operation_id' => (string) Str::uuid(), 'activity_group_id' => $group->id,
            'title' => 'Private museum consent', 'wording' => 'Only the authorised group may read this.', 'event_starts_at' => '2026-02-20T08:00:00Z', 'event_ends_at' => '2026-02-20T10:00:00Z', 'respond_by' => '2026-02-19T10:00:00Z', 'learner_ids' => [$learner]]);
        $this->consent = ConsentRequest::findOrFail($created['id']);
    }

    private function input(array $changes = []): array
    {
        return array_replace(['revision' => 0, 'expected_school_id' => 1, 'expected_account_id' => $this->admin->id, 'consent_request_id' => $this->consent->id, 'transport_notes' => 'Office coach booked.'], $changes);
    }

    public function test_explicit_association_exposes_real_roster_and_stale_revision_cannot_replace_it(): void
    {
        $url = route('event-readiness.save', $this->event->id);
        $this->actingAs($this->admin)->postJson($url, $this->input())->assertOk()->assertJsonPath('error', false);
        $link = EventReadinessLink::firstOrFail();
        $this->assertSame($this->consent->id, $link->consent_request_id);
        $this->get(route('event-readiness.show', $this->event->id))->assertOk()->assertSee('Private museum consent')->assertSee('Awaiting response')->assertSee('Office coach booked.')->assertHeader('Cache-Control', 'no-store, private');
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        app(Consents::class)->respond($guardian, $this->consent->id, $this->schoolA->ids['asha'], [
            'operation_id' => (string) Str::uuid(), 'revision' => 0, 'fingerprint' => $this->consent->fingerprint,
            'decision' => 'yes', 'acknowledged' => true, 'signed_name' => $guardian->full_name,
        ]);
        $this->get(route('event-readiness.show', $this->event->id))->assertOk()->assertViewHas('participants', 1)->assertViewHas('ready', 1);
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $guardian->id)->update(['status' => 'revoked']);
        $this->get(route('event-readiness.show', $this->event->id))->assertOk()->assertViewHas('ready', 0)->assertSee('Not cleared');
        $this->postJson($url, $this->input(['transport_notes' => 'Stale overwrite']))->assertConflict();
        $this->assertSame('Office coach booked.', $link->fresh()->transport_notes);
        $this->deleteJson(route('events.destroy', $this->event->id))->assertConflict();
        $this->postJson($url, $this->input(['revision' => 1, 'consent_request_id' => null, 'transport_notes' => null]))->assertOk();
        $this->assertNull($link->fresh()->consent_request_id);
        $this->deleteJson(route('events.destroy', $this->event->id))->assertOk();
        $this->assertNull($this->event->fresh());
        $this->assertNotNull($this->consent->fresh());
    }

    public function test_event_permission_does_not_grant_a_different_groups_private_consent(): void
    {
        $this->actingAs($this->admin)->postJson(route('event-readiness.save', $this->event->id), $this->input())->assertOk();
        $outsider = User::findOrFail($this->schoolA->ids['theo_user']);
        $outsider->givePermissionTo(['event-list', 'event-edit']);
        $this->actingAs($outsider)->get(route('event-readiness.show', $this->event->id))->assertStatus(404)->assertDontSee('Private museum consent')->assertDontSee('Office coach');
        $this->postJson(route('event-readiness.save', $this->event->id), $this->input(['expected_account_id' => $outsider->id, 'revision' => 1, 'consent_request_id' => null]))->assertStatus(404);
        $this->assertSame($this->consent->id, EventReadinessLink::firstOrFail()->consent_request_id);
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']))->get(route('event-readiness.show', $this->event->id))->assertRedirect(route('login'));
    }

    public function test_cross_school_event_and_consent_and_stale_actor_pin_are_refused(): void
    {
        [$foreign, $foreignConsent] = app(CurrentSchool::class)->run(2, function () {
            $event = new Event;
            $event->forceFill(['session_year_id' => $this->schoolB->ids['session_year'], 'title' => 'Foreign private event', 'type' => 'single', 'start_date' => '2026-02-20', 'description' => 'Foreign'])->save();
            $group = ActivityGroup::create(['session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab private group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
            $consent = $this->consent->replicate(['id', 'school_id']);
            $consent->forceFill(['session_year_id' => $this->schoolB->ids['session_year'], 'activity_group_id' => $group->id, 'title' => 'Baobab private consent', 'created_by' => $this->schoolB->ids['tara_user']])->save();

            return [$event, $consent];
        });
        $this->actingAs($this->admin)->get(route('event-readiness.show', $foreign->id))->assertNotFound()->assertDontSee('Foreign private event');
        $this->postJson(route('event-readiness.save', $this->event->id), $this->input(['expected_school_id' => 2]))->assertForbidden();
        $this->postJson(route('event-readiness.save', $this->event->id), $this->input(['consent_request_id' => $foreignConsent->id]))->assertNotFound()->assertDontSee('Baobab private consent');
        $this->assertSame(0, EventReadinessLink::count());
    }

    public function test_admissions_require_fresh_ticket_authority_and_cannot_link_another_school(): void
    {
        config(['modules.vendors' => ['always_on' => true], 'modules.ticketing' => ['always_on' => true]]);
        $values = ['name' => 'Private admissions', 'status' => 'draft', 'revision' => 1, 'changed_by' => $this->admin->id, 'sales_from' => '2026-02-10 08:00:00', 'sales_until' => '2026-02-20 08:00:00', 'starts_at' => '2026-02-20 08:00:00', 'ends_at' => '2026-02-20 10:00:00'];
        $ticket = TicketEvent::create($values);
        $foreign = app(CurrentSchool::class)->run(2, fn () => TicketEvent::create(array_replace($values, ['name' => 'Baobab private admissions', 'changed_by' => $this->schoolB->ids['tara_user']])));
        $url = route('event-readiness.save', $this->event->id);
        $this->actingAs($this->admin)->postJson($url, $this->input(['consent_request_id' => null, 'ticket_event_id' => $foreign->id]))->assertNotFound();
        for ($i = 0; $i < 26; $i++) {
            TicketEvent::create(array_replace($values, ['name' => 'Newer synthetic admission '.$i]));
        }
        $this->get(route('event-readiness.show', ['event' => $this->event->id, 'view' => 'links']))->assertOk()
            ->assertViewHas('choices', fn ($choices) => ! isset($choices['tickets'][$ticket->id]) && $choices['ticketNextPage'] === 2);
        $this->get(route('event-readiness.show', ['event' => $this->event->id, 'view' => 'links', 'ticket_page' => 2]))->assertOk()
            ->assertViewHas('choices', fn ($choices) => isset($choices['tickets'][$ticket->id]) && ! isset($choices['tickets'][$foreign->id]));
        $this->get(route('event-readiness.show', ['event' => $this->event->id, 'view' => 'links', 'ticket_search' => 'Private admissions']))->assertOk()
            ->assertViewHas('choices', fn ($choices) => array_keys($choices['tickets']) === [$ticket->id]);
        $this->postJson($url, $this->input(['consent_request_id' => null, 'ticket_event_id' => $ticket->id]))->assertOk();
        $this->get(route('event-readiness.show', $this->event->id))->assertOk()->assertSee('Private admissions')->assertSee('0 tickets issued');
        $outsider = User::findOrFail($this->schoolA->ids['theo_user']);
        $outsider->givePermissionTo(['event-list', 'event-edit']);
        $this->actingAs($outsider)->get(route('event-readiness.show', $this->event->id))->assertForbidden()->assertDontSee('Private admissions');
        $this->postJson($url, $this->input(['expected_account_id' => $outsider->id, 'revision' => 1, 'consent_request_id' => null, 'ticket_event_id' => null]))->assertForbidden();
        $this->assertSame($ticket->id, EventReadinessLink::firstOrFail()->ticket_event_id);
    }
}
