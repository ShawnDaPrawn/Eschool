<?php

namespace Tests\Feature;

use App\Http\Middleware\RejectDangerousUploads;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UploadSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Echo back what a controller would store, through the real api middleware group.
        Route::middleware('api')->post('api/_upload-test', function (Request $request) {
            $content = file_get_contents($request->file('file')->getRealPath());

            return response()->json(['content' => mb_check_encoding($content, 'UTF-8') ? $content : null]);
        });
    }

    public function test_script_files_are_refused(): void
    {
        $uploads = [
            UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('SHELL.PHTML', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('photo.php.jpg', 'not really a jpeg'),
            UploadedFile::fake()->createWithContent('.user.ini', 'auto_prepend_file=x'),
            // Detected from the content, whatever the name says.
            $this->realUpload('notes.txt', "<?php\nsystem(\$_GET['c']);"),
            UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>'),
        ];

        foreach ($uploads as $upload) {
            $this->post('api/_upload-test', ['file' => $upload])
                ->assertStatus(422)
                ->assertJson(['error' => true]);
        }
    }

    public function test_files_nested_in_arrays_are_checked(): void
    {
        $this->post('api/_upload-test', ['files' => [['x' => UploadedFile::fake()->createWithContent('a.php', '<?php')]]])
            ->assertStatus(422);
    }

    public function test_scripts_are_stripped_from_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script>'
            .'<a href="javascript:alert(3)"><rect width="10" height="10"/></a></svg>';

        $content = $this->post('api/_upload-test', ['file' => UploadedFile::fake()->createWithContent('logo.svg', $svg)])
            ->assertOk()
            ->json('content');

        $this->assertStringContainsString('<rect', $content);
        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onload', $content);
        $this->assertStringNotContainsString('javascript:', $content);
    }

    public function test_images_with_too_many_pixels_are_refused_before_decoding(): void
    {
        // A few hundred bytes claiming 10000 x 10000 pixels: decoding it would need ~400 MB.
        $this->post('api/_upload-test', ['file' => $this->realUpload('photo.png', $this->pngHeader(10000, 10000))])
            ->assertStatus(422)
            ->assertJson(['error' => true]);

        $this->post('api/_upload-test', ['file' => $this->realUpload('photo.png', $this->pngHeader(4000, 3000))])->assertOk();
    }

    public function test_oversized_svgs_are_refused_without_parsing(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg">'.str_repeat('<rect width="1" height="1"/>', 80000).'</svg>';
        $this->assertGreaterThan(RejectDangerousUploads::MAX_SVG_BYTES, strlen($svg));

        $this->post('api/_upload-test', ['file' => UploadedFile::fake()->createWithContent('big.svg', $svg)])
            ->assertStatus(422);
    }

    public function test_ordinary_files_are_accepted(): void
    {
        $this->post('api/_upload-test', ['file' => UploadedFile::fake()->createWithContent('homework.txt', 'My answers')])
            ->assertOk()
            ->assertJson(['content' => 'My answers']);

        $this->post('api/_upload-test', ['file' => UploadedFile::fake()->image('photo.jpg')])->assertOk();
    }

    /** A PNG signature and IHDR chunk: enough for getimagesize(), nothing to decode. */
    private function pngHeader(int $width, int $height): string
    {
        $ihdr = 'IHDR'.pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
    }

    /**
     * UploadedFile::fake() reports a MIME type from the file name; a real upload's
     * type comes from its content.
     */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }
}
