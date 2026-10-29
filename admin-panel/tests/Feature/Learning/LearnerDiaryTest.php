<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\AssignmentSubmission;
use App\Models\Learning\DiaryEntry;
use App\Models\Learning\DiaryItemState;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\School;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesDemoSchool;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * The learner's private diary (engineering/specs/learning/learner-diary.md).
 * DemoSchool: Asha and Ben are in Grade 5 A, Cara in Grade 6 C; the Fraction
 * worksheet (Maths, total 10, due in 7 days) is set for Grade 5 A; Farid is
 * Asha's father and Tara teaches Grade 5 A.
 */
class LearnerDiaryTest extends TestCase
{
    use UsesDemoSchool, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
        SchoolModule::updateOrCreate(['module' => 'diary'], ['enabled' => true, 'in_package' => true]);
        $this->approve($this->school);
    }

    /** A learner's account works only once a guardian has approved it. */
    private function approve(DemoSchool $school): void
    {
        foreach (['asha' => 'farid', 'ben' => 'gina', 'cara' => 'farid'] as $learner => $guardian) {
            User::findOrFail($school->ids[$learner.'_user'])->forceFill(['password_change_required' => false])->save();
            User::findOrFail($school->ids[$guardian.'_user'])->forceFill(['email_verified_at' => now(), 'status' => 1])->save();
            GuardianLink::updateOrCreate(['learner_id' => $school->ids[$learner], 'guardian_user_id' => $school->ids[$guardian.'_user']],
                ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
            LearnerApproval::create(['learner_id' => $school->ids[$learner], 'guardian_user_id' => $school->ids[$guardian.'_user'],
                'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        }
    }

    private function as(string $who, ?DemoSchool $school = null, int $schoolId = 1): static
    {
        $school ??= $this->school;
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->withToken($school->tokens[$who])->withHeaders(['X-School-Id' => (string) $schoolId]);
    }

    private function range(string $who, int $from = -3, int $to = 20): TestResponse
    {
        return $this->as($who)->getJson('/api/v2/diary?'.http_build_query([
            'from' => now()->addDays($from)->toDateString(), 'to' => now()->addDays($to)->toDateString(),
        ]));
    }

    private function create(string $who, array $fields = []): array
    {
        return $this->as($who)->postJson('/api/v2/diary/entries', $fields + [
            'operation_id' => (string) Str::uuid(), 'kind' => 'todo', 'title' => 'Buy poster paper', 'date' => now()->addDay()->toDateString(),
        ])->assertOk()->json('data');
    }

    public function test_a_learner_keeps_private_encrypted_notes_and_to_dos(): void
    {
        $note = $this->create('asha', ['kind' => 'note', 'title' => 'Feeling nervous about the test', 'body' => 'Revise fractions with Ben.']);
        $todo = $this->create('asha', ['title' => 'Study chapter 4', 'date' => null, 'remind_at' => now()->addDay()->format('Y-m-d').' 18:00']);

        $data = $this->range('asha')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertSame(1, $data['school_id']);
        $this->assertSame($this->school->ids['asha_user'], $data['reader_id']);
        $titles = collect($data['entries'])->pluck('title')->all();
        $this->assertContains('Feeling nervous about the test', $titles);
        $this->assertContains('Study chapter 4', $titles, 'An open undated to-do always shows.');
        $this->assertSame('Revise fractions with Ben.', collect($data['entries'])->firstWhere('id', $note['id'])['body']);

        // Nothing readable is stored.
        $raw = DB::table('diary_entries')->whereIn('id', [$note['id'], $todo['id']])->get();
        foreach ($raw as $row) {
            $this->assertStringNotContainsString('nervous', (string) $row->title);
            $this->assertStringNotContainsString('chapter', (string) $row->title);
            $this->assertStringNotContainsString('fractions', (string) $row->body);
        }
        $this->assertArrayNotHasKey('title', DiaryEntry::query()->findOrFail($note['id'])->toArray());

        // Setting DIARY_KEY later keeps older text readable; new text uses the new key.
        config(['services.diary.key' => 'base64:'.base64_encode(random_bytes(32))]);
        $later = $this->create('asha', ['kind' => 'note', 'title' => 'Written after the key change']);
        $titles = collect($this->range('asha')->assertOk()->json('data.entries'))->pluck('title')->all();
        $this->assertContains('Feeling nervous about the test', $titles);
        $this->assertContains('Written after the key change', $titles);
        $refs = DB::table('diary_entries')->whereIn('id', [$note['id'], $later['id']])->pluck('title')
            ->map(fn (string $cipher) => strstr($cipher, '.', true))->unique();
        $this->assertCount(2, $refs, 'Each root key has its own reference.');
    }

    public function test_a_retry_returns_the_original_and_a_stale_edit_is_refused(): void
    {
        $operation = (string) Str::uuid();
        $first = $this->create('asha', ['operation_id' => $operation]);
        $again = $this->create('asha', ['operation_id' => $operation, 'title' => 'Something else']);
        $this->assertSame($first['id'], $again['id']);
        $this->assertSame('Buy poster paper', $again['title']);

        $done = $this->as('asha')->putJson("/api/v2/diary/entries/{$first['id']}", ['revision' => 1, 'title' => 'Buy poster paper', 'done' => true])
            ->assertOk()->json('data');
        $this->assertTrue($done['done']);
        $this->assertSame(2, $done['revision']);
        $this->as('asha')->putJson("/api/v2/diary/entries/{$first['id']}", ['revision' => 1, 'title' => 'Old copy'])->assertStatus(409);

        $this->as('asha')->deleteJson("/api/v2/diary/entries/{$first['id']}")->assertOk();
        $this->assertFalse(DiaryEntry::query()->whereKey($first['id'])->exists(), 'Deleting is permanent.');
    }

    public function test_nobody_else_can_read_or_change_a_learners_diary(): void
    {
        $entry = $this->create('asha', ['kind' => 'note', 'title' => 'Private thought']);

        // Another learner: not in their range, and the entry does not exist for them.
        $this->assertNotContains('Private thought', collect($this->range('ben')->assertOk()->json('data.entries'))->pluck('title')->all());
        $this->as('ben')->putJson("/api/v2/diary/entries/{$entry['id']}", ['revision' => 1, 'title' => 'Changed'])->assertNotFound();
        $this->as('ben')->deleteJson("/api/v2/diary/entries/{$entry['id']}")->assertNotFound();

        // A parent, a teacher and the school admin have no diary at all.
        foreach (['farid', 'tara'] as $who) {
            $this->range($who)->assertForbidden();
            $this->as($who)->postJson('/api/v2/diary/entries', ['operation_id' => (string) Str::uuid(), 'kind' => 'note', 'title' => 'x'])->assertForbidden();
        }
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $admin = $this->superAdmin()->createToken('test')->plainTextToken;
        $this->withToken($admin)->withHeaders(['X-School-Id' => '1'])->getJson('/api/v2/diary?from='.now()->toDateString().'&to='.now()->toDateString())->assertForbidden();
        $this->assertSame('Private thought', $this->range('asha')->json('data.entries.0.title'));
    }

    public function test_the_diary_is_off_until_the_school_switches_it_on(): void
    {
        SchoolModule::where('module', 'diary')->update(['enabled' => false]);

        // A switched-off module is not there at all (as for every module).
        $this->range('asha')->assertNotFound();
    }

    public function test_homework_and_the_school_calendar_are_placed_in_the_diary_automatically(): void
    {
        $items = collect($this->range('asha')->assertOk()->json('data.school_items'));
        $homework = $items->firstWhere('source', 'homework');
        $this->assertSame($this->school->ids['assignment'], $homework['id']);
        $this->assertSame(now()->addDays(7)->toDateString(), $homework['date']);
        $this->assertSame(10, $homework['homework']['total']);
        $this->assertTrue($homework['homework']['submitted'], 'Asha has handed it in (in review).');
        $this->assertTrue($homework['state']['done']);
        $this->assertSame($this->school->ids['holiday'], $items->firstWhere('source', 'holiday')['id']);
        $this->assertSame($this->school->ids['event'], $items->firstWhere('source', 'event')['id']);

        // Ben is in the same class and has not handed it in; Cara is in another class.
        $ben = collect($this->range('ben')->json('data.school_items'))->firstWhere('source', 'homework');
        $this->assertFalse($ben['homework']['submitted']);
        $this->assertFalse($ben['state']['done']);
        $this->assertNull(collect($this->range('cara')->json('data.school_items'))->firstWhere('source', 'homework'));

        // A mark shows once it is given, and a moved due date moves the item.
        AssignmentSubmission::query()->whereKey($this->school->ids['submission'])->update(['status' => 1, 'points' => 8]);
        SchoolTable::query('assignments')->where('id', $this->school->ids['assignment'])->update(['due_date' => now()->addDays(9)->format('Y-m-d').' 17:00:00']);
        $moved = collect($this->range('asha')->json('data.school_items'))->firstWhere('source', 'homework');
        $this->assertSame(now()->addDays(9)->toDateString(), $moved['date']);
        $this->assertSame('accepted', $moved['homework']['status']);
        $this->assertSame(8, $moved['homework']['points']);
    }

    public function test_unsubmitted_homework_that_is_late_shows_today_as_overdue(): void
    {
        SchoolTable::query('assignments')->where('id', $this->school->ids['assignment'])->update(['due_date' => now()->subDays(2)->format('Y-m-d').' 17:00:00']);

        $ben = collect($this->range('ben', 0, 5)->json('data.school_items'))->firstWhere('source', 'homework');
        $this->assertTrue($ben['homework']['overdue']);
        $this->assertSame(now(schoolSetting('time_zone', 'UTC'))->toDateString(), $ben['date']);
        // Asha handed it in, so it is not carried forward.
        $this->assertNull(collect($this->range('asha', 0, 5)->json('data.school_items'))->firstWhere('source', 'homework'));
    }

    public function test_an_elective_is_only_in_the_diary_of_learners_who_take_it(): void
    {
        SchoolTable::query('class_subjects')->where('id', $this->school->ids['class_subject_a_maths'])->update(['type' => 'Elective']);
        SchoolTable::query('student_subjects')->insert(['student_id' => $this->school->ids['asha'], 'subject_id' => $this->school->ids['maths'],
            'class_section_id' => $this->school->ids['section_a'],
            'session_year_id' => $this->school->ids['session_year'], 'created_at' => now(), 'updated_at' => now()]);

        $this->assertNotNull(collect($this->range('asha')->json('data.school_items'))->firstWhere('source', 'homework'));
        $this->assertNull(collect($this->range('ben')->json('data.school_items'))->firstWhere('source', 'homework'));
    }

    public function test_a_learner_ticks_off_and_annotates_a_school_item_privately(): void
    {
        $path = "/api/v2/diary/items/homework/{$this->school->ids['assignment']}/state";
        $this->as('ben')->putJson($path, ['done' => true, 'note' => 'Ask Tara about question 7', 'remind_at' => now()->addDays(2)->format('Y-m-d').' 16:00'])->assertOk();

        $item = collect($this->range('ben')->json('data.school_items'))->firstWhere('source', 'homework');
        $this->assertTrue($item['state']['done']);
        $this->assertSame('Ask Tara about question 7', $item['state']['note']);
        $this->assertStringNotContainsString('question 7', (string) DiaryItemState::query()->firstOrFail()->getRawOriginal('note'));
        // Asha sees none of Ben's layer.
        $this->assertNull(collect($this->range('asha')->json('data.school_items'))->firstWhere('source', 'homework')['state']['note']);

        // Homework that is not Cara's does not exist for her.
        $this->as('cara')->putJson($path, ['done' => true])->assertNotFound();
        $this->as('ben')->putJson('/api/v2/diary/items/homework/999999/state', ['done' => true])->assertNotFound();
    }

    public function test_the_phone_gets_every_upcoming_reminder_and_only_those(): void
    {
        $zone = schoolSetting('time_zone', 'UTC') ?: 'UTC';
        $tomorrow = now($zone)->addDay()->format('Y-m-d');
        $soon = $this->create('asha', ['title' => 'Pack sports kit', 'date' => null, 'remind_at' => $tomorrow.' 18:00']);
        $this->create('asha', ['title' => 'Already done', 'remind_at' => $tomorrow.' 19:00', 'done' => true]);
        $this->create('asha', ['title' => 'Past', 'remind_at' => now($zone)->subDay()->format('Y-m-d').' 07:00']);
        $later = $this->create('asha', ['title' => 'Far week', 'date' => now($zone)->addDays(40)->toDateString(), 'remind_at' => now($zone)->addDays(40)->format('Y-m-d').' 07:30']);
        $homework = $this->school->ids['assignment'];
        $this->as('asha')->putJson("/api/v2/diary/items/homework/{$homework}/state", ['done' => false, 'remind_at' => $tomorrow.' 16:00'])->assertOk();
        $this->as('ben')->putJson("/api/v2/diary/items/homework/{$homework}/state", ['done' => false, 'remind_at' => $tomorrow.' 17:00'])->assertOk();

        $data = $this->as('asha')->getJson('/api/v2/diary/reminders')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertSame([1, $this->school->ids['asha_user']], [$data['school_id'], $data['reader_id']]);
        // Asha has handed the worksheet in, so it needs no reminder; done and past ones have none either.
        $this->assertSame(["entry:{$soon['id']}", "entry:{$later['id']}"], array_column($data['reminders'], 'key'));
        $this->assertSame('Pack sports kit', $data['reminders'][0]['text']);
        $this->assertSame(CarbonImmutable::parse($tomorrow.' 18:00', $zone)->utc()->format('Y-m-d\\TH:i:s\\Z'), $data['reminders'][0]['at']);

        // Ben has not handed it in: his reminder is his own, with the homework's title.
        $ben = $this->as('ben')->getJson('/api/v2/diary/reminders')->assertOk()->json('data.reminders');
        $this->assertSame(["homework:{$homework}"], array_column($ben, 'key'));
        $this->assertSame('Fraction worksheet', $ben[0]['text']);

        $this->as('asha')->getJson('/api/v2/diary/reminders?from=2026-01-01')->assertStatus(422);
        $this->as('farid')->getJson('/api/v2/diary/reminders')->assertForbidden();
    }

    public function test_another_schools_learner_never_reaches_this_schools_diary(): void
    {
        $entry = $this->create('asha', ['kind' => 'note', 'title' => 'School A only']);
        $school = School::forceCreate(['name' => 'Elsewhere School', 'slug' => 'diary-elsewhere', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed((int) $school->id);
        $other = app(CurrentSchool::class)->run((int) $school->id, function () {
            SchoolModule::updateOrCreate(['module' => 'diary'], ['enabled' => true, 'in_package' => true]);
            $demo = DemoSchool::build('Elsewhere');
            $this->approve($demo);

            return $demo;
        });

        // In its own school the other learner sees only their own (empty) diary.
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $own = $this->withToken($other->tokens['asha'])->withHeaders(['X-School-Id' => (string) $school->id])
            ->getJson('/api/v2/diary?from='.now()->subDay()->toDateString().'&to='.now()->addDays(20)->toDateString());
        $this->assertNotContains('School A only', collect($own->json('data.entries'))->pluck('title')->all());

        // Asking for school A with that account is refused, and school A's entry cannot be changed.
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($other->tokens['asha'])->withHeaders(['X-School-Id' => '1'])
            ->putJson("/api/v2/diary/entries/{$entry['id']}", ['revision' => 1, 'title' => 'Changed'])->assertStatus(403);
        $this->assertSame('School A only', $this->range('asha')->json('data.entries.0.title'));
    }

    public function test_requests_are_bounded_and_strict(): void
    {
        $this->as('asha')->getJson('/api/v2/diary?from='.now()->toDateString().'&to='.now()->addDays(80)->toDateString())->assertStatus(422);
        $this->as('asha')->getJson('/api/v2/diary?from='.now()->toDateString().'&to='.now()->subDay()->toDateString())->assertStatus(422);
        $this->as('asha')->postJson('/api/v2/diary/entries', ['operation_id' => (string) Str::uuid(), 'kind' => 'note', 'title' => 'x', 'user_id' => 5])->assertStatus(422);
        $this->as('asha')->postJson('/api/v2/diary/entries', ['operation_id' => (string) Str::uuid(), 'kind' => 'secret', 'title' => 'x'])->assertStatus(422);

        // Only to-dos may be undated: an undated note would never show again.
        $this->as('asha')->postJson('/api/v2/diary/entries', ['operation_id' => (string) Str::uuid(), 'kind' => 'note', 'title' => 'x'])
            ->assertStatus(422)->assertJsonPath('data.errors.date.0', 'A note needs a date.');
        $note = $this->create('asha', ['kind' => 'note']);
        $this->as('asha')->putJson("/api/v2/diary/entries/{$note['id']}", ['revision' => 1, 'title' => 'x', 'date' => null])->assertStatus(422);
        $this->assertSame(0, DiaryEntry::query()->where('user_id', $this->school->ids['asha_user'])->where('kind', 'note')->whereNull('entry_on')->count());
    }
}
