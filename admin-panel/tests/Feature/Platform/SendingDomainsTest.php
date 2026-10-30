<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Mail\Platform\TemplateMail;
use App\Models\Platform\SchoolSendingDomain;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Mail\SendingDomains;
use App\Services\Platform\Mail\SesIdentities;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Saas\PlatformOnboarding;
use App\Services\Platform\Tenancy\CurrentSchool;
use Aws\CommandInterface;
use Aws\Result;
use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use GuzzleHttp\Promise\Create;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\Email;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * School sending domains (Action Plan 2.2): connect, DNS records, check,
 * scheduled poll, disconnect, the From address of school notices, and who may
 * do what. SES is an SesV2Client with a fake handler: no test calls AWS.
 * engineering/specs/platform/sending-domains.md.
 */
class SendingDomainsTest extends TestCase
{
    use UsesTwoSchools;

    /** @var array<string, array{verified: bool, dkim: string, mail_from: string}> identities the fake SES holds */
    private array $ses = [];

    /** @var list<array{0: string, 1: array<string, mixed>}> */
    private array $commands = [];

    private bool $sesDown = false;

    private bool $clientBuilt = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        config([
            'mail.sending_domains.enabled' => true,
            'mail.default' => 'array',
            'mail.from.address' => 'noreply@navigateed.co.za',
            'mail.from.name' => 'NavigateEd',
            'services.ses.region' => 'af-south-1',
            'app.url' => 'https://admin.navigateed.co.za',
            'identity.portal_url' => 'https://app.navigateed.co.za',
        ]);
        $this->app->instance(SesIdentities::class, new SesIdentities(function (): SesV2Client {
            $this->clientBuilt = true;

            return new SesV2Client([
                'version' => '2019-09-27', 'region' => 'af-south-1', 'credentials' => ['key' => 'test', 'secret' => 'test'], 'retries' => 0,
                'handler' => fn (CommandInterface $command) => $this->answer($command),
            ]);
        }));
    }

    private function answer(CommandInterface $command): mixed
    {
        $name = $command->getName();
        $args = $command->toArray();
        $this->commands[] = [$name, $args];
        $domain = (string) ($args['EmailIdentity'] ?? '');
        $fail = fn (string $code) => Create::rejectionFor(new SesV2Exception($code, $command, ['code' => $code]));
        if ($this->sesDown) {
            return $fail('ServiceUnavailable');
        }
        $tokens = array_map(fn (string $n) => substr(hash('sha256', $domain.$n), 0, 32), ['1', '2', '3']);

        return match ($name) {
            'CreateEmailIdentity' => isset($this->ses[$domain]) ? $fail('AlreadyExistsException') : Create::promiseFor(new Result([
                'IdentityType' => 'DOMAIN', 'VerifiedForSendingStatus' => false,
                'DkimAttributes' => ['SigningEnabled' => true, 'Status' => ($this->ses[$domain] = ['verified' => false, 'dkim' => 'PENDING', 'mail_from' => 'PENDING'])['dkim'], 'Tokens' => $tokens],
            ])),
            'PutEmailIdentityMailFromAttributes' => Create::promiseFor(new Result([])),
            'GetEmailIdentity' => ! isset($this->ses[$domain]) ? $fail('NotFoundException') : Create::promiseFor(new Result([
                'IdentityType' => 'DOMAIN', 'VerifiedForSendingStatus' => $this->ses[$domain]['verified'],
                'DkimAttributes' => ['SigningEnabled' => true, 'Status' => $this->ses[$domain]['dkim'], 'Tokens' => $tokens],
                'MailFromAttributes' => ['MailFromDomain' => 'bounce.'.$domain, 'MailFromDomainStatus' => $this->ses[$domain]['mail_from'], 'BehaviorOnMxFailure' => 'USE_DEFAULT_VALUE'],
                'VerificationInfo' => $this->ses[$domain]['verified'] ? [] : ['ErrorType' => 'HOST_NOT_FOUND'],
            ])),
            'DeleteEmailIdentity' => ! isset($this->ses[$domain]) ? $fail('NotFoundException') : (function () use ($domain) {
                unset($this->ses[$domain]);

                return Create::promiseFor(new Result([]));
            })(),
            default => $fail('UnexpectedCommand'),
        };
    }

    /** SES has seen the DNS records. */
    private function sesVerifies(string $domain): void
    {
        $this->ses[$domain] = ['verified' => true, 'dkim' => 'SUCCESS', 'mail_from' => 'SUCCESS'];
    }

    /** @return list<string> */
    private function sent(string $name): array
    {
        return array_values(array_map(fn (array $c) => (string) ($c[1]['EmailIdentity'] ?? ''), array_filter($this->commands, fn (array $c) => $c[0] === $name)));
    }

    private function schoolBAdmin(): User
    {
        return app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): User {
            $user = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'principal.baobab@example.com', 'mobile' => '', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $user->assignRole('Super Admin');
            app(MembershipService::class)->grant($user, self::SCHOOL_B_ID, 'Super Admin');

            return $user;
        });
    }

    /** Connects a domain for School A through the page and returns its row. */
    private function connectA(string $domain = 'jacaranda-school.co.za'): SchoolSendingDomain
    {
        $this->actingAs($this->superAdmin())->post('/sending-domain', ['domain' => $domain])->assertRedirect('/sending-domain')->assertSessionHasNoErrors();

        return app(CurrentSchool::class)->run(1, fn () => SchoolSendingDomain::query()->sole());
    }

    /** Sends School A's Farid an urgent notice (email is mandatory) and returns the mail that left. */
    private function noticeToFarid(): Email
    {
        $before = count($this->messages());
        app(CurrentSchool::class)->run(1, fn () => app(Notifier::class)->send(User::query()->findOrFail($this->schoolA->ids['farid_user']), 'comms.urgent', 'Gate closed', 'The north gate is closed today.'));
        $messages = $this->messages();
        $this->assertCount($before + 1, $messages, 'One notice email was sent');

        return end($messages);
    }

    /** @return list<Email> */
    private function messages(): array
    {
        return array_values(array_map(fn ($sent) => $sent->getOriginalMessage(), app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all()));
    }

    public function test_the_super_admin_connects_a_domain_adds_the_records_checks_it_and_disconnects(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/sending-domain')->assertOk()->assertSee('Connect your domain');

        $row = $this->connectA(' Jacaranda-School.CO.za. ');
        $this->assertSame('jacaranda-school.co.za', $row->domain);
        $this->assertSame('bounce.jacaranda-school.co.za', $row->mail_from_domain);
        $this->assertSame(SchoolSendingDomain::PENDING, $row->status);
        $this->assertCount(3, $row->dkim_tokens);
        $this->assertSame((int) $admin->id, (int) $row->connected_by);
        [$create, $mailFrom] = [$this->commands[0], $this->commands[1]];
        $this->assertSame(['CreateEmailIdentity', 'jacaranda-school.co.za', 'RSA_2048_BIT', '1'], [$create[0], $create[1]['EmailIdentity'], $create[1]['DkimSigningAttributes']['NextSigningKeyLength'], $create[1]['Tags'][0]['Value']]);
        $this->assertSame(['PutEmailIdentityMailFromAttributes', 'bounce.jacaranda-school.co.za', 'USE_DEFAULT_VALUE'], [$mailFrom[0], $mailFrom[1]['MailFromDomain'], $mailFrom[1]['BehaviorOnMxFailure']]);

        $page = $this->get('/sending-domain')->assertOk()->assertSee('Waiting for the DNS records')
            ->assertSee('The NavigateEd address, until the domain is verified.')
            ->assertSee('10 feedback-smtp.af-south-1.amazonses.com')->assertSee('"v=spf1 include:amazonses.com ~all"')
            ->assertSee('_dmarc.jacaranda-school.co.za')->assertSee('"v=DMARC1; p=none;"');
        foreach ($row->dkim_tokens as $token) {
            $page->assertSee($token.'._domainkey.jacaranda-school.co.za')->assertSee($token.'.dkim.amazonses.com');
        }

        // Not there yet: "Check now" says so and records the reason.
        $this->post('/sending-domain/'.$row->id.'/check')->assertRedirect('/sending-domain')->assertSessionHas('success', 'jacaranda-school.co.za is not verified yet.');
        $this->assertSame(['pending', 'host_not_found'], [$row->fresh()->status, $row->fresh()->last_error]);
        $this->get('/sending-domain')->assertSee('The records were not found yet.');

        $this->sesVerifies('jacaranda-school.co.za');
        $this->post('/sending-domain/'.$row->id.'/check')->assertSessionHas('success', 'jacaranda-school.co.za is verified. Your school\'s notices are now sent from it.');
        $fresh = $row->fresh();
        $this->assertSame([SchoolSendingDomain::VERIFIED, 'SUCCESS', 'SUCCESS', null], [$fresh->status, $fresh->dkim_status, $fresh->mail_from_status, $fresh->last_error]);
        $this->assertNotNull($fresh->verified_at);
        $this->get('/sending-domain')->assertSee('Verified')->assertSee('notices@jacaranda-school.co.za');

        $this->delete('/sending-domain/'.$row->id)->assertRedirect('/sending-domain')->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('school_sending_domains')->count());
        $this->assertSame(['jacaranda-school.co.za'], $this->sent('DeleteEmailIdentity'));
        $this->assertArrayNotHasKey('jacaranda-school.co.za', $this->ses);

        $audit = DB::table('audit_log')->where('action', 'like', 'sending_domain.%')->orderBy('id')->get();
        $this->assertSame(['sending_domain.connected', 'sending_domain.verified', 'sending_domain.disconnected'], $audit->pluck('action')->all());
        $this->assertSame([1, 1, 1], $audit->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([(int) $admin->id], $audit->pluck('actor_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
    }

    public function test_the_scheduled_poll_verifies_waiting_domains_inside_each_school(): void
    {
        $row = $this->connectA();
        $this->actingAs($this->schoolBAdmin())->post('/sending-domain', ['domain' => 'baobab-high.co.za'])->assertSessionHasNoErrors();
        $this->sesVerifies('jacaranda-school.co.za');
        $this->commands = [];
        // The scheduler runs signed in as nobody.
        $this->app['auth']->forgetGuards();

        // Just connected: not due yet.
        $this->artisan('mail:check-sending-domains')->assertSuccessful();
        $this->assertSame([], $this->sent('GetEmailIdentity'));

        $this->travel(11)->minutes();
        $this->artisan('mail:check-sending-domains')->expectsOutput('Checked 2 sending domains.')->assertSuccessful();
        $this->assertSame(['jacaranda-school.co.za', 'baobab-high.co.za'], $this->sent('GetEmailIdentity'));
        $this->assertSame(SchoolSendingDomain::VERIFIED, $row->fresh()->status);
        $this->assertSame(SchoolSendingDomain::PENDING, app(CurrentSchool::class)->run(2, fn () => SchoolSendingDomain::query()->sole()->status));
        $verified = DB::table('audit_log')->where('action', 'sending_domain.verified')->sole();
        $this->assertSame([1, null], [(int) $verified->school_id, $verified->actor_id]);

        // A verified domain is checked again a day later, not every run.
        $this->commands = [];
        $this->travel(11)->minutes();
        $this->artisan('mail:check-sending-domains')->assertSuccessful();
        $this->assertSame(['baobab-high.co.za'], $this->sent('GetEmailIdentity'));

        // SES stops verifying it: notices fall back and the change is audited.
        $this->ses['jacaranda-school.co.za'] = ['verified' => false, 'dkim' => 'FAILED', 'mail_from' => 'FAILED'];
        $this->travel(1)->days();
        $this->artisan('mail:check-sending-domains')->assertSuccessful();
        $this->assertSame([SchoolSendingDomain::FAILED, 'dkim_failed', null], [$row->fresh()->status, $row->fresh()->last_error, $row->fresh()->verified_at]);
        $this->assertTrue(DB::table('audit_log')->where('action', 'sending_domain.unverified')->where('school_id', 1)->exists());
    }

    public function test_school_notices_use_the_school_domain_only_once_it_is_verified(): void
    {
        // School B's verified domain is never School A's.
        $this->actingAs($this->schoolBAdmin())->post('/sending-domain', ['domain' => 'baobab-high.co.za'])->assertSessionHasNoErrors();
        $this->sesVerifies('baobab-high.co.za');
        app(CurrentSchool::class)->run(2, fn () => app(SendingDomains::class)->refresh(SchoolSendingDomain::query()->sole()));

        // The platform From address as today (the mail settings in config at send time).
        $platform = $this->noticeToFarid()->getFrom()[0]->getAddress();
        $this->assertSame(config('mail.from.address'), $platform);
        $this->assertStringNotContainsString('baobab', $platform);

        $row = $this->connectA();
        $this->assertSame($platform, $this->noticeToFarid()->getFrom()[0]->getAddress());

        $this->sesVerifies('jacaranda-school.co.za');
        $this->post('/sending-domain/'.$row->id.'/check');
        $mail = $this->noticeToFarid();
        $schoolName = (string) app(CurrentSchool::class)->run(1, fn () => app(CurrentSchool::class)->school()->name);
        $this->assertSame(['notices@jacaranda-school.co.za', $schoolName], [$mail->getFrom()[0]->getAddress(), $mail->getFrom()[0]->getName()]);
        $this->assertCount(1, $mail->getFrom());

        // Account mail keeps the platform address.
        $before = count($this->messages());
        app(CurrentSchool::class)->run(1, fn () => MailService::queue(TemplateMail::parentWelcome([
            'subject' => 'Welcome', 'email' => 'father@example.org', 'name' => 'Farid', 'username' => 'father@example.org', 'password' => 'Synthetic-1',
            'child_name' => 'Asha', 'child_grnumber' => '20261', 'child_password' => 'Synthetic-2', 'type' => 'application_accept', 'class_name' => 'Grade 5 - A',
            'school_name' => 'Jacaranda', 'school_email' => 'school@example.org', 'school_contact' => '012 345 6789', 'android_app' => '', 'ios_app' => '', 'url' => 'http://localhost',
        ])));
        $messages = $this->messages();
        $this->assertCount($before + 1, $messages);
        $this->assertSame($platform, end($messages)->getFrom()[0]->getAddress());

        // Switched off for the platform: the platform address again.
        config(['mail.sending_domains.enabled' => false]);
        $this->assertSame($platform, $this->noticeToFarid()->getFrom()[0]->getAddress());
    }

    public function test_teachers_and_parents_cannot_see_or_change_the_domain(): void
    {
        $row = $this->connectA();
        $this->commands = [];
        foreach (['tara_user', 'farid_user'] as $person) {
            $this->actingAs(User::query()->findOrFail($this->schoolA->ids[$person]));
            $this->get('/sending-domain')->assertForbidden();
            $this->post('/sending-domain', ['domain' => 'other-school.co.za'])->assertForbidden();
            $this->post('/sending-domain/'.$row->id.'/check')->assertForbidden();
            $this->delete('/sending-domain/'.$row->id)->assertForbidden();
        }
        $this->assertSame(1, DB::table('school_sending_domains')->count());
        $this->assertSame(SchoolSendingDomain::PENDING, $row->fresh()->status);
        $this->assertSame([], $this->commands);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'like', 'sending_domain.%')->count());
    }

    public function test_another_school_cannot_see_check_disconnect_or_claim_the_domain(): void
    {
        $row = $this->connectA('mail.jacaranda-school.co.za');
        $before = $row->fresh()->toArray();
        $this->commands = [];
        // A new browser: School A's flashed confirmation is not School B's.
        $this->flushSession();
        $this->actingAs($this->schoolBAdmin());

        $this->get('/sending-domain')->assertOk()->assertDontSee('jacaranda-school')->assertSee('Connect your domain');
        $this->post('/sending-domain/'.$row->id.'/check')->assertNotFound();
        $this->delete('/sending-domain/'.$row->id)->assertNotFound();
        // The same domain, its parent and a subdomain all belong to School A.
        foreach (['mail.jacaranda-school.co.za', 'MAIL.Jacaranda-School.co.za', 'jacaranda-school.co.za', 'x.mail.jacaranda-school.co.za'] as $claim) {
            $this->post('/sending-domain', ['domain' => $claim])->assertSessionHasErrors(['domain' => 'This domain cannot be connected. If it is your school\'s, ask Navigate Ed support to check it.']);
        }

        $this->assertSame(1, DB::table('school_sending_domains')->count());
        $this->assertEquals($before, $row->fresh()->toArray());
        $this->assertSame([], $this->commands);
        $this->assertSame(0, DB::table('audit_log')->where('action', 'like', 'sending_domain.%')->where('school_id', self::SCHOOL_B_ID)->count());
    }

    public function test_domains_are_validated_strictly(): void
    {
        // More tries than the page's ten a minute.
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->actingAs($this->superAdmin());
        $cases = [
            '10.0.0.5' => 'Enter a domain name, not an IP address.',
            '2001:db8::1' => 'Enter a domain name, not an IP address.',
            '[::1]' => 'Enter a domain name, not an IP address.',
            'https://school.co.za' => 'Enter the domain only, for example school.co.za.',
            'office@school.co.za' => 'Enter the domain only, for example school.co.za.',
            'school .co.za' => 'Enter the domain only, for example school.co.za.',
            'school' => 'This is not a valid domain name.',
            'co.za' => 'This is not a valid domain name.',
            '-bad.co.za' => 'This is not a valid domain name.',
            'bad-.co.za' => 'This is not a valid domain name.',
            'under_score.co.za' => 'This is not a valid domain name.',
            'school..co.za' => 'This is not a valid domain name.',
            'school.123' => 'This is not a valid domain name.',
            str_repeat('a', 64).'.co.za' => 'This is not a valid domain name.',
            'navigateed.co.za' => 'This domain belongs to NavigateEd and cannot be used.',
            'schools.navigateed.co.za' => 'This domain belongs to NavigateEd and cannot be used.',
            'amazonses.com' => 'This domain belongs to NavigateEd and cannot be used.',
        ];
        foreach ($cases as $domain => $message) {
            $this->post('/sending-domain', ['domain' => $domain])->assertSessionHasErrors(['domain' => $message]);
        }
        $this->post('/sending-domain', ['domain' => ''])->assertSessionHasErrors('domain');
        $this->post('/sending-domain', ['domain' => str_repeat('a.', 127).'za'])->assertSessionHasErrors('domain');
        $this->assertSame(0, DB::table('school_sending_domains')->count());
        $this->assertSame([], $this->commands);

        // One domain per school.
        $this->connectA();
        $this->post('/sending-domain', ['domain' => 'second-domain.co.za'])->assertSessionHasErrors(['domain' => 'This school already has a domain. Disconnect it before connecting another.']);
        $this->assertSame(1, DB::table('school_sending_domains')->count());
    }

    public function test_an_identity_ses_already_has_or_an_unreachable_ses_leaves_nothing_behind(): void
    {
        $this->ses['old-school.co.za'] = ['verified' => true, 'dkim' => 'SUCCESS', 'mail_from' => 'SUCCESS'];
        $this->actingAs($this->superAdmin())->post('/sending-domain', ['domain' => 'old-school.co.za'])
            ->assertSessionHasErrors(['domain' => 'This domain cannot be connected. If it is your school\'s, ask Navigate Ed support to check it.']);
        $this->assertSame(0, DB::table('school_sending_domains')->count());
        $this->assertArrayHasKey('old-school.co.za', $this->ses);

        $this->sesDown = true;
        $this->post('/sending-domain', ['domain' => 'jacaranda-school.co.za'])->assertSessionHasErrors(['domain' => 'The mail service could not be reached just now. Try again in a few minutes.']);
        $this->assertSame(0, DB::table('school_sending_domains')->count());
        $this->assertSame(0, DB::table('audit_log')->where('action', 'like', 'sending_domain.%')->count());

        // Connected, then SES goes away: the check records it and the domain stays.
        $this->sesDown = false;
        $row = $this->connectA();
        $this->sesDown = true;
        $this->post('/sending-domain/'.$row->id.'/check')->assertSessionHas('success');
        $this->assertSame(['pending', 'ses_unreachable'], [$row->fresh()->status, $row->fresh()->last_error]);
        $this->delete('/sending-domain/'.$row->id)->assertSessionHasErrors('domain');
        $this->assertSame(1, DB::table('school_sending_domains')->count());
    }

    public function test_switched_off_the_page_only_explains_and_no_ses_client_is_built(): void
    {
        config(['mail.sending_domains.enabled' => false]);
        $this->actingAs($this->superAdmin())->get('/sending-domain')->assertOk()->assertSee('Sending from your own domain is not switched on yet.')->assertDontSee('Connect domain');
        $this->post('/sending-domain', ['domain' => 'jacaranda-school.co.za'])->assertSessionHasErrors(['domain' => 'Sending from your own domain is not switched on yet.']);
        $this->artisan('mail:check-sending-domains')->expectsOutput('School sending domains are switched off.')->assertSuccessful();
        $this->assertSame(0, DB::table('school_sending_domains')->count());
        $this->assertStringNotContainsString('notices@', $this->noticeToFarid()->getFrom()[0]->getAddress());
        $this->assertFalse($this->clientBuilt);
        $this->assertSame('later', collect(app(PlatformOnboarding::class)->checklist(1)['steps'])->firstWhere('key', 'sending_domain')['status']);
    }

    public function test_the_hq_onboarding_checklist_follows_the_domain(): void
    {
        $step = fn () => collect(app(PlatformOnboarding::class)->checklist(1)['steps'])->firstWhere('key', 'sending_domain')['status'];
        $this->assertSame('todo', $step());
        $row = $this->connectA();
        $this->assertSame('started', $step());
        $this->sesVerifies('jacaranda-school.co.za');
        $this->post('/sending-domain/'.$row->id.'/check');
        $this->assertSame('done', $step());
        $this->assertSame('todo', collect(app(PlatformOnboarding::class)->checklist(self::SCHOOL_B_ID)['steps'])->firstWhere('key', 'sending_domain')['status']);
    }
}
