<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\NotificationMail;
use App\Models\User;
use App\Services\Platform\Notifications\EmailUnsubscribe;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * One-click unsubscribe from optional notice emails (Action Plan 2.4,
 * RFC 8058). engineering/specs/platform/email-unsubscribe.md.
 */
class EmailUnsubscribeTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    private function parent(): User
    {
        $user = User::findOrFail($this->schoolA->ids['farid_user']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    /** Sends the notice through the real queue job and returns the message the mailer received. */
    private function deliver(User $to, string $type): ?NotificationMail
    {
        app(NotificationRules::class)->forget();
        app(Notifier::class)->send($to, $type, 'Trip forms', 'Bring them back on Friday');
        Mail::fake();
        $job = Queue::pushed(SendMail::class)->last();
        app(CurrentSchool::class)->forget();
        $job->handle();
        $sent = null;
        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use (&$sent) {
            $sent = $mail;

            return true;
        });

        return $sent;
    }

    /** The headers the mail's message callbacks add when it is sent. */
    private function headers(NotificationMail $mail): Headers
    {
        $mail->build();
        $email = new Email;
        foreach ($mail->callbacks as $callback) {
            $callback($email);
        }

        return $email->getHeaders();
    }

    private function emailOn(User $user, string $type): bool
    {
        return app(CurrentSchool::class)->run(1, function () use ($user, $type) {
            app(NotificationRules::class)->forget();

            return app(NotificationRules::class)->allows($user, $type, 'email');
        });
    }

    public function test_an_optional_notice_carries_one_click_unsubscribe_and_the_link_switches_its_email_off(): void
    {
        $parent = $this->parent();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', true);
        $mail = $this->deliver($parent, 'comms.notice');
        $this->assertNotNull($mail->unsubscribeUrl);
        $this->assertStringContainsString('/email/unsubscribe/'.$parent->id.'/1/comms.notice?', $mail->unsubscribeUrl);
        $headers = $this->headers($mail);
        $this->assertSame('<'.$mail->unsubscribeUrl.'>', $headers->get('List-Unsubscribe')?->getBodyAsString());
        $this->assertSame('List-Unsubscribe=One-Click', $headers->get('List-Unsubscribe-Post')?->getBodyAsString());
        $this->assertStringContainsString('Stop these emails', $mail->render());

        // A person opening the link sees what it does and confirms.
        app(CurrentSchool::class)->forget();
        $this->get($mail->unsubscribeUrl)->assertOk()->assertSee('Stop emails about "School notice" from');
        $this->assertTrue($this->emailOn($parent, 'comms.notice'), 'opening the link changes nothing');

        // A mail provider's one-click POST: no session, no CSRF token, the signature is the authority.
        $this->post($mail->unsubscribeUrl, ['List-Unsubscribe' => 'One-Click'])->assertOk()->assertSee('You will no longer get emails about');
        $this->assertFalse($this->emailOn($parent, 'comms.notice'));
        $this->assertTrue(app(CurrentSchool::class)->run(1, fn () => app(NotificationRules::class)->allows($parent, 'comms.notice', 'push')), 'only email is switched off');
        $this->post($mail->unsubscribeUrl)->assertOk()->assertSee('You will no longer get emails about');
        $this->assertSame(1, DB::table('notification_preferences')->where('user_id', $parent->id)->where('type', 'comms.notice')->where('channel', 'email')->where('enabled', false)->count());
    }

    public function test_a_required_notice_never_carries_it_and_cannot_be_switched_off_through_a_link(): void
    {
        $parent = $this->parent();
        $mail = $this->deliver($parent, 'attendance.absence');
        $this->assertNull($mail->unsubscribeUrl);
        $this->assertFalse($this->headers($mail)->has('List-Unsubscribe'));
        $this->assertStringNotContainsString('Stop these emails', $mail->render());

        // A link made by hand for a required notice, even correctly signed, switches nothing off.
        $url = URL::temporarySignedRoute(EmailUnsubscribe::ROUTE, now()->addDay(), ['user' => $parent->id, 'school' => 1, 'type' => 'attendance.absence']);
        app(CurrentSchool::class)->forget();
        $this->get($url)->assertOk()->assertSee('cannot be switched off here');
        $this->post($url)->assertOk()->assertSee('cannot be switched off here');
        $this->assertTrue($this->emailOn($parent, 'attendance.absence'));
        $this->assertSame(0, DB::table('notification_preferences')->where('user_id', $parent->id)->count());
    }

    public function test_a_changed_expired_or_unsigned_link_does_nothing(): void
    {
        $parent = $this->parent();
        app(NotificationRules::class)->choose($parent, 'comms.notice', 'email', true);
        $url = app(CurrentSchool::class)->run(1, fn () => app(EmailUnsubscribe::class)->url($parent, 'comms.notice'));
        app(CurrentSchool::class)->forget();
        $before = DB::table('notification_preferences')->get()->toArray();

        // Someone else's id, another school or another type in a signed link breaks the signature.
        $other = (int) $this->schoolA->ids['gina_user'];
        foreach ([
            str_replace('/unsubscribe/'.$parent->id.'/', '/unsubscribe/'.$other.'/', $url),
            str_replace('/'.$parent->id.'/1/', '/'.$parent->id.'/2/', $url),
            str_replace('comms.notice', 'comms.message', $url),
            strtok($url, '?'),
        ] as $tampered) {
            $this->get($tampered)->assertForbidden();
            $this->post($tampered)->assertForbidden();
        }
        $this->travel(EmailUnsubscribe::DAYS + 1)->days();
        $this->post($url)->assertForbidden();
        $this->assertEquals($before, DB::table('notification_preferences')->get()->toArray());
    }

    public function test_the_link_only_ever_acts_at_the_school_it_was_sent_from(): void
    {
        // Farid is a guardian at School A only: a link for School B is about a school he is not at.
        $parent = $this->parent();
        $url = URL::temporarySignedRoute(EmailUnsubscribe::ROUTE, now()->addDay(), ['user' => $parent->id, 'school' => 2, 'type' => 'comms.notice']);
        app(CurrentSchool::class)->forget();
        $this->post($url)->assertOk()->assertSee('cannot be switched off here');
        $this->assertSame(0, DB::table('notification_preferences')->where('user_id', $parent->id)->count());
        // An unknown person is simply not found.
        $this->post(URL::temporarySignedRoute(EmailUnsubscribe::ROUTE, now()->addDay(), ['user' => 999999, 'school' => 1, 'type' => 'comms.notice']))->assertNotFound();
    }
}
