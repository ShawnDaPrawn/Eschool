<?php

namespace Tests\Feature;

use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

/**
 * The web System Update replaces the app's code with an uploaded zip. Where it
 * is turned off (by default on Cloud Run) it must not even show the form.
 */
class SystemUpdateSwitchTest extends TestCase
{
    use RefreshDatabase;

    private const MARKER = 'storage/app/system-update-switch-marker.txt';

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/eschool-update-switch-'.uniqid();
        File::ensureDirectoryExists($this->workDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);
        File::delete(base_path(self::MARKER));
        File::deleteDirectory(base_path('update'));
        parent::tearDown();
    }

    public function test_updates_are_off_by_default_on_cloud_run(): void
    {
        $this->assertTrue((require config_path('system_update.php'))['enabled']);

        $_SERVER['K_SERVICE'] = 'navigateed-admin';
        try {
            $this->assertFalse((require config_path('system_update.php'))['enabled']);

            $_SERVER['SYSTEM_UPDATE_ENABLED'] = 'true';
            $this->assertTrue((require config_path('system_update.php'))['enabled']);
        } finally {
            unset($_SERVER['K_SERVICE'], $_SERVER['SYSTEM_UPDATE_ENABLED']);
        }
    }

    public function test_a_disabled_update_shows_no_form_and_installs_nothing(): void
    {
        $this->seedLikeInstaller();
        config(['system_update.enabled' => false]);
        $version = Settings::where('type', 'system_version')->value('message');

        $this->actingAs($this->superAdmin())->get('/system-update')
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['message' => 'System updates are turned off on this server. Deploy the new version instead.']);

        $this->post('/system-update', ['file' => $this->updatePackage($version, '99.0.0')])
            ->assertOk()
            ->assertJson(['error' => true, 'message' => 'System updates are turned off on this server. Deploy the new version instead.']);

        $this->assertFileDoesNotExist(base_path(self::MARKER));
        $this->assertDirectoryDoesNotExist(base_path('update'));
        $this->assertSame($version, Settings::where('type', 'system_version')->value('message'));
    }

    /** An update zip in the format SystemUpdateController expects (see SystemUpdateTest). */
    private function updatePackage(string $from, string $to): UploadedFile
    {
        $source = new ZipArchive;
        $source->open($this->workDir.'/source_code.zip', ZipArchive::CREATE);
        $source->addFromString(self::MARKER, 'installed by SystemUpdateSwitchTest');
        $source->close();

        $package = $this->workDir.'/update.zip';
        $zip = new ZipArchive;
        $zip->open($package, ZipArchive::CREATE);
        $zip->addFromString('version_info.php', "<?php\nreturn ['current_version' => '$from', 'update_version' => '$to'];");
        $zip->addFile($this->workDir.'/source_code.zip', 'source_code.zip');
        $zip->close();

        return new UploadedFile($package, 'update.zip', 'application/zip', null, true);
    }
}
