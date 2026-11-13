<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Announcement;
use App\Models\ClassSchool;
use App\Models\Event;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class EventWorkspaceTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function programme(string $title = 'Synthetic sports programme'): array
    {
        return ['title' => $title, 'event_type' => 'multiple', 'session_year_id' => $this->schoolA->ids['session_year'], 'date_range' => '2026-06-15 - 2026-06-16',
            'events' => [['title' => 'Opening ceremony', 'date' => '2026-06-15', 'timerange' => '08:00:00 - 09:00:00', 'description' => 'Welcome'], ['title' => 'Soccer final', 'date' => '2026-06-16', 'timerange' => '14:00:00 - 15:00:00', 'description' => 'Under 14 final']]];
    }

    public function test_create_and_edit_all_programme_items_and_single_event_conversion(): void
    {
        $this->actingAs($this->superAdmin())->get('/events?view=create')->assertOk()->assertSee('Add programme item');
        $this->postJson('/events', $this->programme())->assertOk()->assertJsonPath('error', false);
        $event = Event::where('title', 'Synthetic sports programme')->firstOrFail();
        $this->assertSame(2, $event->multipleEvent()->count());
        $this->get('/events?event_id='.$event->id)->assertOk()->assertSee('Opening ceremony')->assertSee('Soccer final');
        $rows = $event->multipleEvent->map(fn ($row) => ['id' => $row->id, 'title' => $row->title.' updated', 'date' => $row->date, 'timerange' => $row->start_time.' - '.$row->end_time, 'description' => $row->description])->all();
        $this->putJson('/events/'.$event->id, ['edit_id' => $event->id, 'title' => 'Updated sports day', 'edit_event_type' => 'multiple', 'date_range' => '2026-06-15 - 2026-06-16', 'edit_events' => $rows])->assertOk()->assertJsonPath('error', false);
        $this->assertSame('Opening ceremony updated', $event->multipleEvent()->first()->title);
        $this->putJson('/events/'.$event->id, ['edit_id' => $event->id, 'title' => 'Assembly', 'edit_event_type' => 'single', 'edit_date' => '2026-06-15'])->assertOk()->assertJsonPath('error', false);
        $this->assertSame(0, $event->multipleEvent()->count());
    }

    public function test_invalid_last_row_and_wrong_parent_leave_every_record_unchanged(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/events', $this->programme())->assertOk();
        $event = Event::where('title', 'Synthetic sports programme')->firstOrFail();
        $this->postJson('/events', $this->programme('Other school event'))->assertOk();
        $other = Event::where('title', 'Other school event')->firstOrFail();
        $base = ['edit_id' => $event->id, 'title' => 'Must not save', 'edit_event_type' => 'multiple', 'date_range' => '2026-06-15 - 2026-06-16'];
        $row = ['id' => $event->multipleEvent()->first()->id, 'title' => 'Must not update', 'date' => '2026-06-15', 'timerange' => '08:00:00 - 09:00:00'];
        $this->putJson('/events/'.$event->id, $base + ['edit_events' => [$row], 'events' => [['title' => 'Outside range', 'date' => '2026-06-20', 'timerange' => '08:00:00 - 09:00:00']]])->assertUnprocessable();
        $this->putJson('/events/'.$event->id, $base + ['edit_events' => [array_replace($row, ['id' => $other->multipleEvent()->first()->id])]])->assertNotFound();
        $this->assertSame('Synthetic sports programme', $event->fresh()->title);
        $this->assertSame('Opening ceremony', $event->multipleEvent()->first()->title);
        $this->assertSame(2, $other->multipleEvent()->count());
    }

    public function test_wrong_role_and_school_cannot_edit_and_invalid_year_cannot_create(): void
    {
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']))->postJson('/events', $this->programme())->assertRedirect(route('login'));
        $this->actingAs($this->superAdmin())->postJson('/events', array_replace($this->programme(), ['session_year_id' => $this->schoolB->ids['session_year']]))->assertNotFound();
        $foreign = app(CurrentSchool::class)->run(2, function () {
            $event = new Event;
            $event->title = 'Foreign private event';
            $event->type = 'single';
            $event->session_year_id = $this->schoolB->ids['session_year'];
            $event->start_date = '2026-06-15';
            $event->save();

            return $event;
        });
        $this->get('/events?event_id='.$foreign->id)->assertNotFound()->assertDontSee('Foreign private event');
    }

    public function test_school_staff_can_manage_class_notices_without_granting_teacher_or_foreign_record_access(): void
    {
        $this->actingAs($this->superAdmin())->get('/announcement?view=create')->assertOk()->assertSee('Whole class');
        $data = ['title' => 'Synthetic class notice', 'description' => 'Office notice for the selected class.', 'set_data' => 'class', 'get_data' => [$this->schoolA->ids['class_a']]];
        $this->postJson('/announcement', $data)->assertOk()->assertJsonPath('error', false);
        $notice = Announcement::where('title', $data['title'])->firstOrFail();
        $this->assertSame(ClassSchool::class, $notice->table_type);
        $this->assertSame($this->schoolA->ids['class_a'], (int) $notice->table_id);
        $this->get('/announcement?notice_id='.$notice->id)->assertOk()->assertSee('school-notice-editor')->assertSee('Add attachments')->assertSee('Synthetic class notice');
        $edit = ['edit_id' => $notice->id, 'title' => 'Updated class notice', 'description' => 'Updated office wording.', 'set_data' => 'class', 'get_data' => $this->schoolA->ids['class_a']];
        $this->putJson('/announcement/'.$notice->id, $edit)->assertOk()->assertJsonPath('error', false);
        $this->assertSame('Updated class notice', $notice->fresh()->title);
        $this->postJson('/announcement', array_replace($data, ['get_data' => [$this->schoolA->ids['class_a'], $this->schoolB->ids['class_a']]]))->assertNotFound();
        $this->assertSame(0, Announcement::where('title', $data['title'])->count());
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo(['announcement-create', 'announcement-edit', 'announcement-delete']);
        $this->actingAs($teacher)->postJson('/announcement', $data)->assertForbidden();
        $this->get('/announcement?notice_id='.$notice->id)->assertForbidden()->assertDontSee('Updated class notice');
        $this->postJson('/announcement', array_replace($data, ['set_data' => 'noticeboard']))->assertForbidden();
        $this->putJson('/announcement/'.$notice->id, array_replace($edit, ['set_data' => 'noticeboard']))->assertForbidden();
        $this->deleteJson('/announcement/'.$notice->id)->assertForbidden();
        $this->assertSame('Updated class notice', $notice->fresh()->title);
        $this->actingAs($this->superAdmin())->deleteJson('/announcement/'.$notice->id)->assertOk()->assertJsonPath('error', false);
        $this->assertSoftDeleted('announcements', ['id' => $notice->id]);
    }

    public function test_teacher_notice_editor_uses_only_owned_current_allocations(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo(['announcement-list', 'announcement-create', 'announcement-edit', 'announcement-delete']);
        $data = ['title' => 'Owned subject notice', 'description' => 'Synthetic teacher wording.', 'set_data' => 'class_section', 'class_section_id' => $this->schoolA->ids['section_a'], 'get_data' => [$this->schoolA->ids['maths']]];
        $this->actingAs($teacher)->postJson('/announcement', $data)->assertOk()->assertJsonPath('error', false);
        $notice = Announcement::where('title', $data['title'])->firstOrFail();
        $this->get('/announcement?notice_id='.$notice->id)->assertOk()->assertSee('Owned subject notice')->assertSee('Allocated class and subject');
        $this->postJson('/announcement', array_replace($data, ['class_section_id' => $this->schoolB->ids['section_a']]))->assertNotFound();
        $this->postJson('/announcement', array_replace($data, ['term_id' => $this->schoolB->ids['term']]))->assertNotFound();
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $other->givePermissionTo(['announcement-list', 'announcement-edit']);
        $this->actingAs($other)->get('/announcement?notice_id='.$notice->id)->assertForbidden()->assertDontSee('Owned subject notice');
    }
}
