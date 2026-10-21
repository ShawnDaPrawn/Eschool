<?php

namespace Tests\Feature;

use Tests\Concerns\RunsAppServer;
use Tests\TestCase;

/**
 * In production the panel only answers on its own host names (bootstrap/app.php).
 * Checked against a real server, because Laravel skips host checks in tests.
 */
class TrustedHostsTest extends TestCase
{
    use RunsAppServer;

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::appServerRunning()) {
            $this->freshInstall();
        }
    }

    public function test_an_installed_panel_answers_only_on_its_own_host_names(): void
    {
        $this->startAppServer(['TRUSTED_HOSTS' => 'portal.school.test, 10.0.0.5']);
        $get = fn (?string $host) => $this->appClient()->withHeaders($host ? ['Host' => $host] : [])->get('/login')->status();

        $this->assertSame(200, $get(null), 'APP_URL');
        $this->assertSame(400, $get('elsewhere.example'));
        // A school reached under more than one name lists the others in TRUSTED_HOSTS.
        $this->assertSame(200, $get('portal.school.test'));
        $this->assertSame(200, $get('10.0.0.5'));
    }

    public function test_the_install_wizard_answers_on_the_address_the_school_opens_it_on(): void
    {
        $this->startAppServer();
        unlink($this->appServerStoragePath('installed'));

        // Until the wizard's last step writes it, APP_URL is .env.example's placeholder,
        // so the wizard was refused (400) on the school's own domain and couldn't run.
        $this->assertSame(200, $this->appClient()->withHeaders(['Host' => 'school.example'])->get('/install')->status());
    }
}
