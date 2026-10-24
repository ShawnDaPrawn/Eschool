<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academics\Content\ContentMarkdown;
use Tests\TestCase;

class ContentMarkdownTest extends TestCase
{
    public function test_raw_html_images_and_unsafe_reference_links_never_become_active_markup(): void
    {
        $service = app(ContentMarkdown::class);
        $raw = "# Fractions\n\n<script>alert('private')</script>\n\n<img src=x onerror=alert(1)>\n\n![diagram](https://evil.test/track)\n\n[Open][bad] and [Safe](https://school.example/lesson)\n\n[bad]: javascript:alert(1)\n";
        $clean = $service->clean($raw);
        $html = $service->html($clean);
        $this->assertStringContainsString('# Fractions', $clean);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('evil.test', $html);
        $this->assertStringContainsString('href="https://school.example/lesson"', $html);
    }

    public function test_tables_lists_code_and_literal_markup_keep_their_readable_meaning(): void
    {
        $s = app(ContentMarkdown::class);
        $raw = "## Equivalent fractions\n\n- One half\n- Two quarters\n\n| Part | Whole |\n| --- | --- |\n| 1 | 2 |\n\n```html\n<script>example()</script>\n```\n\nLiteral \<img> and **important**.\n";
        $clean = $s->clean($raw);
        $html = $s->html($clean);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<li>', $html);
        $this->assertStringContainsString('<strong>important</strong>', $html);
        $this->assertStringContainsString('&lt;script&gt;example()&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Equivalent fractions', $html);
    }

    public function test_ordered_items_keep_nested_lists_paragraphs_and_code_inside_the_item(): void
    {
        $service = app(ContentMarkdown::class);
        $raw = "10. Question about halves\n\n    - A nested fraction\n    - Another fraction\n\n    Explain the answer.\n\n    ```text\n    half = two quarters\n    ```\n\n11. Next question\n";
        $clean = $service->clean($raw);
        $html = $service->html($clean);
        $document = new \DOMDocument;
        $document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('/html/body/ol')->length);
        $this->assertSame('10', $xpath->query('/html/body/ol')->item(0)->getAttribute('start'));
        $this->assertSame(2, $xpath->query('/html/body/ol/li')->length);
        $this->assertSame(2, $xpath->query('/html/body/ol/li[1]/ul/li')->length);
        $this->assertSame(1, $xpath->query('/html/body/ol/li[1]/pre/code')->length);
        $this->assertStringContainsString('Explain the answer.', $xpath->query('/html/body/ol/li[1]')->item(0)->textContent);
        $this->assertSame($html, $service->html($service->clean($clean)));
    }

    public function test_inline_code_preserves_literal_spaces_and_backticks_across_saves(): void
    {
        $service = app(ContentMarkdown::class);
        foreach (['`  `' => '  ', '`` `x` ``' => '`x`', '`  x  `' => ' x '] as $raw => $literal) {
            $clean = $service->clean('Example '.$raw.'.');
            $html = $service->html($clean);
            $document = new \DOMDocument;
            $document->loadHTML($html);
            $this->assertSame($literal, $document->getElementsByTagName('code')->item(0)->textContent);
            $this->assertSame($html, $service->html($service->clean($clean)));
        }
    }
}
