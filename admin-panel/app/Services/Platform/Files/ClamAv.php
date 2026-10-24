<?php

declare(strict_types=1);

namespace App\Services\Platform\Files;

use Carbon\CarbonImmutable;
use RuntimeException;
use Throwable;

/** Private clamd INSTREAM. No filenames or source bodies enter logs/errors. */
class ClamAv implements UploadScanner
{
    protected function connect()
    {
        $host = (string) config('academic_content.clamav_host');
        $port = (int) config('academic_content.clamav_port');
        if (! preg_match('/^[A-Za-z0-9_.:-]{1,253}$/D', $host) || $port < 1 || $port > 65535) {
            throw new RuntimeException('scanner_unavailable');
        }
        $socket = @stream_socket_client('tcp://'.$host.':'.$port, $errno, $error, 3, STREAM_CLIENT_CONNECT);
        if ($socket === false) {
            throw new RuntimeException('scanner_unavailable');
        }
        stream_set_timeout($socket, (int) config('academic_content.clamav_timeout'));

        return $socket;
    }

    private function write($socket, string $bytes): void
    {
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = fwrite($socket, substr($bytes, $offset));
            if ($written === false || $written === 0 || (stream_get_meta_data($socket)['timed_out'] ?? true)) {
                throw new RuntimeException('scanner_unavailable');
            }
            $offset += $written;
        }
    }

    private function read($socket): string
    {
        $output = '';
        while (! feof($socket) && strlen($output) <= 4096) {
            $chunk = fread($socket, 1024);
            if ($chunk === false || (stream_get_meta_data($socket)['timed_out'] ?? true)) {
                throw new RuntimeException('scanner_unavailable');
            }
            $output .= $chunk;
            if (str_contains($output, "\0")) {
                return substr($output, 0, strpos($output, "\0"));
            }
        }
        throw new RuntimeException('scanner_unavailable');
    }

    protected function version(): string
    {
        $socket = $this->connect();
        try {
            $this->write($socket, "zVERSION\0");

            return $this->read($socket);
        } finally {
            fclose($socket);
        }
    }

    protected function inspect(string $bytes): string
    {
        $socket = $this->connect();
        try {
            $this->write($socket, "zINSTREAM\0");
            for ($offset = 0; $offset < strlen($bytes); $offset += 65536) {
                $chunk = substr($bytes, $offset, 65536);
                $this->write($socket, pack('N', strlen($chunk)).$chunk);
            }
            $this->write($socket, pack('N', 0));

            return $this->read($socket);
        } finally {
            fclose($socket);
        }
    }

    public function scan(string $bytes): array
    {
        if ($bytes === '' || strlen($bytes) > (int) config('academic_content.maximum_source_bytes')) {
            throw new RuntimeException('scanner_source_limit');
        }
        $version = $this->version();
        if (! preg_match('/^ClamAV [0-9][^\r\n]{1,180}\/([0-9]+)\/(.+)$/D', $version, $match)) {
            throw new RuntimeException('scanner_unavailable');
        }
        try {
            $date = CarbonImmutable::parse($match[2], 'UTC');
        } catch (Throwable) {
            throw new RuntimeException('scanner_unavailable');
        }
        if ($date->lt(now('UTC')->subDays(7)) || $date->gt(now('UTC')->addDay())) {
            throw new RuntimeException('scanner_definitions_outdated');
        }
        $result = $this->inspect($bytes);
        if ($result === 'stream: OK') {
            return ['clean' => true, 'engine' => $version, 'signature' => null];
        }
        if (preg_match('/^stream: ([^\x00-\x1f]{1,191}) FOUND$/D', $result, $found)) {
            return ['clean' => false, 'engine' => $version, 'signature' => $found[1]];
        }
        throw new RuntimeException('scanner_unavailable');
    }
}
