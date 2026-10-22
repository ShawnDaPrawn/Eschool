<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Security\ContentSecurityPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The panel and the public pages carry a report-only content security
 * policy; POST /csp-report logs what it would have blocked, ids only.
 */
class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_panel_and_public_pages_carry_the_report_only_policy(): void
    {
        foreach (['/login', '/learner-setup'] as $path) {
            $this->get($path)->assertOk()
                ->assertHeader(ContentSecurityPolicy::HEADER, ContentSecurityPolicy::header())
                ->assertHeader('Reporting-Endpoints', 'csp="/csp-report"')
                ->assertHeaderMissing('Content-Security-Policy');
        }

        $this->actingAs($this->superAdmin())->get('/home')->assertOk()
            ->assertHeader(ContentSecurityPolicy::HEADER, ContentSecurityPolicy::header());
    }

    public function test_the_policy_allows_what_the_pages_load_and_nothing_wider(): void
    {
        $policy = ContentSecurityPolicy::header();

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString('report-uri /csp-report', $policy);
        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertDoesNotMatchRegularExpression('/(script|style|default|connect)-src[^;]*\s\*(\s|;|$)/', $policy);
    }

    public function test_api_answers_do_not_carry_it(): void
    {
        $this->getJson('/api/settings?type=app_settings')->assertOk()->assertHeaderMissing(ContentSecurityPolicy::HEADER);
    }

    public function test_a_report_is_logged_with_ids_only(): void
    {
        Log::spy();
        $this->actingAs($this->superAdmin());

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode(['csp-report' => [
            'document-uri' => 'https://admin.example/students/12?search=Thandi%20Mokoena',
            'violated-directive' => 'script-src-elem',
            'effective-directive' => 'script-src-elem',
            'blocked-uri' => 'https://evil.example/x.js?token=secret',
            'source-file' => 'https://admin.example/assets/js/custom/custom.js?v=1',
            'line-number' => 42,
            'script-sample' => 'Thandi Mokoena',
            'disposition' => 'report',
        ]]))->assertNoContent();

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) {
            return $message === 'Content security policy violation'
                && $context === [
                    'directive' => 'script-src-elem',
                    'blocked' => 'https://evil.example/x.js',
                    'page' => '/students/12',
                    'source' => 'https://admin.example/assets/js/custom/custom.js',
                    'line' => 42,
                    'disposition' => 'report',
                    'user_id' => $this->superAdmin()->id,
                ];
        });
    }

    public function test_reporting_api_reports_and_inline_keywords_are_read(): void
    {
        Log::spy();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/reports+json'], json_encode([
            ['type' => 'csp-violation', 'body' => ['documentURL' => 'https://admin.example/home', 'effectiveDirective' => 'style-src-attr', 'blockedURL' => 'inline', 'lineNumber' => 3]],
            ['type' => 'deprecation', 'body' => ['id' => 'x']],
        ]))->assertNoContent();

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context['blocked'] === 'inline'
            && $context['directive'] === 'style-src-attr' && $context['page'] === '/home' && $context['user_id'] === null);
    }

    public function test_reports_need_no_csrf_token_and_rubbish_is_ignored(): void
    {
        Log::spy();

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], 'not json')->assertNoContent();
        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode(['hello' => 'world']))->assertNoContent();

        Log::shouldNotHaveReceived('warning');
    }

    public function test_reports_are_rate_limited(): void
    {
        $report = json_encode(['csp-report' => ['effective-directive' => 'img-src', 'blocked-uri' => 'data']]);
        for ($i = 0; $i < 30; $i++) {
            $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $report)->assertNoContent();
        }

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $report)->assertTooManyRequests();
    }
}
