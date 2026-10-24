<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Files\ClamAv;
use RuntimeException;
use Tests\TestCase;

class ClamAvTest extends TestCase
{
    private function scanner(string $reply, ?string $version = null): ClamAv
    {
        return new class($reply, $version ?? 'ClamAV 1.4.3/99999/'.now('UTC')->format('D M j H:i:s Y')) extends ClamAv
        {
            public function __construct(private string $reply, private string $engine) {}

            protected function version(): string
            {
                return $this->engine;
            }

            protected function inspect(string $bytes): string
            {
                return $this->reply;
            }
        };
    }

    public function test_only_an_explicit_clean_result_from_current_definitions_is_accepted(): void
    {
        $this->assertTrue($this->scanner('stream: OK')->scan('file')['clean']);
        $virus = $this->scanner('stream: Eicar-Signature FOUND')->scan('file');
        $this->assertFalse($virus['clean']);
        $this->assertSame('Eicar-Signature', $virus['signature']);
        foreach (['stream: UNKNOWN', 'stream: ERROR', 'stream: size limit exceeded. ERROR', 'OK'] as $reply) {
            try {
                $this->scanner($reply)->scan('file');
                $this->fail('Unexpected scanner response accepted');
            } catch (RuntimeException $e) {
                $this->assertSame('scanner_unavailable', $e->getMessage());
            }
        }
    }

    public function test_outdated_definitions_do_not_certify_a_source_as_clean(): void
    {
        $this->expectExceptionMessage('scanner_definitions_outdated');
        $this->scanner('stream: OK', 'ClamAV 1.4.3/99999/'.now('UTC')->subDays(8)->format('D M j H:i:s Y'))->scan('file');
    }
}
