<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Emphasis;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/** Parse first, then write a safe Markdown subset. No raw HTML or image fetches. */
class ContentMarkdown
{
    private function environment(): Environment
    {
        $e = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 30, 'max_delimiters_per_line' => 1000]);
        $e->addExtension(new CommonMarkCoreExtension);
        $e->addExtension(new TableExtension);

        return $e;
    }

    public function clean(string $input): string
    {
        abort_if(strlen($input) > (int) config('academic_content.maximum_markdown_bytes') || ! mb_check_encoding($input, 'UTF-8'), 422, __('Converted text exceeds its safe limit.'));
        $input = str_replace("\0", '', $input);
        $document = (new MarkdownParser($this->environment()))->parse($input);
        $clean = trim($this->write($document));
        abort_if($clean === '' || strlen($clean) > (int) config('academic_content.maximum_markdown_bytes'), 422, __('No readable content is available. Review the source file.'));

        return $clean."\n";
    }

    public function html(string $clean): string
    {
        return (new MarkdownConverter($this->environment()))->convert($clean)->getContent();
    }

    private function children(Node $node): string
    {
        $text = '';
        foreach ($node->children() as $child) {
            $text .= $this->write($child);
        }

        return $text;
    }

    private function escape(string $text): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+.!|>~<>-])/u', '\\\\$1', $text);
    }

    private function safeUrl(string $url): ?string
    {
        if (preg_match('/[\x00-\x20\x7f<>"\\\\]/', $url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return str_replace(['(', ')'], ['%28', '%29'], $url);
    }

    private function fence(string $literal, int $minimum): string
    {
        preg_match_all('/`+/', $literal, $runs);
        $length = $minimum;
        foreach ($runs[0] as $run) {
            $length = max($length, strlen($run) + 1);
        }

        return str_repeat('`', $length);
    }

    private function write(Node $node): string
    {
        if ($node instanceof HtmlBlock || $node instanceof HtmlInline) {
            return '';
        }
        if ($node instanceof Text) {
            return $this->escape($node->getLiteral());
        }
        if ($node instanceof Image) {
            return $this->children($node);
        }
        if ($node instanceof Link) {
            $label = $this->children($node);
            $url = $this->safeUrl($node->getUrl());

            return $url === null ? $label : '['.$label.']('.$url.')';
        }
        if ($node instanceof Code) {
            $f = $this->fence($node->getLiteral(), 1);

            return $f.' '.$node->getLiteral().' '.$f;
        }
        if ($node instanceof FencedCode || $node instanceof IndentedCode) {
            $f = $this->fence($node->getLiteral(), 3);

            return $f."\n".$node->getLiteral()."\n".$f."\n\n";
        }
        if ($node instanceof Strong) {
            return '**'.$this->children($node).'**';
        }
        if ($node instanceof Emphasis) {
            return '*'.$this->children($node).'*';
        }
        if ($node instanceof Newline) {
            return "\n";
        }
        if ($node instanceof Heading) {
            return str_repeat('#', $node->getLevel()).' '.$this->children($node)."\n\n";
        }
        if ($node instanceof Paragraph) {
            return $this->children($node)."\n\n";
        }
        if ($node instanceof BlockQuote) {
            return '> '.str_replace("\n", "\n> ", trim($this->children($node)))."\n\n";
        }
        if ($node instanceof ThematicBreak) {
            return "---\n\n";
        }
        if ($node instanceof ListBlock) {
            $output = '';
            $index = $node->getListData()->start ?? 1;
            foreach ($node->children() as $item) {
                $prefix = $node->getListData()->type === ListBlock::TYPE_ORDERED ? $index++.'. ' : '- ';
                $output .= $prefix.str_replace("\n", "\n  ", trim($this->children($item)))."\n";
            }

            return $output."\n";
        }
        if ($node instanceof Table) {
            $rows = [];
            foreach ($node->children() as $section) {
                foreach ($section->children() as $row) {
                    $cells = [];
                    foreach ($row->children() as $cell) {
                        $cells[] = str_replace("\n", ' ', trim($this->children($cell)));
                    } $rows[] = $cells;
                }
            }
            if ($rows === []) {
                return '';
            }
            $head = array_shift($rows);
            $result = '| '.implode(' | ', $head)." |\n| ".implode(' | ', array_fill(0, count($head), '---'))." |\n";
            foreach ($rows as $row) {
                $result .= '| '.implode(' | ', $row)." |\n";
            }

            return $result."\n";
        }

        return $this->children($node);
    }
}
