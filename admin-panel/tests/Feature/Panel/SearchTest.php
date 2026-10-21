<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\SessionYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * GET /search: finds learners, parents, teachers, staff, homerooms, subjects
 * and pages; shows nobody what they could not open; escapes what it returns;
 * keeps to its limits.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private int $yearId;

    /** @var array<string, int> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->yearId = (int) getSettings('session_year')['session_year'];
        $this->superAdmin()->update(['password' => Hash::make('search-test-1')]);
        $this->school();
    }

    public function test_the_super_admin_finds_a_learner_and_opens_their_page(): void
    {
        $response = $this->actingAs($this->superAdmin())->getJson('/search?q=Lerato')->assertOk();

        $learners = $this->group($response->json('groups'), 'learners');
        $this->assertSame('Lerato Dlamini', $learners[0]['title']);
        $this->assertSame(route('student.report', $this->ids['lerato']), $learners[0]['url']);
        $this->assertStringContainsString('Grade 5', $learners[0]['subtitle']);

        // The admission number finds them too, and the page opens.
        $this->assertNotEmpty($this->group($this->getJson('/search?q=DEMO-0001')->json('groups'), 'learners'));
        $this->get($learners[0]['url'])->assertOk()->assertSee('Lerato');
    }

    public function test_people_classes_subjects_and_pages_are_found(): void
    {
        $admin = $this->actingAs($this->superAdmin());

        $this->assertSame('Sipho Dlamini', $this->group($admin->getJson('/search?q=sipho')->json('groups'), 'parents')[0]['title']);
        $this->assertSame('Thandi Mokoena', $this->group($admin->getJson('/search?q=thandi')->json('groups'), 'teachers')[0]['title']);
        $this->assertSame('Grade 5 A', $this->group($admin->getJson('/search?q=grade+5')->json('groups'), 'homerooms')[0]['title']);
        $this->assertSame('Mathematics', $this->group($admin->getJson('/search?q=math')->json('groups'), 'subjects')[0]['title']);

        $pages = $this->group($admin->getJson('/search?q=terms')->json('groups'), 'pages');
        $this->assertSame(route('semester.index'), $pages[0]['url']);
        $notice = $this->group($admin->getJson('/search?q=send+a+notice')->json('groups'), 'pages');
        $this->assertSame(route('announcement.index'), $notice[0]['url']);
        $settings = $this->group($admin->getJson('/search?q=school+profile')->json('groups'), 'pages');
        $this->assertSame(url('settings'), $settings[0]['url']);
    }

    public function test_a_teacher_finds_nothing_they_cannot_open(): void
    {
        $teacher = User::findOrFail($this->ids['thandi_user']);

        $parents = $this->actingAs($teacher)->getJson('/search?q=sipho')->assertOk();
        $this->assertSame([], $this->group($parents->json('groups'), 'parents'));

        $settings = $this->getJson('/search?q=school+profile')->json('groups');
        $this->assertSame([], $this->group($settings, 'pages'), 'A teacher must not find the settings pages');
        $this->assertSame([], $this->group($this->getJson('/search?q=thandi')->json('groups'), 'teachers'));
        $this->assertSame([], $this->group($this->getJson('/search?q=grade+5')->json('groups'), 'homerooms'));

        // Only the learners of their own homeroom, linked to the list they can open.
        $own = $this->group($this->getJson('/search?q=Lerato')->json('groups'), 'learners');
        $this->assertSame('Lerato Dlamini', $own[0]['title']);
        $this->assertSame(route('students.index', ['search' => 'DEMO-0001']), $own[0]['url']);
        $this->assertSame([], $this->group($this->getJson('/search?q=Kabelo')->json('groups'), 'learners'),
            'A learner of another homeroom must not be found');
    }

    public function test_results_are_escaped_in_json_and_on_the_page(): void
    {
        DB::table('users')->where('id', $this->ids['lerato_user'])->update(['first_name' => '<script>alert(1)</script>']);
        $admin = $this->actingAs($this->superAdmin());

        $json = $admin->getJson('/search?q=Dlamini');
        $this->assertStringNotContainsString('<script>', $json->getContent());
        $this->assertSame('<script>alert(1)</script> Dlamini', $this->group($json->json('groups'), 'learners')[0]['title']);

        $page = $admin->get('/search?q=Dlamini')->assertOk();
        $page->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Dlamini', false);
        $page->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_search_keeps_to_its_limits(): void
    {
        for ($i = 1; $i <= 22; $i++) {
            $this->learner("Many$i", 'Zulu', "MANY-$i", $this->ids['homeroom_a']);
        }
        $admin = $this->actingAs($this->superAdmin());

        $this->assertCount(5, $this->group($admin->getJson('/search?q=zulu')->json('groups'), 'learners'));
        $this->assertSame(20, substr_count($admin->get('/search?q=zulu')->getContent(), 'class="search-result"'));

        $this->assertSame([], $admin->getJson('/search?q=z')->json('groups'), 'One letter is too short to search');
        $this->assertSame([], $admin->getJson('/search?q=%25')->json('groups'), 'A % sign is not a wildcard');
        $admin->getJson('/search?q='.str_repeat('a', 500))->assertOk();
        $admin->get('/search')->assertOk()->assertSee(__('search_start_title'));
    }

    public function test_learners_follow_the_selected_year(): void
    {
        $past = SessionYear::create(['name' => 'Last year', 'start_date' => '2020-01-10', 'end_date' => '2020-12-01']);

        $found = $this->actingAs($this->superAdmin())->withSession(['session_year' => $past->id])->getJson('/search?q=Lerato');

        $this->assertSame([], $this->group($found->json('groups'), 'learners'));
    }

    public function test_a_guest_finds_nothing(): void
    {
        $this->get('/search?q=Lerato')->assertDontSee('Lerato Dlamini');
        $this->getJson('/search?q=Lerato')->assertDontSee('Lerato');
    }

    public function test_the_header_has_the_search_box_with_its_shortcuts(): void
    {
        $this->actingAs($this->superAdmin())->get('/home')->assertOk()
            ->assertSee('data-panel-search', false)
            ->assertSee('role="combobox"', false)
            ->assertSee('aria-keyshortcuts="/ Control+K Meta+K"', false);
    }

    /** @return list<array<string, mixed>> */
    private function group(?array $groups, string $key): array
    {
        foreach ($groups ?? [] as $group) {
            if ($group['key'] === $key) {
                return $group['items'];
            }
        }

        return [];
    }

    private function school(): void
    {
        $now = ['created_at' => now(), 'updated_at' => now()];
        $medium = DB::table('mediums')->insertGetId(['name' => 'English'] + $now);
        $section = DB::table('sections')->insertGetId(['name' => 'A'] + $now);
        foreach (['a' => 'Grade 5', 'b' => 'Grade 6'] as $key => $name) {
            $class = DB::table('classes')->insertGetId(['name' => $name, 'medium_id' => $medium] + $now);
            $this->ids["homeroom_$key"] = DB::table('class_sections')->insertGetId(['class_id' => $class, 'section_id' => $section] + $now);
            $this->ids["class_$key"] = $class;
        }
        DB::table('subjects')->insert(['name' => 'Mathematics', 'code' => 'MATH', 'bg_color' => '#5c6bc0', 'image' => 'm.png', 'medium_id' => $medium, 'type' => 'Theory'] + $now);
        $this->ids['category'] = DB::table('categories')->insertGetId(['name' => 'General', 'status' => 1] + $now);

        $thandi = $this->user('Thandi', 'Mokoena', 'thandi@example.com', 'Teacher');
        $this->ids['thandi_user'] = $thandi->id;
        $teacher = DB::table('teachers')->insertGetId(['user_id' => $thandi->id, 'qualification' => 'B.Ed'] + $now);
        DB::table('staffs')->insert(['user_id' => $thandi->id] + $now);
        DB::table('class_teachers')->insert(['class_section_id' => $this->ids['homeroom_a'], 'class_teacher_id' => $teacher, 'session_year_id' => $this->yearId] + $now);

        $sipho = $this->user('Sipho', 'Dlamini', 'sipho@example.com', 'Parent');
        $this->ids['sipho'] = DB::table('parents')->insertGetId(['user_id' => $sipho->id, 'first_name' => 'Sipho', 'last_name' => 'Dlamini', 'gender' => 'Male', 'email' => 'sipho@example.com', 'mobile' => '0820000000', 'occupation' => 'Engineer'] + $now);

        $this->ids['lerato'] = $this->learner('Lerato', 'Dlamini', 'DEMO-0001', $this->ids['homeroom_a']);
        $this->ids['kabelo'] = $this->learner('Kabelo', 'Dlamini', 'DEMO-0002', $this->ids['homeroom_b']);
    }

    private function learner(string $first, string $last, string $admission, int $homeroom): int
    {
        $user = $this->user($first, $last, $admission, 'Student');
        $this->ids[strtolower($first).'_user'] = $user->id;
        $now = ['created_at' => now(), 'updated_at' => now()];
        $class = DB::table('class_sections')->where('id', $homeroom)->value('class_id');
        $student = DB::table('students')->insertGetId([
            'user_id' => $user->id, 'class_id' => $class, 'class_section_id' => $homeroom, 'category_id' => $this->ids['category'],
            'admission_no' => $admission, 'roll_number' => 1, 'admission_date' => now()->toDateString(), 'father_id' => $this->ids['sipho'] ?? null,
        ] + $now);
        DB::table('student_sessions')->insert(['student_id' => $student, 'class_section_id' => $homeroom, 'session_year_id' => $this->yearId, 'status' => 1] + $now);

        return $student;
    }

    private function user(string $first, string $last, string $email, string $role): User
    {
        $user = User::forceCreate([
            'first_name' => $first, 'last_name' => $last, 'email' => $email, 'password' => Hash::make('secret123'),
            'gender' => 'Female', 'mobile' => '0820000000', 'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
