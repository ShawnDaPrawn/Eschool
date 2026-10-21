<?php

namespace Tests\Feature;

use App\Models\EmailTempalate;
use App\Models\Parents;
use App\Models\Staff;
use App\Models\Students;
use App\Models\Subject;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\ParentPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_a_parent_only_reaches_their_own_children(): void
    {
        $mother = $this->user('Parent', 'mother@example.com');
        ParentPolicyService::acceptCurrent($mother);
        $parent = Parents::forceCreate([
            'user_id' => $mother->id, 'first_name' => 'Mia', 'last_name' => 'Doe', 'gender' => 'Female',
            'mobile' => '1', 'occupation' => 'x', 'email' => $mother->email,
        ]);
        $own = $this->student('A1', ['mother_id' => $parent->id]);
        $other = $this->student('B1', ['father_id' => 999]);
        StudentSessions::forceCreate(['student_id' => $own->id, 'session_year_id' => getSettings('session_year')['session_year'], 'status' => 1]);

        // children() used to be a UNION whose chained where() only filtered the father branch.
        $this->assertSame([$own->id], $parent->children()->pluck('id')->all());
        $this->assertFalse($parent->children()->whereKey($other->id)->exists());

        $token = $mother->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/parent/subjects?child_id='.$other->id)->assertJson(['error' => true, 'code' => 105]);
        $this->withToken($token)->getJson('/api/parent/subjects?child_id=0 OR 1')->assertJson(['error' => true, 'code' => 105]);
    }

    public function test_app_tokens_only_work_for_their_own_role(): void
    {
        $student = $this->withToken($this->user('Student', 'student@example.com')->createToken('t')->plainTextToken);
        $student->getJson('/api/teacher/classes')->assertForbidden();
        $student->getJson('/api/parent/get-profile-data')->assertForbidden();

        $this->app['auth']->forgetGuards();
        $teacher = $this->withToken($this->user('Teacher', 'teacher@example.com')->createToken('t')->plainTextToken);
        $teacher->getJson('/api/student/dashboard')->assertForbidden();
        $teacher->getJson('/api/parent/get-profile-data')->assertForbidden();
    }

    public function test_app_logins_and_password_resets_are_rate_limited(): void
    {
        foreach (['api/student/login', 'api/student/forgot-password', 'api/parent/login', 'api/teacher/login', 'api/forgot-password', 'password/email'] as $uri) {
            $route = Route::getRoutes()->match(Request::create('/'.$uri, 'POST'));
            $this->assertContains('throttle:login', $route->gatherMiddleware(), $uri);
        }

        $limits = RateLimiter::limiter('login')(Request::create('/api/teacher/login', 'POST', ['email' => 'A@x.com']));
        $this->assertSame(5, $limits[0]->maxAttempts);
        $this->assertStringContainsString('a@x.com', $limits[0]->key);
    }

    public function test_the_admin_panel_needs_a_staff_role(): void
    {
        $roleless = $this->user(null, 'nobody@example.com');

        $this->actingAs($roleless)->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_self_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'x@example.com', 'password' => 'password1', 'password_confirmation' => 'password1'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_only_email_setting_managers_can_edit_email_templates(): void
    {
        $before = EmailTempalate::where('type', 'staff_registration')->value('content');

        $this->actingAs($this->user('Teacher', 'teacher@example.com'))
            ->postJson('/email/store', ['type' => 'staff', 'content' => '@php system("id"); @endphp'])
            ->assertForbidden();

        $this->assertSame($before, EmailTempalate::where('type', 'staff_registration')->value('content'));
    }

    public function test_subjects_need_subject_permissions(): void
    {
        $subject = Subject::forceCreate(['name' => 'Maths', 'bg_color' => '#fff', 'image' => 'x.png', 'medium_id' => 1, 'type' => 'Theory']);

        $this->actingAs($this->user('Teacher', 'teacher@example.com'))
            ->deleteJson('/subject/'.$subject->id)
            ->assertJson(['error' => true]);

        $this->assertNotNull(Subject::find($subject->id));
    }

    public function test_email_templates_fill_placeholders_without_running_blade(): void
    {
        $html = EmailTemplateService::render('Hi {$name} of {class_name}. {$unknown} @php echo 1; @endphp {{ 7*7 }}', [
            'name' => '<b>Ann</b>',
            'class_name' => '5A',
        ]);

        $this->assertSame('Hi &lt;b&gt;Ann&lt;/b&gt; of 5A. {$unknown} @php echo 1; @endphp {{ 7*7 }}', $html);
    }

    public function test_pages_send_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    private function user(?string $role, string $email): User
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        if ($role) {
            $user->assignRole($role);
        }
        if ($role === 'Teacher') {
            Staff::forceCreate(['user_id' => $user->id]);
        }

        return $user;
    }

    private function student(string $admissionNo, array $parents): Students
    {
        $user = $this->user('Student', strtolower($admissionNo).'@example.com');

        return Students::forceCreate(array_merge([
            'user_id' => $user->id, 'category_id' => 1, 'admission_no' => $admissionNo, 'admission_date' => '2024-01-01',
        ], $parents));
    }
}
