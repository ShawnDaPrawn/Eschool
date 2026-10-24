<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\User;
use App\Services\Panel\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The navigation by job (config/navigation.php): it reaches every page the old
 * sidebar reached, it shows only what the person may open, and every label is in
 * every language file.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every destination of the sidebar before the redesign (resources/views/
     * layouts/sidebar.blade.php at the start of Phase 0): route names, or paths
     * for the links that had no route name. System Update is not here: Phase 0
     * removes it.
     */
    private const OLD_SIDEBAR = [
        'home',
        // Academics
        'medium.index', 'section.index', 'stream.index', 'shifts.index', 'subject.index', 'term.index', 'class.index',
        'class.subject', 'class.select-elective-subjects', 'class.teacher', 'subject-teachers.index', 'students.assign-class',
        'promote-student.index',
        // Students
        'category.index', 'students.create', 'online-registration.index', 'students.index-students-roll-number', 'students.index',
        'generate_id.index', 'student.result.index', 'students.reset_password', 'students.create-bulk-data',
        // Teachers, parents, staff
        'teachers.index', 'teacher.details', 'generate_teacher_id.index', 'parents.index', 'roles.index', 'staff.index',
        'generate_staff_id.index',
        // Leave
        'leave.index', 'leave-master.index', 'leave-report.index', 'leave-request.index', 'student-leave-request.index',
        // Timetable
        'timetable.index', '/class-timetable', '/teacher-timetable',
        // Attendance
        'attendance.index', 'attendance.add-bulk-data', 'attendance.staff_attendance', 'attendance.monthly', 'attendance.view',
        'attendace_report.index',
        // Lessons and assignments
        'lesson.index', 'lesson-topic.index', 'assignment.index', 'assignment.submission',
        // Exams
        'exams.index', 'exam-timetable.index', 'exams.upload-marks', 'exams.view-result', 'exams.get-result', 'grades',
        // Fees and reports
        'fees-type.index', 'fees.class.index', 'fees.paid.index', 'fees.transactions.log.index', 'students.report',
        // Online exams
        'online-exam.index', 'online-exam-question.index', 'online-exam-question.create-bulk', 'online-exam.terms-conditions',
        // Communication and calendar
        'notifications.index', 'announcement.index', 'sliders.index', 'holiday.index', 'events.index', 'session-years.index',
        'photo.index', 'video.index',
        // Web settings
        'content.index', 'educational.index', 'faq.index', 'contact_us.index',
        // System settings
        '/app-settings', '/settings', 'language.index', '/fcm-settings', 'chat_setting.index', 'fees.config.index',
        'setting.email-config-index', 'email.Template', 'id_card_setting.index', 'form-fields.index', 'privacy.index',
        '/contact-us', '/about-us', '/terms-condition',
    ];

    private const SECTIONS = ['home', 'people', 'academics', 'communication', 'money', 'activities', 'wellbeing', 'settings'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_every_page_of_the_old_sidebar_is_in_the_navigation(): void
    {
        $definitions = app(Navigation::class)->definitions();
        $routes = array_filter(array_column($definitions, 'route'));
        $paths = array_map(fn ($url) => '/'.ltrim($url, '/'), array_filter(array_column($definitions, 'url')));

        $missing = array_values(array_filter(self::OLD_SIDEBAR, fn (string $target) => str_starts_with($target, '/')
            ? ! in_array($target, $paths, true)
            : ! in_array($target, $routes, true)));

        $this->assertSame([], $missing, 'Pages the old sidebar reached that the navigation does not');
    }

    public function test_someone_with_every_permission_can_reach_every_old_sidebar_page(): void
    {
        // Like the old sidebar, items follow permissions: the Super Admin has no
        // teacher-only ones (apply for leave, lessons), so give one person all.
        $everyone = $this->user('Super Admin');
        $everyone->givePermissionTo(Permission::all());
        $urls = array_column(app(Navigation::class)->items($everyone->fresh()), 'url');

        foreach (self::OLD_SIDEBAR as $target) {
            $url = str_starts_with($target, '/') ? url($target) : route($target);
            $this->assertContains($url, $urls, "$target cannot be reached from the navigation");
        }
    }

    public function test_the_registry_has_the_eight_sections_and_every_item_points_to_a_page(): void
    {
        $this->assertSame(self::SECTIONS, array_keys(config('navigation.sections')));

        $definitions = app(Navigation::class)->definitions();
        $keys = array_column($definitions, 'key');
        $this->assertSame(array_unique($keys), $keys, 'Navigation keys must be unique');

        foreach ($definitions as $item) {
            $this->assertTrue(isset($item['route']) xor isset($item['url']), "{$item['key']} needs a route or a url");
            $this->assertArrayHasKey('permissions', $item, "{$item['key']} must say which permissions show it");
            $this->assertArrayHasKey('module', $item, "{$item['key']} must name its module (or null)");
            if (isset($item['route'])) {
                $this->assertTrue(Route::has($item['route']), "{$item['key']}: no route named {$item['route']}");
            }
        }
    }

    public function test_every_navigation_label_is_in_every_panel_language_file(): void
    {
        $labels = [];
        foreach (config('navigation.sections') as $section) {
            $labels[] = $section['label'];
            foreach ($section['groups'] as $group) {
                $labels[] = $group['label'];
                foreach ($group['items'] as $item) {
                    $labels[] = $item['label'];
                }
            }
        }

        foreach (File::glob(resource_path('lang/*.json')) as $file) {
            $translations = json_decode(File::get($file), true);
            $missing = array_values(array_diff(array_unique($labels), array_keys($translations)));
            $this->assertSame([], $missing, basename($file).' is missing navigation labels');
        }
    }

    public function test_the_super_admin_sees_all_eight_sections(): void
    {
        $sections = array_column(app(Navigation::class)->sections($this->superAdmin()), 'key');

        $this->assertSame(self::SECTIONS, $sections);
    }

    public function test_a_teacher_sees_only_what_their_permissions_open(): void
    {
        $navigation = app(Navigation::class);
        $teacher = $this->user('Teacher');
        $keys = array_column($navigation->items($teacher), 'key');

        foreach (['home', 'learners', 'take-attendance', 'notices', 'lessons', 'capture-marks', 'apply-leave', 'person-profile'] as $key) {
            $this->assertContains($key, $keys, "A teacher should see $key");
        }
        foreach (['add-learner', 'import-learners', 'school-profile', 'roles', 'fee-types', 'staff', 'school-years', 'parents'] as $key) {
            $this->assertNotContains($key, $keys, "A teacher must not see $key");
        }
        $sections = array_column($navigation->sections($teacher), 'key');
        $this->assertContains('settings', $sections);
        $this->assertNotContains('money', $sections);
    }

    public function test_a_custom_staff_role_sees_only_its_own_sections(): void
    {
        Role::create(['name' => 'Finance', 'custom_role' => 1])->syncPermissions(['fees-type', 'fees-classes', 'fees-paid']);
        $finance = $this->user('Finance');

        $this->assertSame(['home', 'money', 'settings'], array_column(app(Navigation::class)->sections($finance), 'key'));
        $this->assertSame(
            ['home', 'fee-types', 'grade-fees', 'fees-paid', 'payment-log', 'person-profile'],
            array_column(app(Navigation::class)->items($finance), 'key')
        );
    }

    public function test_the_page_shows_the_navigation_with_the_current_section_open(): void
    {
        $this->superAdmin()->update(['password' => Hash::make('navigation-test')]);

        $page = $this->actingAs($this->superAdmin())->get(route('announcement.index'))->assertOk();

        $html = $page->getContent();
        $page->assertSee('data-panel-layout', false);
        $page->assertSee('href="#main-content"', false);
        $this->assertMatchesRegularExpression('#<a class="panel-nav__item" href="'.preg_quote(route('announcement.index'), '#').'"\s+aria-current="page"#', $html);
        $this->assertMatchesRegularExpression('/aria-expanded="true"\s+aria-controls="nav-section-communication"/', $html);
        $this->assertMatchesRegularExpression('/aria-expanded="false"\s+aria-controls="nav-section-settings"/', $html);
        // The open section's links show; the closed ones wait behind their button.
        $this->assertMatchesRegularExpression('/<div class="panel-nav__panel" id="nav-section-communication"\s*>/', $html);
        $this->assertMatchesRegularExpression('/<div class="panel-nav__panel" id="nav-section-settings"\s+hidden\s*>/', $html);
        $page->assertSee(route('students.create'), false);
        $page->assertSee(__('nav_add_learner'));
    }

    public function test_old_sidebar_links_still_open_their_pages(): void
    {
        // The redesign moves pages into sections without changing any address,
        // so bookmarks and old links keep working.
        $admin = $this->actingAs($this->superAdmin());
        foreach (['/medium', '/settings', '/class-timetable', '/announcement', '/fees-type', '/session-years'] as $path) {
            $admin->get($path)->assertOk();
        }
    }

    private function user(string $role): User
    {
        $user = User::forceCreate([
            'first_name' => $role, 'last_name' => 'User', 'email' => strtolower($role).'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
