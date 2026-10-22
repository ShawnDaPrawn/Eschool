<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Imports\StudentsImport;
use App\Models\Platform\LearnerInvitation;
use App\Models\User;
use App\Services\Platform\Identity\LearnerInvitations;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Mailer\SentMessage;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * New learners get no date-of-birth password: each guardian is emailed an
 * expiring, single-use link to choose it, and the school can print a one-time
 * set-up code.
 */
class LearnerInvitationTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    private const GOOD = 'Harbour-lights-77';

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array', 'mail.from.address' => 'office@school.example']);
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
    }

    public function test_a_new_learner_gets_a_random_password_and_each_guardian_a_link(): void
    {
        $second = $this->secondParent();
        $learner = $this->addLearner(['father_email' => $this->people['parent_row'], 'mother_email' => $second]);

        foreach (['15032016', '15-03-2016', '2016-03-15'] as $dob) {
            $this->assertFalse(Hash::check($dob, $learner->password), "password is not the date of birth ($dob)");
        }

        $invitations = $this->sentTo('learner-setup/');
        $this->assertEqualsCanonicalizing(['pam@example.com', 'max@example.com'], array_keys($invitations));
        $this->assertSame(2, LearnerInvitation::open()->where('learner_user_id', $learner->id)->where('channel', 'link')->count());

        // The welcome emails no longer carry a learner password.
        foreach ($this->sentMessages() as $message) {
            $this->assertStringNotContainsString('15032016', $message->getOriginalMessage()->getHtmlBody());
        }
        $this->assertStringContainsString(e(trans('learner_password_by_invitation')), implode('', array_map(
            fn (SentMessage $m) => (string) $m->getOriginalMessage()->getHtmlBody(), $this->sentMessages())));
    }

    public function test_a_guardian_sets_the_password_with_the_link_once(): void
    {
        $second = $this->secondParent();
        $learner = $this->addLearner(['father_email' => $this->people['parent_row'], 'mother_email' => $second]);
        $links = $this->sentTo('learner-setup/');
        $link = $links['pam@example.com'];

        $this->get($link)->assertOk()->assertSee($learner->full_name)->assertSee($learner->email);

        // The password rule applies.
        $this->from($link)->post($link, ['password' => 'short1', 'password_confirmation' => 'short1'])
            ->assertRedirect($link)->assertSessionHasErrors('password');

        $this->post($link, ['password' => self::GOOD, 'password_confirmation' => self::GOOD])
            ->assertOk()->assertSee(trans('learner_setup_done_title'));
        $this->assertTrue(Hash::check(self::GOOD, $learner->fresh()->password));

        // Single use: the same link and the other guardian's link are closed.
        $this->get($link)->assertOk()->assertSee(trans('learner_setup_link_invalid_title'));
        $this->post($link, ['password' => 'Other-pass-2026', 'password_confirmation' => 'Other-pass-2026'])
            ->assertSee(trans('learner_setup_link_invalid_title'));
        $this->get($links['max@example.com'])->assertSee(trans('learner_setup_link_invalid_title'));
        $this->assertTrue(Hash::check(self::GOOD, $learner->fresh()->password));

        // And the learner signs in with it.
        $this->postJson('/api/student/login', ['gr_number' => $learner->email, 'password' => self::GOOD])
            ->assertJson(['error' => false, 'password_change_required' => false]);
    }

    public function test_a_link_expires_and_cannot_be_tampered_with(): void
    {
        $learner = $this->addLearner(['father_email' => $this->people['parent_row'], 'mother_email' => $this->secondParent()]);
        $link = $this->sentTo('learner-setup/')['pam@example.com'];

        // Another token, or a changed expiry, breaks the signature.
        $this->get(preg_replace('#/learner-setup/(\d+)/[^?]+#', '/learner-setup/$1/'.Str::random(64), $link))
            ->assertSee(trans('learner_setup_link_invalid_title'));
        $this->get(preg_replace('/expires=\d+/', 'expires='.(time() + 9_999_999), $link))
            ->assertSee(trans('learner_setup_link_invalid_title'));

        $this->travel(config('auth.learner_invitations.link_days') + 1)->days();
        $this->get($link)->assertSee(trans('learner_setup_link_invalid_title'));
        $this->post($link, ['password' => self::GOOD, 'password_confirmation' => self::GOOD])
            ->assertSee(trans('learner_setup_link_invalid_title'));
        $this->assertFalse(Hash::check(self::GOOD, $learner->fresh()->password));
    }

    public function test_the_school_prints_a_one_time_set_up_code(): void
    {
        $learner = $this->person('learner');
        $sheet = fn () => $this->actingAs($this->superAdmin())->post('/students/'.$learner->id.'/sign-in-sheet')->assertOk();

        $first = $this->codeFrom($sheet()->assertSee($learner->email)->getContent());
        $code = $this->codeFrom($sheet()->getContent());
        $this->assertNotSame($first, $code);
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $setUp = fn (string $gr, string $code, string $password = self::GOOD) => $this->from('/learner-setup')->post('/learner-setup', [
            'gr_number' => $gr, 'code' => $code, 'password' => $password, 'password_confirmation' => $password,
        ]);

        // A printed code stops working once a newer one is printed.
        $setUp($learner->email, $first)->assertRedirect('/learner-setup')->assertSessionHasErrors('code');
        $setUp('GR-NOBODY', $code)->assertSessionHasErrors('code');
        $setUp($learner->email, $code, 'weakweakweak')->assertSessionHasErrors('password');

        // Lower case and without the dash is fine, once.
        $setUp($learner->email, strtolower(str_replace('-', '', $code)))->assertOk()->assertSee(trans('learner_setup_done_title'));
        $this->assertTrue(Hash::check(self::GOOD, $learner->fresh()->password));
        $setUp($learner->email, $code, 'Second-try-2026')->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check(self::GOOD, $learner->fresh()->password));
    }

    public function test_only_staff_who_manage_learners_can_print_or_send(): void
    {
        $learner = $this->person('learner');
        $teacher = $this->person('teacher');

        $this->actingAs($teacher)->post('/students/'.$learner->id.'/sign-in-sheet')->assertForbidden();
        $this->actingAs($teacher)->postJson('/students/'.$learner->id.'/invite-guardians')->assertJson(['error' => true]);
        $this->assertSame(0, LearnerInvitation::count());

        // Only learners: not the teacher's own account.
        $this->actingAs($this->superAdmin())->post('/students/'.$teacher->id.'/sign-in-sheet')->assertNotFound();
        $this->actingAs($this->superAdmin())->postJson('/students/'.$teacher->id.'/invite-guardians')->assertJson(['error' => true]);
    }

    public function test_sending_again_replaces_the_earlier_links(): void
    {
        $learner = $this->person('learner');

        $this->actingAs($this->superAdmin())->postJson('/students/'.$learner->id.'/invite-guardians')->assertJson(['error' => false]);
        $old = $this->sentTo('learner-setup/')['pam@example.com'];
        $this->actingAs($this->superAdmin())->postJson('/students/'.$learner->id.'/invite-guardians')->assertJson(['error' => false]);

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get($old)->assertSee(trans('learner_setup_link_invalid_title'));
        $this->assertSame(1, LearnerInvitation::open()->where('learner_user_id', $learner->id)->count());
    }

    public function test_a_staff_reset_sends_the_guardians_a_link_instead_of_the_date_of_birth(): void
    {
        $learner = $this->person('learner');
        $learner->forceFill(['reset_request' => 1])->save();
        $learner->createToken('student-app');

        $response = $this->actingAs($this->superAdmin())->postJson('/student-change-password', ['id' => $learner->id])
            ->assertJson(['error' => false, 'message' => trans('learner_invitation_sent', ['count' => 1])]);

        $this->assertStringEndsWith('/students/'.$learner->id.'/sign-in-sheet', $response->json('data.sheet_url'));
        $fresh = $learner->fresh();
        $this->assertFalse(Hash::check('01062015', $fresh->password));
        $this->assertFalse(Hash::check('secret123', $fresh->password));
        $this->assertSame(0, (int) $fresh->reset_request);
        $this->assertSame(0, $learner->tokens()->count());
        $this->assertArrayHasKey('pam@example.com', $this->sentTo('learner-setup/'));
    }

    public function test_accepting_an_online_application_invites_the_guardians(): void
    {
        $learner = $this->person('learner');
        $learner->forceFill(['status' => 0, 'password' => Hash::make(LearnerInvitations::unguessablePassword())])->save();

        $this->actingAs($this->superAdmin())->postJson('/update-active-status', [
            'edit_id' => $learner->id, 'status' => 1, 'class_id' => $this->people['class'], 'class_section_id' => $this->people['class_section'],
        ])->assertJson(['error' => false]);

        $this->assertArrayHasKey('pam@example.com', $this->sentTo('learner-setup/'));
    }

    public function test_an_import_gives_learners_random_passwords_and_invites_after_the_file_is_in(): void
    {
        $columns = StudentsImport::COLUMNS;
        $row = array_fill_keys($columns, '');
        $row = array_merge($row, [
            'first_name' => 'Ivy', 'last_name' => 'Import', 'gender' => 'Female', 'dob' => '2016-03-15', 'mobile' => '0123456789',
            'category' => 'General', 'admission_date' => now()->toDateString(), 'blood_group' => 'O+', 'height' => '120', 'wieght' => '25',
            'current_address' => '1 Road', 'permanent_address' => '1 Road', 'parents' => 'no', 'guardian' => 'yes',
            'guardian_first_name' => 'Gus', 'guardian_last_name' => 'Guard', 'guardian_email' => 'gus@example.com',
            'guardian_mobile' => '0123456789', 'guardian_dob' => '1980-01-01', 'guardian_gender' => 'Male', 'guardian_occupation' => 'Pilot',
        ]);
        $path = tempnam(sys_get_temp_dir(), 'import').'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $columns);
        fputcsv($handle, array_map(fn ($column) => $row[$column], $columns));
        fclose($handle);

        $this->actingAs($this->superAdmin());
        Excel::import(new StudentsImport($this->people['class_section']), $path);

        $ivy = User::role('Student')->where('first_name', 'Ivy')->firstOrFail();
        $this->assertFalse(Hash::check('15032016', $ivy->password));
        $this->assertArrayHasKey('gus@example.com', $this->sentTo('learner-setup/'));
    }

    public function test_a_learner_without_a_guardian_email_gets_no_link(): void
    {
        DB::table('students')->where('id', $this->people['student'])->update(['father_id' => null]);

        $this->actingAs($this->superAdmin())->postJson('/students/'.$this->people['learner'].'/invite-guardians')
            ->assertJson(['error' => true, 'message' => trans('learner_invitation_no_guardian_email')]);
        $this->assertSame([], $this->sentTo('learner-setup/'));
    }

    /** Adds a learner through the panel's Add Student form, with existing parents. */
    private function addLearner(array $parents): User
    {
        $this->actingAs($this->superAdmin())->postJson('/students', $parents + [
            'first_name' => 'Nia', 'last_name' => 'New', 'gender' => 'female', 'dob' => '15-03-2016',
            'class_section_id' => $this->people['class_section'], 'category_id' => DB::table('categories')->value('id'),
            'admission_date' => now()->format('d-m-Y'), 'current_address' => '1 Road', 'permanent_address' => '1 Road',
            'height' => 120, 'weight' => 25, 'parent_guardian_type' => 'parent',
            // The form always sends these, even for existing parents.
            'father_dob' => '03-02-1985', 'mother_dob' => '04-05-1986',
        ])->assertOk()->assertJson(['error' => false]);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return User::role('Student')->where('first_name', 'Nia')->firstOrFail();
    }

    private function secondParent(): int
    {
        $user = User::forceCreate([
            'first_name' => 'Max', 'last_name' => 'Parent', 'email' => 'max@example.com', 'password' => Hash::make('secret123'),
            'gender' => 'Male', 'mobile' => '0123456789', 'status' => 1,
        ]);
        $user->assignRole('Parent');

        return SchoolTable::query('parents')->insertGetId([
            'user_id' => $user->id, 'first_name' => 'Max', 'last_name' => 'Parent', 'gender' => 'Male',
            'email' => 'max@example.com', 'mobile' => '0123456789', 'occupation' => 'Chef', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return list<SentMessage> */
    private function sentMessages(): array
    {
        return Mail::mailer('array')->getSymfonyTransport()->messages()->all();
    }

    /**
     * The first link containing $path in the mail sent to each address.
     *
     * @return array<string, string>
     */
    private function sentTo(string $path): array
    {
        $links = [];
        foreach ($this->sentMessages() as $message) {
            $html = (string) $message->getOriginalMessage()->getHtmlBody();
            if (preg_match('#href="([^"]*'.preg_quote($path, '#').'[^"]*)"#', $html, $match) === 1) {
                foreach ($message->getOriginalMessage()->getTo() as $address) {
                    $links[$address->getAddress()] = html_entity_decode($match[1]);
                }
            }
        }

        return $links;
    }

    private function codeFrom(string $html): string
    {
        $this->assertSame(1, preg_match('/\b([A-Z2-9]{5}-[A-Z2-9]{5})\b/', $html, $match), 'the sheet shows a code');

        return $match[1];
    }
}
