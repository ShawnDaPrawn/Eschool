<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Services\SettingsConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Settings edited in the admin panel live in the settings table only, so the
 * app keeps working once config is cached (php artisan config:cache).
 */
class SettingsConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_nothing_outside_config_reads_env(): void
    {
        $calls = [];
        foreach (['app', 'bootstrap', 'database', 'resources/views', 'routes'] as $dir) {
            foreach (File::allFiles(base_path($dir)) as $file) {
                $path = $file->getPathname();
                if ($file->getExtension() !== 'php' || str_contains($path, 'bootstrap/cache')) {
                    continue;
                }
                $code = Str::endsWith($path, '.blade.php')
                    ? preg_replace('/\{\{--.*?--\}\}/s', '', $file->getContents())
                    : $this->withoutComments($file->getContents());
                if (preg_match_all('/(?<![\w$>:])env\s*\(/', $code, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as [, $offset]) {
                        $calls[] = Str::after($path, base_path().'/').':'.(substr_count(substr($code, 0, $offset), "\n") + 1);
                    }
                }
            }
        }

        // Once config is cached, env() returns null everywhere but config/.
        $this->assertSame([], $calls, 'Read these values through config() or schoolSetting() instead');
    }

    public function test_admin_settings_override_the_env_config(): void
    {
        $this->seedLikeInstaller();
        config(['mail.from.name' => config('app.name')]); // MAIL_FROM_NAME="${APP_NAME}", as in .env.example
        foreach ([
            'school_name' => 'Greenfield High',
            'time_zone' => 'Africa/Johannesburg',
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.example.org',
            'mail_port' => '2525',
            'mail_username' => 'mailer',
            'mail_password' => 'p@ss word#1',
            'mail_encryption' => 'ssl',
            'mail_send_from' => 'office@example.org',
        ] as $type => $message) {
            Settings::updateOrInsert(['type' => $type], ['message' => $message]);
        }

        SettingsConfigService::apply();

        $this->assertSame('Greenfield High', config('app.name'));
        $this->assertSame('Greenfield High', config('mail.from.name'));
        $this->assertSame('Africa/Johannesburg', config('app.timezone'));
        $this->assertSame('Africa/Johannesburg', date_default_timezone_get());
        $this->assertSame('mail.example.org', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('p@ss word#1', config('mail.mailers.smtp.password'));
        $this->assertSame('ssl', config('mail.mailers.smtp.encryption'));
        $this->assertSame('office@example.org', config('mail.from.address'));
    }

    public function test_unset_or_invalid_settings_keep_the_env_config(): void
    {
        $this->seedLikeInstaller();
        Settings::where('type', 'time_zone')->update(['message' => 'Not/AZone']);
        Settings::updateOrInsert(['type' => 'mail_host'], ['message' => '']);
        config(['mail.from.name' => 'Front Office']); // a MAIL_FROM_NAME of its own
        $timezone = config('app.timezone');
        $host = config('mail.mailers.smtp.host');

        SettingsConfigService::apply();

        $this->assertSame($timezone, config('app.timezone'));
        $this->assertSame($host, config('mail.mailers.smtp.host'));
        $this->assertSame('Front Office', config('mail.from.name'));
    }

    public function test_saving_mail_settings_no_longer_rewrites_env(): void
    {
        $this->seedLikeInstaller();
        $env = File::get(base_path('.env'));

        $this->actingAs($this->superAdmin())->post('/email-settings', [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.org',
            'mail_port' => '587',
            'mail_username' => 'office',
            'mail_password' => 'secret with spaces # and hash',
            'mail_encryption' => 'tls',
            'mail_send_from' => 'office@example.org',
        ])->assertOk()->assertJson(['error' => false]);

        $this->assertSame($env, File::get(base_path('.env')), '.env was rewritten');
        $this->assertSame('secret with spaces # and hash', schoolSetting('mail_password'));
    }

    private function withoutComments(string $php): string
    {
        $code = '';
        foreach (token_get_all($php) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                // Keep line numbers right.
                $code .= str_repeat("\n", substr_count($token[1], "\n"));
            } else {
                $code .= is_array($token) ? $token[1] : $token;
            }
        }

        return $code;
    }
}
