<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Files\FileNames;
use PHPUnit\Framework\TestCase;

/**
 * The server's sanitizeFileName (FileNames), and that every client file name
 * in app/ goes through it.
 */
class FileNamesTest extends TestCase
{
    public function test_names_cannot_leave_their_folder_or_hide(): void
    {
        $cases = [
            '../../etc/passwd' => '_._etc_passwd',
            '..\\..\\windows\\win.ini' => '_._windows_win.ini',
            '....//evil.png' => '__evil.png',
            '.htaccess' => 'htaccess',
            '  report.pdf. ' => 'report.pdf',
            "bad\x00name\x1F.pdf" => 'bad_name_.pdf',
            'C:\\temp\\x.doc' => 'C__temp_x.doc',
            'a<b>"c"|d?.png' => 'a_b__c__d_.png',
            "Doctor's note #1 (100%).pdf" => 'Doctor_s note _1 (100_).pdf',
            'Maths – Term 1.docx' => 'Maths – Term 1.docx',
            '..' => 'file',
            '' => 'file',
        ];

        foreach ($cases as $raw => $expected) {
            $clean = FileNames::sanitize($raw);
            $this->assertSame($expected, $clean, json_encode($raw));
            $this->assertDoesNotMatchRegularExpression('#[/\\\\\x00-\x1F]|\.\.#', $clean);
        }
    }

    public function test_long_names_keep_their_extension(): void
    {
        $clean = FileNames::sanitize(str_repeat('a', 400).'.pdf');

        $this->assertSame(FileNames::MAX_LENGTH, mb_strlen($clean));
        $this->assertStringEndsWith('.pdf', $clean);
    }

    public function test_unusable_names_are_refused(): void
    {
        foreach (['.', '..', ' . ', "photo\x01.png", "x\x7F.pdf"] as $name) {
            $this->assertTrue(FileNames::isRefused($name), json_encode($name));
        }
        foreach (['photo.png', 'a..b.pdf', '.env.txt'] as $name) {
            $this->assertFalse(FileNames::isRefused($name), $name);
        }
    }

    public function test_every_client_file_name_in_app_goes_through_the_sanitiser(): void
    {
        $allowed = [
            'Services/Platform/Files/FileNames.php',
            // Reads the raw name to refuse dangerous ones before anything stores them.
            'Http/Middleware/RejectDangerousUploads.php',
        ];
        $root = dirname(__DIR__, 2).'/app/';
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root));
            $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', (string) file_get_contents($file->getPathname()));
            if (! in_array($relative, $allowed, true) && str_contains((string) $code, 'getClientOriginalName(')) {
                $offenders[] = $relative;
            }
        }

        $this->assertSame([], $offenders, 'Use FileNames::forUpload($file) instead of getClientOriginalName().');
    }
}
