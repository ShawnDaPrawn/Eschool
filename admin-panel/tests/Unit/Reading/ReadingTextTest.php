<?php

declare(strict_types=1);

namespace Tests\Unit\Reading;

use App\Services\Reading\ReadingFetchFailed;
use App\Services\Reading\ReadingText;
use PHPUnit\Framework\TestCase;

/** Cleaning a Project Gutenberg text and cutting it into pages (Action Plan 4.4). */
class ReadingTextTest extends TestCase
{
    private function file(string $body): string
    {
        return "The Project Gutenberg eBook of Example\nTranslator: Someone\n\n*** START OF THIS PROJECT GUTENBERG EBOOK EXAMPLE ***\n"
            .$body."\n*** END OF THIS PROJECT GUTENBERG EBOOK EXAMPLE ***\nEnd of the Project Gutenberg licence.\n";
    }

    public function test_the_licence_header_footer_and_every_mention_of_the_mark_are_removed(): void
    {
        $clean = (new ReadingText)->clean($this->file("\nTranscribed from the Project Gutenberg edition.\n\nChapter one.\n\n\n\nThe end, at gutenberg.org.\nReally the end."));

        $this->assertSame("Chapter one.\n\nReally the end.", $clean['text']);
        $this->assertSame(2, $clean['removed_lines']);
        $this->assertSame(5, $clean['word_count']);
        $this->assertStringContainsString('Translator: Someone', $clean['header']);
        $this->assertStringNotContainsStringIgnoringCase('gutenberg', $clean['text']);
    }

    public function test_a_file_it_cannot_clean_reliably_is_refused(): void
    {
        foreach (['no markers' => ['Just words.', 'markers_missing'], 'end before start' => ["*** END OF THE PROJECT GUTENBERG EBOOK X ***\n*** START OF THE PROJECT GUTENBERG EBOOK X ***\nText", 'markers_missing'],
            'latin-1' => [$this->file("Caf\xE9"), 'not_utf8'], 'empty' => [$this->file("\nProject Gutenberg only\n"), 'empty_text']] as $case => [$raw, $reason]) {
            try {
                (new ReadingText)->clean($raw);
                $this->fail('Accepted: '.$case);
            } catch (ReadingFetchFailed $failure) {
                $this->assertSame($reason, $failure->reason, $case);
            }
        }
    }

    public function test_pages_follow_paragraphs_and_cover_the_whole_text_without_splitting_characters(): void
    {
        $paragraphs = [];
        foreach (range(1, 40) as $n) {
            $paragraphs[] = "Ndaba $n: ".str_repeat('Ukufunda kumnandi kakhulu ë ', 12);
        }
        $paragraphs[] = str_repeat('averylongwordwithoutspaces', 200);
        $text = implode("\n\n", $paragraphs);
        $pages = (new ReadingText)->pages($text, 1000);

        $this->assertGreaterThan(5, count($pages));
        $joined = '';
        foreach ($pages as [$start, $length]) {
            $page = substr($text, $start, $length);
            $this->assertTrue(mb_check_encoding($page, 'UTF-8'));
            $this->assertLessThanOrEqual(1000, mb_strlen($page));
            $joined .= $page;
        }
        $this->assertSame(preg_replace('/\s+/', '', $text), preg_replace('/\s+/', '', $joined));
        $this->assertStringStartsWith('Ndaba 1:', substr($text, $pages[0][0], $pages[0][1]));
    }
}
