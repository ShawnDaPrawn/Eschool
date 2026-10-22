<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The admin panel and the public website take their colours from the Material 3
 * design tokens in public/assets/css/m3-tokens.css, which are generated from the
 * apps' theme (see packages/eschool_core/test/web_tokens_test.dart). A raw colour
 * in a stylesheet would drift from the apps, so the stylesheets (public/assets/css
 * and the Material 3 library in public/assets/m3) may only use the tokens. See wiki/UI-Standards.md.
 *
 * Not checked: the generated tokens themselves, the legacy template bundles and
 * third-party CSS. Colours inside data: URIs can't read CSS variables, so they are
 * skipped. A stylesheet can switch the check off around a block that must keep
 * its own colours (such as the printed ID card) with comments reading
 * "design-tokens: off" and "design-tokens: on".
 */
class DesignTokensTest extends TestCase
{
    private const UNCHECKED = [
        'm3-tokens.css',            // generated: the tokens themselves
        'style.css',                // legacy template bundles
        'rtl.css',
        'vendor.bundle.base.css',
        'flatpickr.min.css',        // third-party
        'ekko-lightbox.css',
    ];

    private const NAMED_COLOURS = 'white|black|red|green|blue|gr[ae]y|silver|yellow|orange|purple|navy|teal|maroon|olive|lime|aqua|fuchsia|pink|brown|gold|indigo|violet|crimson';

    public function test_stylesheets_use_the_design_tokens_for_colour(): void
    {
        $assets = dirname(__DIR__, 2).'/public/assets';
        $found = [];

        // The legacy redesign sheets and the Material 3 library.
        foreach (array_merge(glob($assets.'/css/*.css'), glob($assets.'/m3/*.css')) as $path) {
            if (in_array(basename($path), self::UNCHECKED, true)) {
                continue;
            }
            foreach ($this->rawColours(file_get_contents($path)) as $line => $text) {
                $found[] = basename($path).":$line  $text";
            }
        }

        $this->assertSame([], $found, "Use the --md-sys-* / --app-* tokens instead of raw colours:\n".implode("\n", $found));
    }

    /** @return array<int, string> line number => offending declaration */
    private function rawColours(string $css): array
    {
        // Blank out what isn't checked, keeping the line numbers.
        $blank = fn (array $m) => preg_replace('/[^\n]/', ' ', $m[0]);
        $css = preg_replace_callback('#/\*\s*design-tokens:\s*off\b.*?/\*\s*design-tokens:\s*on\s*\*/#s', $blank, $css);
        $css = preg_replace_callback('#/\*.*?\*/#s', $blank, $css);
        $css = preg_replace_callback('#url\(\s*(["\']?)data:.*?\1\s*\)#s', $blank, $css);
        $css = preg_replace_callback('#url\([^)]*\)#', $blank, $css);

        // Only declaration values: the innermost { } blocks, split on ";".
        $raw = '/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?)\(|(?<![\w-])(?:'.self::NAMED_COLOURS.')(?![\w-])/i';
        $hits = [];
        preg_match_all('/\{([^{}]*)\}/', $css, $blocks, PREG_OFFSET_CAPTURE);
        foreach ($blocks[1] as [$body, $offset]) {
            foreach (explode(';', $body) as $declaration) {
                $value = strstr($declaration, ':');
                if ($value !== false && preg_match($raw, $value)) {
                    $start = $offset + strlen($declaration) - strlen(ltrim($declaration));
                    $hits[substr_count($css, "\n", 0, $start) + 1] = trim(preg_replace('/\s+/', ' ', $declaration));
                }
                $offset += strlen($declaration) + 1;
            }
        }

        return $hits;
    }
}
