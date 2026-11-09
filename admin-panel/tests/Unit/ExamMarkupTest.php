<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ExamMarkup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExamMarkupTest extends TestCase
{
    public static function attacks(): array
    {
        return [
            ['<script>alert(1)</script>'],
            ['<img src=x onerror=alert(1)>'],
            ['<svg><a xlink:href="javascript:alert(1)">link</a></svg>'],
            ['<math><mtext><table><mglyph><style><!--</style><img title="--><img src=x onerror=alert(1)>">'],
            ['<iframe srcdoc="<script>alert(1)</script>"></iframe>'],
            ['<object data="javascript:alert(1)"></object>'],
            ['<form id=location><input name=href></form>'],
            ['<style>body{background:url(https://example.com/track)}</style>'],
            ['<template><img src=x onerror=alert(1)></template>'],
            ['<!-- <img src=x onerror=alert(1)> -->'],
        ];
    }

    #[DataProvider('attacks')]
    public function test_active_content_is_removed_from_legacy_encoded_html(string $attack): void
    {
        $this->assertSame('<p>Safe</p>', ExamMarkup::stored(safe_htmlspecialchars('<p>Safe</p>'.$attack)));
    }

    public function test_math_and_basic_formatting_survive_without_arbitrary_attributes(): void
    {
        $input = '<p onclick="alert(1)" style="color:red">Solve <strong>x</strong><br>'
            .'<span class="editor math-tex" id="location" onmouseover="alert(1)">\\( x &lt; 3 \\)</span></p>'
            .'<table><tr><td colspan="2" rowspan="999" background="https://example.com">A &amp; B</td></tr></table>';
        $clean = ExamMarkup::clean($input);
        $this->assertSame('<p>Solve <strong>x</strong><br><span class="math-tex">\\( x &lt; 3 \\)</span></p>'
            .'<table><tr><td colspan="2">A &amp; B</td></tr></table>', $clean);
        $this->assertSame($clean, ExamMarkup::clean($clean));
    }

    public function test_plain_question_text_keeps_its_original_meaning(): void
    {
        $text = '<img> is an HTML tag; 2 < 3 & 4 > 1';
        $this->assertSame($text, ExamMarkup::stored(safe_htmlspecialchars($text), false));
    }

    public function test_editor_headings_and_link_labels_remain_readable_without_urls(): void
    {
        $this->assertSame('<h2>Read <a>the question</a></h2><p><font>Then answer</font></p>',
            ExamMarkup::clean('<h2>Read <a href="javascript:alert(1)" target="_blank">the question</a></h2>'
                .'<p><font face="Arial" color="red">Then answer</font></p>'));
    }
}
