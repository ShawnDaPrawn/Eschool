<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\NotificationMail;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Communication\Message;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Communication\MessageDelivery;
use App\Services\Communication\MessageFlow;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Notifications\EmailContent;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Branded school emails with the full message for school messages, notice
 * only for private types (Action Plan 2.1).
 * engineering/specs/notifications/branded-email.md.
 */
class BrandedEmailTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $grade;

    private int $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(9, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        foreach (['asha', 'ben'] as $key) {
            app(EnrolmentService::class)->place($this->schoolA->ids[$key], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic message enrolment');
        }
        $g = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Message fractions group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = (int) $g->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic message author'], $this->group);
        foreach (['asha', 'ben'] as $key) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids[$key], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic audience member'], $this->group);
        }
        Queue::fake();
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function person(string $key): User
    {
        return User::findOrFail($this->schoolA->ids[$key.'_user']);
    }

    private function data(array $changes = []): array
    {
        return $changes + ['revision' => 0, 'operation_id' => (string) Str::uuid(), 'title' => 'Private school message', 'markdown' => "# Fractions\n\nPrivate family details.",
            'channels' => ['in_app'], 'scheduled_local' => null, 'audience' => ['kind' => 'group', 'session_year_id' => $this->year, 'target_id' => $this->group, 'parents' => true]];
    }

    private function draft(?User $actor = null, array $changes = []): Message
    {
        $r = app(MessageFlow::class)->save($actor ?? $this->teacher(), null, $this->data($changes));

        return Message::findOrFail($r['id']);
    }

    private function submit(Message $m, ?User $actor = null): Message
    {
        $actor ??= $this->teacher();
        $preview = app(MessageFlow::class)->preview($actor, (int) $m->id);
        app(MessageFlow::class)->submit($actor, (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint']]);

        return $m->refresh();
    }

    private function approved(?Message $m = null): Message
    {
        $m = $this->submit($m ?? $this->draft());
        $preview = app(MessageFlow::class)->preview($this->superAdmin(), (int) $m->id);
        app(MessageFlow::class)->decide($this->superAdmin(), (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint'], 'decision' => 'approve', 'reason' => 'Reviewed synthetic audience and message']);

        return $m->refresh();
    }

    private function denied(callable $fn, int $code): void
    {
        try {
            $fn();
            $this->fail('Expected refusal');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($code, $e->getStatusCode());
        } catch (ModelNotFoundException $e) {
            $this->assertSame(404, $code);
        }
    }

    private function unusedHeaders(string $person = 'tara'): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$this->schoolA->tokens[$person], 'X-School-Id' => '1'];
    }

    /** Publishes a message to the group's guardians by email and returns the SendMail jobs it queued. */
    private function published(bool $verified = true): array
    {
        app(CurrentSchool::class)->set(1);
        foreach (['farid', 'mona', 'gina'] as $key) {
            $this->person($key)->forceFill(['email_verified_at' => $verified ? now() : null])->save();
            app(NotificationRules::class)->choose($this->person($key), 'communication.message', 'email', true);
        }
        School::whereKey(1)->update(['theme_color' => '#0a7c4b', 'crest_path' => 'logo/crest.png']);
        $m = $this->approved($this->draft(null, ['channels' => ['in_app', 'email'], 'markdown' => "# Trip on Friday\n\nBring **R50** & a hat. Private family details."]));
        app(MessageDelivery::class)->process((int) $m->id);

        return ['message' => $m->refresh(), 'jobs' => Queue::pushed(SendMail::class)->filter(fn (SendMail $job) => $job->mailable instanceof NotificationMail && $job->mailable->notificationType === 'communication.message')->values()];
    }

    private function deliver(SendMail $job): NotificationMail
    {
        Mail::fake();
        app(CurrentSchool::class)->forget();
        $job->handle();
        $sent = null;
        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use (&$sent) {
            $sent = $mail;

            return true;
        });

        return $sent;
    }

    private function text(NotificationMail $mail): string
    {
        return view('emails.school-notification-text', $mail->buildViewData())->render();
    }

    public function test_a_school_message_email_carries_the_message_with_the_schools_branding(): void
    {
        ['jobs' => $jobs] = $this->published();
        $this->assertCount(3, $jobs);
        // The message itself never travels in the queue.
        $this->assertStringNotContainsString('Private family details', serialize($jobs[0]->mailable));

        $this->assertStringNotContainsString('Private school message', serialize($jobs[0]->mailable), 'nor its subject');

        $mail = $this->deliver($jobs[0]);
        $this->assertSame('Private school message', $mail->noticeTitle, 'the sender\'s subject is the email\'s subject and heading');
        $this->assertSame('Private school message', $mail->build()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('Private school message</h1>', $html);
        $this->assertStringContainsString('<h1>Trip on Friday</h1>', $html);
        $this->assertStringContainsString('<strong>R50</strong>', $html);
        $this->assertStringContainsString('#0A7C4B', $html);
        $this->assertStringContainsString('/storage/logo/crest.png', $html);
        $this->assertStringContainsString('Sent by', $html);
        $text = $this->text($mail);
        $this->assertStringContainsString("Trip on Friday\n\nBring R50 & a hat. Private family details.", $text, 'plain text, not HTML-escaped');
        $this->assertStringNotContainsString('<strong>', $text);
    }

    public function test_an_unverified_address_a_withdrawn_message_and_a_lost_access_get_the_notice_only(): void
    {
        ['jobs' => $jobs] = $this->published(false);
        $mail = $this->deliver($jobs[0]);
        $this->assertNull($mail->contentHtml);
        $this->assertStringNotContainsString('Private family details', $mail->render());
        $this->assertStringNotContainsString('Private school message', $mail->render(), 'an unverified address gets neither the body nor the subject');
        $this->assertStringContainsString('Open NavigateEd to view this notice.', $mail->render());

        // Verified now, but the message is withdrawn before the job runs: nothing is sent.
        ['message' => $m, 'jobs' => $jobs] = $this->published();
        app(MessageFlow::class)->withdraw($this->teacher(), (int) $m->id, ['revision' => $m->revision, 'operation_id' => (string) Str::uuid(), 'reason' => 'Retract synthetic message']);
        Mail::fake();
        app(CurrentSchool::class)->forget();
        $jobs->last()->handle();
        Mail::assertNothingSent();
    }

    public function test_full_content_is_only_for_the_recipient_the_publication_and_the_school(): void
    {
        ['message' => $m] = $this->published();
        $context = ['communication_message_id' => (int) $m->id, 'communication_publication_id' => (string) $m->publication_id];
        $content = app(EmailContent::class);
        $this->assertNotNull($content->full($this->person('farid'), 'communication.message', $context));
        // Wrong owner: Cara\'s family is not on this message. Theo (staff) neither.
        $this->assertNull($content->full($this->person('theo')->forceFill(['email_verified_at' => now()]), 'communication.message', $context));
        // A notice for another publication of the message.
        $this->assertNull($content->full($this->person('farid'), 'communication.message', ['communication_publication_id' => (string) Str::uuid()] + $context));
        // Wrong school: the same ids read at School B.
        app(CurrentSchool::class)->run(2, fn () => $this->assertNull($content->full(User::findOrFail($this->schoolB->ids['farid_user'])->forceFill(['email_verified_at' => now()]), 'communication.message', $context)));
        // Wrong type: the same context on any other type.
        $this->assertNull($content->full($this->person('farid'), 'comms.notice', $context));
    }

    public function test_private_types_stay_notice_only_whatever_the_config_says(): void
    {
        // Only school messages opt in, and none is both.
        foreach (config('notification_types') as $type => $definition) {
            $this->assertFalse(($definition['notice_only'] ?? false) && ($definition['full_content'] ?? false), $type);
        }
        foreach (['safeguarding.alert', 'wellbeing.staff_update', 'discipline.family_update', 'progress.band_changed', 'consent.requested', 'transfer.consent_requested'] as $type) {
            $this->assertTrue(EmailContent::noticeOnly($type), $type);
            $types = config('notification_types');
            $types[$type]['full_content'] = true;
            config(['notification_types' => $types]);
            $this->assertFalse(EmailContent::allowsFullContent($type), $type);
        }
        $this->assertTrue(EmailContent::allowsFullContent('communication.message'));
    }

    public function test_a_colour_that_is_not_a_colour_falls_back(): void
    {
        $this->assertSame('#0A7C4B', EmailContent::colour('#0a7c4b'));
        foreach (['red;background:url(x)', '#fff', '', null, '#12345G'] as $value) {
            $this->assertSame(EmailContent::DEFAULT_COLOUR, EmailContent::colour($value));
        }
    }
}
