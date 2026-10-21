<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
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

        // A live school (users already exist) stays closed without the marker, but
        // the host check must not answer 400: that used to block the wizard on the
        // school's own domain, while APP_URL was still .env.example's placeholder.
        $closed = $this->appClient()->withHeaders(['Host' => 'school.example'])->get('/install')->status();
        $this->assertSame(404, $closed);

        // An empty database is a school opening the wizard for the first time.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->assertSame(200, $this->appClient()->withHeaders(['Host' => 'school.example'])->get('/install')->status());
    }
}
