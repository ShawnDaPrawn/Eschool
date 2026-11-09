<?php

declare(strict_types=1);

namespace App\Services;

use DOMElement;
use DOMNode;
use DOMText;
use Masterminds\HTML5;

/** A small, inert formatting vocabulary for the legacy equation editor. */
final class ExamMarkup
{
    private const TAGS = ['p', 'div', 'span', 'br', 'hr', 'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'small', 'mark', 'del', 'ins', 'font',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption'];

    public static function stored(?string $value, bool $equation = true): string
    {
        $html = safe_htmlspecialchars_decode($value);

        return $equation ? self::clean($html) : $html;
    }

    public static function clean(string $html): string
    {
        // Bound parser work. Oversized historical content is displayed as text.
        if (strlen($html) > 262144) {
            return e($html);
        }

        // HTML5 parses a fragment without loading network resources or XML DTDs.
        // Rebuild it from trusted tag names and escaped text, never serialize
        // attacker-supplied attributes, namespaces, URLs, CSS or comments.
        $fragment = (new HTML5)->loadHTMLFragment($html);
        $remaining = 10000;
        $output = '';
        foreach ($fragment->childNodes as $node) {
            $output .= self::node($node, 0, $remaining);
        }

        return $output;
    }

    private static function node(DOMNode $node, int $depth, int &$remaining): string
    {
        if ($depth > 64 || --$remaining < 0) {
            return '';
        }
        if ($node instanceof DOMText) {
            return e($node->textContent);
        }
        if (! $node instanceof DOMElement || ! in_array($node->tagName, self::TAGS, true)) {
            return '';
        }

        $tag = $node->tagName;
        $attributes = '';
        if ($tag === 'span' && in_array('math-tex', preg_split('/\s+/', $node->getAttribute('class')) ?: [], true)) {
            $attributes = ' class="math-tex"';
        }
        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                $value = $node->getAttribute($attribute);
                if (ctype_digit($value) && (int) $value >= 1 && (int) $value <= 20) {
                    $attributes .= ' '.$attribute.'="'.(int) $value.'"';
                }
            }
        }
        $output = '<'.$tag.$attributes.'>';
        if (in_array($tag, ['br', 'hr'], true)) {
            return $output;
        }
        foreach ($node->childNodes as $child) {
            $output .= self::node($child, $depth + 1, $remaining);
        }

        return $output.'</'.$tag.'>';
    }
}
