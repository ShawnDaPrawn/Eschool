<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\IncidentThread;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class PublicReportingPageTest extends TestCase
{
    use UsesTwoSchools;

    private array $category;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('wellbeing', true, $this->superAdmin());
        $this->category = app(WellbeingSettings::class)->saveCategory($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'name' => 'Safety concern', 'emergency_guidance' => 'Speak to a trusted adult now.', 'active' => true]);
        $this->path = '/school/'.School::findOrFail(1)->slug.'/report';
    }

    private function report(string $secret, array $extra = []): array
    {
        return $extra + ['secret' => $secret, 'anonymous' => '1', 'category_id' => $this->category['id'], 'body' => 'Synthetic private report.', 'urgency' => 'urgent'];
    }

    public function test_public_html_report_and_replies_are_stateless_escaped_and_ignore_signed_in_identity(): void
    {
        $this->actingAs($this->superAdmin(), 'web');
        $form = $this->get($this->path)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'self' 'unsafe-inline'; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        $secret = $form->viewData('secret');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $secret);
        $this->assertSame([], $form->headers->getCookies());
        $this->assertFalse($this->app['request']->hasSession());
        $form->assertSee('Save your private code before sending')->assertSee('This form is not an emergency response service.');
        $body = '<script>synthetic private concern</script>';
        $saved = $this->post($this->path, $this->report($secret, ['body' => $body]))->assertOk()->assertSee($body)->assertDontSee($body, false);
        $this->assertSame([], $saved->headers->getCookies());
        $this->assertNull(IncidentReport::firstOrFail()->reporter_user_id);
        $this->assertStringNotContainsString($body, DB::table('incident_reports')->value('body'));
        $this->assertStringNotContainsString($secret, json_encode((array) DB::table('incident_reports')->first()));
        $reply = ['secret' => $secret, 'operation_id' => (string) Str::uuid(), 'body' => 'Synthetic reporter follow-up.'];
        $this->post($this->path.'/replies', $reply)->assertOk()->assertSee($reply['body']);
        $this->post($this->path.'/replies', $reply)->assertOk();
        $this->assertSame(1, IncidentThread::count());
        IncidentThread::create(['incident_report_id' => IncidentReport::firstOrFail()->id, 'author_type' => 'staff', 'staff_user_id' => $this->superAdmin()->id, 'body' => 'INTERNAL PRIVATE TRIAGE', 'internal' => true, 'operation_id' => (string) Str::uuid()]);
        $this->post($this->path.'/follow-up', ['secret' => $secret])->assertOk()->assertDontSee('INTERNAL PRIVATE TRIAGE');
    }

    public function test_public_labels_use_english_without_changing_the_callers_locale(): void
    {
        app()->setLocale('ar');
        $this->get($this->path)->assertOk()->assertSee('<html lang="en">', false)->assertSee('Make an anonymous report');
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_validation_and_conflict_return_private_drafts_without_session_flash(): void
    {
        $secret = bin2hex(random_bytes(32));
        $draft = $this->report($secret, ['category_id' => ['malformed'], 'body' => 'PRIVATE RESPONSE DRAFT']);
        $response = $this->post($this->path, $draft)->assertUnprocessable()->assertSee('PRIVATE RESPONSE DRAFT')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame([], $response->headers->getCookies());
        $this->assertFalse($this->app['request']->hasSession());
        $this->assertSame(0, IncidentReport::count());
        $this->post($this->path, $this->report($secret))->assertOk();
        $this->post($this->path, $this->report($secret, ['body' => 'CHANGED PRIVATE DRAFT']))->assertConflict()->assertSee('CHANGED PRIVATE DRAFT');
        $this->assertSame('Synthetic private report.', IncidentReport::firstOrFail()->body);
        $reply = ['secret' => $secret, 'operation_id' => (string) Str::uuid(), 'body' => 'Original reply'];
        $this->post($this->path.'/replies', $reply)->assertOk();
        $this->post($this->path.'/replies', array_replace($reply, ['body' => 'CHANGED REPLY DRAFT']))->assertConflict()->assertSee('CHANGED REPLY DRAFT');
        $this->assertSame(1, IncidentThread::count());
        $this->assertFalse($this->app['request']->hasSession());
    }

    public function test_wrong_school_code_identified_report_and_disabled_module_do_not_reveal_private_content(): void
    {
        $secret = bin2hex(random_bytes(32));
        $this->post($this->path, $this->report($secret))->assertOk();
        $this->post('/school/baobab-high/report/follow-up', ['secret' => $secret])->assertNotFound()->assertDontSee('Synthetic private report.');
        $this->post($this->path.'/follow-up?secret='.$secret, [])->assertUnprocessable()->assertDontSee($secret);
        app(IncidentReporting::class)->identify($this->superAdmin(), $secret, ['revision' => 1, 'identify_consent' => true]);
        $response = $this->post($this->path.'/follow-up', ['secret' => $secret])->assertForbidden()->assertDontSee('Synthetic private report.');
        $this->assertSame([], $response->headers->getCookies());
        app(ModuleGate::class)->change('wellbeing', false, $this->superAdmin());
        $this->get($this->path)->assertNotFound()->assertSee('If someone is in immediate danger');
    }
}
