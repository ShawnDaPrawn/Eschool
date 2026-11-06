<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingBookText;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Fetches a book's plain text from the configured Project Gutenberg mirror
 * (Action Plan 4.4), on demand and only on the server: never from a learner's
 * device. The first read of a book fetches it once, under a lock, with
 * timeouts, no redirects, a content-type check and a size limit; the cleaned
 * text, its page offsets and the original header (for HQ's rights check) go
 * to private storage, and every later read is served from there. A failure
 * is recorded with a short reason, and the mirror is not asked again for
 * that book until retry_after_seconds have passed.
 */
class ReadingFetcher
{
    public const DISK = 'reading_private';

    public function __construct(private readonly ReadingText $text) {}

    /** The book's cached text, fetching it first if needed. */
    public function ensure(ReadingBook $book, bool $force = false): ReadingBookText
    {
        $row = $this->row($book);
        if (! $force && $this->ready($row)) {
            return $row;
        }
        if (! $force) {
            $this->backOff($row);
        }
        try {
            return Cache::lock('reading.fetch.'.$book->id, 90)->block(35, function () use ($book, $force): ReadingBookText {
                // Another reader may have fetched the book, or failed to, while this one waited for the lock.
                $row = $this->row($book);
                if (! $force && $this->ready($row)) {
                    return $row;
                }
                if (! $force) {
                    $this->backOff($row);
                }

                return $this->fetch($book, $row);
            });
        } catch (LockTimeoutException) {
            throw new ReadingFetchFailed('unreachable');
        }
    }

    /**
     * Drop a book's cached text, pages and header, so the next read fetches it
     * again (when its Gutenberg number changes, the old text is another book).
     */
    public function forget(ReadingBook $book): void
    {
        Cache::lock('reading.fetch.'.$book->id, 90)->block(35, function () use ($book): void {
            $row = ReadingBookText::query()->where('reading_book_id', $book->id)->first();
            if ($row === null) {
                return;
            }
            $old = $row->path;
            $row->fill(['state' => 'missing', 'path' => null, 'sha256' => null, 'bytes' => null, 'page_count' => null, 'word_count' => null, 'removed_lines' => 0,
                'fetched_at' => null, 'last_error' => null, 'last_failed_at' => null])->save();
            if ($old !== null) {
                $base = substr($old, 0, -strlen('-text.txt'));
                Storage::disk(self::DISK)->delete([$old, $base.'-pages.json', $base.'-header.txt']);
            }
        });
    }

    /** After a failure, the mirror is not asked again for this book until the wait is over. */
    private function backOff(ReadingBookText $row): void
    {
        if ($row->last_failed_at !== null && $row->last_failed_at->gt(now()->subSeconds((int) config('reading.retry_after_seconds')))) {
            throw new ReadingFetchFailed((string) ($row->last_error ?? 'unreachable'));
        }
    }

    /** The text of one page (1-based) of a ready book. */
    public function page(ReadingBookText $row, int $page): string
    {
        $offsets = $this->offsets($row);
        abort_unless($page >= 1 && $page <= count($offsets), 404);
        [$start, $length] = $offsets[$page - 1];
        $stream = Storage::disk(self::DISK)->readStream((string) $row->path);
        try {
            fseek($stream, (int) $start);

            return (string) stream_get_contents($stream, (int) $length);
        } finally {
            fclose($stream);
        }
    }

    /** The original header of the file (title, author, translator, edition), for HQ only. */
    public function header(ReadingBookText $row): ?string
    {
        if (! $this->ready($row)) {
            return null;
        }
        $path = $this->sibling($row, 'header');

        return Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->get($path) : null;
    }

    public function ready(ReadingBookText $row): bool
    {
        return $row->state === 'ready' && $row->path !== null && Storage::disk(self::DISK)->exists($row->path);
    }

    private function fetch(ReadingBook $book, ReadingBookText $row): ReadingBookText
    {
        try {
            $clean = $this->text->clean($this->download((int) $book->gutenberg_id));
            $pages = $this->text->pages($clean['text'], (int) config('reading.page_characters'));
            if (count($pages) > (int) config('reading.max_pages')) {
                throw new ReadingFetchFailed('too_many_pages');
            }
            $sha = hash('sha256', $clean['text']);
            $base = 'books/'.$book->id.'/'.$sha;
            $disk = Storage::disk(self::DISK);
            try {
                $disk->put($base.'-text.txt', $clean['text']);
                $disk->put($base.'-pages.json', json_encode($pages, JSON_THROW_ON_ERROR));
                $disk->put($base.'-header.txt', $clean['header']);
            } catch (Throwable) {
                throw new ReadingFetchFailed('storage');
            }
            $old = $row->path;
            $row->fill(['state' => 'ready', 'path' => $base.'-text.txt', 'sha256' => $sha, 'bytes' => strlen($clean['text']), 'page_count' => count($pages),
                'word_count' => $clean['word_count'], 'removed_lines' => $clean['removed_lines'], 'fetched_at' => now(), 'last_error' => null, 'last_failed_at' => null])->save();
            if ($old !== null && $old !== $row->path) {
                $previous = substr($old, 0, -strlen('-text.txt'));
                $disk->delete([$old, $previous.'-pages.json', $previous.'-header.txt']);
            }

            return $row;
        } catch (ReadingFetchFailed $failure) {
            $row->fill(['state' => $this->ready($row) ? 'ready' : 'failed', 'last_error' => $failure->reason, 'last_failed_at' => now()])->save();
            throw $failure;
        }
    }

    private function download(int $gutenbergId): string
    {
        $origin = rtrim((string) config('reading.mirror_url'), '/');
        if ($origin === '') {
            throw new ReadingFetchFailed('no_mirror');
        }
        $p = parse_url($origin);
        if (! is_array($p) || ! isset($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment'])
            || ! in_array($p['scheme'] ?? '', config('reading.allow_http') ? ['https', 'http'] : ['https'], true)) {
            throw new ReadingFetchFailed('mirror_invalid');
        }
        $path = ltrim(str_replace('{id}', (string) $gutenbergId, (string) config('reading.text_path')), '/');
        if (! preg_match('#^[A-Za-z0-9._/-]+$#D', $path) || str_contains($path, '..')) {
            throw new ReadingFetchFailed('mirror_invalid');
        }
        $max = (int) config('reading.max_bytes');
        try {
            $response = Http::withHeaders(['Accept' => 'text/plain'])->connectTimeout((int) config('reading.connect_timeout'))->timeout((int) config('reading.timeout'))
                ->withOptions(['allow_redirects' => false, 'stream' => true])->get($origin.'/'.$path);
        } catch (ConnectionException) {
            throw new ReadingFetchFailed('unreachable');
        }
        if (! $response->successful()) {
            throw new ReadingFetchFailed('http_error');
        }
        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if (! in_array($type, (array) config('reading.content_types'), true)) {
            throw new ReadingFetchFailed('wrong_type');
        }
        $declared = $response->header('Content-Length');
        if (is_numeric($declared) && (int) $declared > $max) {
            throw new ReadingFetchFailed('too_large');
        }
        $stream = $response->toPsrResponse()->getBody();
        $body = '';
        try {
            while (! $stream->eof()) {
                $body .= $stream->read(65536);
                if (strlen($body) > $max) {
                    throw new ReadingFetchFailed('too_large');
                }
            }
        } catch (ReadingFetchFailed $failure) {
            throw $failure;
        } catch (Throwable) {
            throw new ReadingFetchFailed('unreachable');
        } finally {
            $stream->close();
        }

        return $body;
    }

    private function row(ReadingBook $book): ReadingBookText
    {
        $row = ReadingBookText::query()->where('reading_book_id', $book->id)->first();
        if ($row !== null) {
            return $row;
        }
        try {
            return ReadingBookText::query()->create(['reading_book_id' => $book->id, 'state' => 'missing']);
        } catch (UniqueConstraintViolationException) {
            return ReadingBookText::query()->where('reading_book_id', $book->id)->firstOrFail();
        }
    }

    /** @return list<array{0: int, 1: int}> */
    private function offsets(ReadingBookText $row): array
    {
        abort_unless($this->ready($row), 503);
        $offsets = json_decode((string) Storage::disk(self::DISK)->get($this->sibling($row, 'pages')), true);
        abort_unless(is_array($offsets), 503);

        return $offsets;
    }

    private function sibling(ReadingBookText $row, string $kind): string
    {
        return substr((string) $row->path, 0, -strlen('-text.txt')).'-'.$kind.($kind === 'pages' ? '.json' : '.txt');
    }
}
