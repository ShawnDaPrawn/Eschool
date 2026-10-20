<?php

namespace Tests\Feature;

use App\Models\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class SystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const MARKER = 'storage/app/system-update-test-marker.txt';

    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/eschool-update-test-'.uniqid();
        File::ensureDirectoryExists($this->workDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);
        File::delete(base_path(self::MARKER));
        File::deleteDirectory(base_path('update'));
        parent::tearDown();
    }

    public function test_update_package_installs_with_only_the_zip(): void
    {
        $this->seedLikeInstaller();

        $response = $this->actingAs($this->superAdmin())
            ->post('/system-update', ['file' => $this->updatePackage('3.5.0', '3.5.1')]);

        $response->assertOk()->assertJson(['error' => false]);
        $this->assertSame('3.5.1', Settings::where('type', 'system_version')->value('message'));
        $this->assertFileExists(base_path(self::MARKER));
    }

    public function test_system_update_form_only_asks_for_the_zip(): void
    {
        $this->seedLikeInstaller();

        $html = $this->actingAs($this->superAdmin())->get('/system-update')->assertOk()->getContent();

        preg_match('/<form[^>]*id="system-update".*?<\/form>/s', $html, $form);
        $this->assertNotEmpty($form, 'System Update form not found');
        preg_match_all('/<(?:input|select|textarea)[^>]*name="([^"]+)"/', $form[0], $fields);
        $this->assertSame(['_token', 'file'], $fields[1]);
    }

    /**
     * Build an update zip in the format SystemUpdateController expects:
     * version_info.php plus source_code.zip (extracted over the app).
     */
    private function updatePackage(string $from, string $to): UploadedFile
    {
        $source = new ZipArchive;
        $source->open($this->workDir.'/source_code.zip', ZipArchive::CREATE);
        $source->addFromString(self::MARKER, 'installed by SystemUpdateTest');
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
