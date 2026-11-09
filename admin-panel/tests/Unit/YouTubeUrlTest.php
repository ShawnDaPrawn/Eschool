<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\YouTubeUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YouTubeUrlTest extends TestCase
{
    public static function urls(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', true],
            ['https://m.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=20#start', true],
            ['http://youtube.com/watch?vi=dQw4w9WgXcQ', true],
            ['youtu.be/dQw4w9WgXcQ?si=share', true],
            ['https://youtube.com/embed/dQw4w9WgXcQ', true],
            ['https://youtube.com/v/dQw4w9WgXcQ', true],
            ['https://youtube.com/shorts/dQw4w9WgXcQ', true],
            ['https://youtube.com/watch?v=dQw4w9WgXcQ<img src=x onerror=alert(1)>', false],
            ['https://youtu.be/dQw4w9WgXcQ\"><script>alert(1)</script>', false],
            ["https://youtu.be/dQw4w9WgXcQ\n", false],
            ["https://youtu.be/dQw4w9WgXcQ\0", false],
            ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', false],
            ['https://youtube.com@evil.example/watch?v=dQw4w9WgXcQ', false],
            ['javascript:https://youtu.be/dQw4w9WgXcQ', false],
            ['https://youtube.com/watch?v[]=dQw4w9WgXcQ', false],
            ['https://youtu.be/dQw4w9WgXcQextra', false],
            [['https://youtu.be/dQw4w9WgXcQ'], false],
        ];
    }

    #[DataProvider('urls')]
    public function test_only_complete_youtube_video_urls_are_allowed(mixed $url, bool $valid): void
    {
        $this->assertSame($valid, (new YouTubeUrl)->passes('url', $url));
    }
}
