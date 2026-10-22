<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Middleware\EnforcePasswordChange;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * Learners whose password is still their date of birth are flagged for a
 * forced change; installed apps keep signing in, and learn about it from an
 * added password_change_required field.
 */
class DateOfBirthPasswordTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        // Leo's date of birth is 2015-06-01.
        $this->buildSignInSchool();
    }

    public function test_the_command_flags_learners_in_every_date_format_the_seed_used(): void
    {
        $learners = [];
        foreach (['01062015' => true, '01-06-2015' => true, '01/06/2015' => true, '2015-06-01' => true, '20150601' => true, 'Not-the-dob-2015' => false] as $password => $flag) {
            $learners[] = [$this->learner((string) $password, '2015-06-01'), $flag];
        }
        $noDob = $this->learner('01062015', null);
        // Teachers are made to change a date-of-birth password at the panel; this is for learners.
        $this->person('teacher')->forceFill(['password' => Hash::make('12041990')])->save();

        $this->artisan('learners:flag-dob-passwords', ['--chunk' => 2])
            ->expectsOutputToContain('Flagged 5 of 7 learner(s) checked')
            ->assertExitCode(Command::SUCCESS);

        foreach ($learners as [$learner, $flag]) {
            $this->assertSame($flag, $learner->fresh()->password_change_required, $learner->first_name);
        }
        $this->assertFalse($noDob->fresh()->password_change_required);
        $this->assertFalse($this->person('teacher')->password_change_required);
    }

    public function test_the_command_is_idempotent_and_has_a_dry_run(): void
    {
        $learner = $this->learner('01062015', '2015-06-01');

        $this->artisan('learners:flag-dob-passwords', ['--dry-run' => true])->expectsOutputToContain('Would flag 1 of 2');
        $this->assertFalse($learner->fresh()->password_change_required);

        $this->artisan('learners:flag-dob-passwords')->expectsOutputToContain('Flagged 1 of 2');
        // Flagged learners are not checked again.
        $this->artisan('learners:flag-dob-passwords')->expectsOutputToContain('Flagged 0 of 1')->assertExitCode(0);
        $this->assertTrue($learner->fresh()->password_change_required);
    }

    public function test_the_migration_adds_the_flag_and_queues_the_command(): void
    {
        Queue::fake();
        Schema::shouldReceive('table')->once()->withArgs(fn ($table) => $table === 'users');

        (require database_path('migrations/2026_10_22_090000_add_password_change_required_to_users_table.php'))->up();

        Queue::assertPushed(QueuedCommand::class, fn (QueuedCommand $job) => str_contains(serialize($job), 'learners:flag-dob-passwords'));
    }

    public function test_v1_sign_in_keeps_working_and_reports_the_flag(): void
    {
        $learner = $this->person('learner');

        // Signing in with the date of birth works as before, says a change is needed, and flags the account.
        $learner->forceFill(['password' => Hash::make('01062015')])->save();
        $this->postJson('/api/student/login', ['gr_number' => 'GR-LEO', 'password' => '01062015'])
            ->assertOk()
            ->assertJson(['error' => false, 'password_change_required' => true])
            ->assertJsonStructure(['token', 'data' => ['first_name']])
            ->assertJsonMissingPath('data.password_change_required');
        $this->assertTrue($learner->fresh()->password_change_required);

        // Changing it ends the forced change.
        $token = $learner->createToken('student-app')->plainTextToken;
        $this->freshDevice();
        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => '01062015', 'new_password' => 'Harbour-lights-77', 'new_confirm_password' => 'Harbour-lights-77',
        ])->assertJson(['error' => false]);
        $this->assertFalse($learner->fresh()->password_change_required);

        $this->freshDevice();
        $this->postJson('/api/student/login', ['gr_number' => 'GR-LEO', 'password' => 'Harbour-lights-77'])
            ->assertJson(['error' => false, 'password_change_required' => false]);
    }

    public function test_parent_and_teacher_sign_ins_carry_the_field_too(): void
    {
        $this->postJson('/api/parent/login', ['email' => 'pam@example.com', 'password' => 'secret123'])
            ->assertJson(['error' => false, 'password_change_required' => false]);

        $this->person('teacher')->forceFill(['password' => Hash::make('12041990')])->save();
        $this->freshDevice();
        $this->postJson('/api/teacher/login', ['email' => 'tom@example.com', 'password' => '12041990'])
            ->assertJson(['error' => false, 'password_change_required' => true]);
    }

    public function test_apps_that_support_it_are_held_until_the_password_changes(): void
    {
        $learner = $this->person('learner');
        $learner->forceFill(['password_change_required' => true])->save();
        $token = $learner->createToken('student-app')->plainTextToken;

        // An installed app (no header) is not affected.
        $this->withToken($token)->getJson('/api/student/get-profile-data')->assertOk()->assertJson(['error' => false]);

        $this->freshDevice();
        $this->withToken($token)->withHeader(EnforcePasswordChange::HEADER, '1')->getJson('/api/student/get-profile-data')
            ->assertForbidden()
            ->assertJson(['error' => true, 'code' => EnforcePasswordChange::CODE]);

        // The change itself is allowed, and lifts the hold.
        $this->freshDevice();
        $this->withToken($token)->withHeader(EnforcePasswordChange::HEADER, '1')->postJson('/api/change-password', [
            'current_password' => 'secret123', 'new_password' => 'Harbour-lights-77', 'new_confirm_password' => 'Harbour-lights-77',
        ])->assertJson(['error' => false]);
        $this->freshDevice();
        $this->withToken($token)->withHeader(EnforcePasswordChange::HEADER, '1')->getJson('/api/student/get-profile-data')
            ->assertOk()->assertJson(['error' => false]);
    }

    public function test_a_flagged_account_must_change_its_password_in_the_panel(): void
    {
        $teacher = $this->person('teacher');
        $teacher->forceFill(['password' => Hash::make('Tom-panel-2026'), 'password_change_required' => true])->save();

        $this->post('/login', ['email' => 'tom@example.com', 'password' => 'Tom-panel-2026']);
        $this->get('/home')->assertRedirect('resetpassword');
    }

    private function learner(string $password, ?string $dob): User
    {
        static $n = 0;
        $n++;
        $user = User::forceCreate([
            'first_name' => "Learner$n", 'last_name' => 'Dob', 'email' => "GR-DOB-$n", 'password' => Hash::make($password),
            'gender' => 'Female', 'mobile' => '0123456789', 'dob' => $dob, 'status' => 1,
        ]);
        $user->assignRole('Student');

        return $user;
    }

    private function freshDevice(): void
    {
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }
}
