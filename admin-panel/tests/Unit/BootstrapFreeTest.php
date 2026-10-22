<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The admin panel and the website are moving off Bootstrap onto our own
 * Material 3 library (public/assets/m3, see wiki/UI-Standards.md), one part
 * at a time. tests/bootstrap-baseline.txt lists the files that still use
 * Bootstrap. This test fails when
 *   - a file that isn't on the list uses Bootstrap (new code uses the M3
 *     library instead), or
 *   - a file on the list no longer does (take it off, so the list only shrinks).
 * When the list is empty, Bootstrap is gone and the list and this exception
 * mechanism can be deleted.
 *
 * Run with UPDATE_BOOTSTRAP_BASELINE=1 to drop finished files from the list.
 * It never adds files.
 */
class BootstrapFreeTest extends TestCase
{
    private const BASELINE = 'tests/bootstrap-baseline.txt';

    /** Where Bootstrap markup and calls can live. */
    private const SCANNED = [
        'resources/views' => '.blade.php',
        'public/assets/js/custom' => '.js',
        'public/assets/js/script.js' => '.js',
        'app' => '.php',
    ];

    /** Bootstrap 4/5 and admin-template class names (whole tokens in a class attribute). */
    private const CLASS_PATTERN = '/^(?:container(?:-fluid)?|row|no-gutters|g-[0-5]|col(?:-(?:xs|sm|md|lg|xl|xxl))?(?:-(?:\d+|auto))?'
        .'|offset(?:-(?:sm|md|lg|xl))?-\d+|btn(?:-[a-z0-9-]+)?|btn-group(?:-[a-z]+)?|form-(?:control|group|check|label|select|text|row|inline|switch|range|floating)(?:-[a-z-]+)?'
        .'|input-group(?:-[a-z-]+)?|custom-(?:control|checkbox|radio|switch|select|file|range)(?:-[a-z-]+)?'
        .'|modal(?:-[a-z-]+)?|dropdown(?:-[a-z-]+)?|dropup|dropright|dropleft|nav|nav-[a-z-]+|navbar(?:-[a-z-]+)?'
        .'|card(?:-[a-z-]+)?|table(?:-[a-z-]+)?|badge(?:-[a-z-]+)?|alert(?:-[a-z-]+)?|list-group(?:-[a-z-]+)?'
        .'|pagination(?:-[a-z]+)?|page-item|page-link|progress|progress-bar(?:-[a-z-]+)?|breadcrumb(?:-item)?'
        .'|offcanvas(?:-[a-z-]+)?|accordion(?:-[a-z-]+)?|collapse|collapsing|carousel(?:-[a-z-]+)?|jumbotron'
        .'|btn-close|close|sr-only|visually-hidden|img-fluid|img-thumbnail|spinner-(?:border|grow)(?:-sm)?'
        .'|invalid-feedback|valid-feedback|was-validated|is-invalid|is-valid|embed-responsive(?:-[a-z0-9-]+)?'
        .'|d-(?:none|inline|inline-block|block|flex|inline-flex|grid|table|table-cell)|d-(?:sm|md|lg|xl)-(?:none|inline|inline-block|block|flex|inline-flex|grid)'
        .'|[mp][trblxyse]?-(?:sm-|md-|lg-|xl-)?(?:[0-5]|auto|n[1-5])|text-(?:left|right|center|start|end|justify|muted|primary|secondary|success|danger|warning|info|light|dark|white|body|uppercase|lowercase|capitalize|nowrap|truncate|wrap|break)'
        .'|bg-(?:primary|secondary|success|danger|warning|info|light|dark|white|transparent|gradient-[a-z]+)'
        .'|border(?:-(?:top|right|bottom|left|0|primary|secondary|success|danger|warning|info|light|dark|white))?|rounded(?:-[a-z0-9]+)?'
        .'|shadow(?:-sm|-lg|-none)?|[wh]-(?:25|50|75|100|auto)|mw-100|mh-100|vw-100|vh-100'
        .'|justify-content-(?:[a-z]+-)?(?:start|end|center|between|around|evenly)|align-items-(?:[a-z]+-)?(?:start|end|center|baseline|stretch)'
        .'|align-self-[a-z]+|align-content-[a-z]+|flex-(?:row|column|wrap|nowrap|grow-[01]|shrink-[01]|fill)(?:-reverse)?'
        .'|float-(?:left|right|none|start|end)|font-weight-[a-z]+|fw-[a-z]+|fs-[1-6]|small|lead|display-[1-6]|h[1-6]'
        .'|position-(?:static|relative|absolute|fixed|sticky)|fixed-(?:top|bottom)|sticky-top|overflow-(?:auto|hidden)|clearfix)$/';

    /** Bootstrap's data API, its jQuery plugins and its assets. */
    private const OTHER_PATTERNS = [
        'data attribute' => '/\bdata-(?:bs-[a-z-]+|toggle|dismiss)\s*=/',
        'jQuery plugin call' => '/\.(?:modal|dropdown|collapse|tab|tooltip|popover|carousel|bootstrapTable)\s*\(/',
        'Bootstrap JS' => '/\bbootstrap\.(?:Modal|Dropdown|Collapse|Tab|Tooltip|Popover|Offcanvas|Toast)\b|new\s+bootstrap\b/',
        'asset' => '/(?:href|src)\s*=\s*["\'][^"\']*(?:bootstrap|vendor\.bundle\.base|\/css\/style\.css|\/css\/rtl\.css)/i',
    ];

    public function test_only_files_on_the_baseline_use_bootstrap(): void
    {
        $root = dirname(__DIR__, 2);
        $baseline = $this->baseline($root);
        $using = $this->filesUsingBootstrap($root);

        if (getenv('UPDATE_BOOTSTRAP_BASELINE') === '1') {
            $baseline = array_values(array_intersect($baseline, array_keys($using)));
            file_put_contents($root.'/'.self::BASELINE, $this->header().implode("\n", $baseline)."\n");
        }

        $new = array_diff_key($using, array_flip($baseline));
        $this->assertSame([], array_map(fn ($hit) => $hit, $new),
            "These files use Bootstrap. Build them with the Material 3 library (public/assets/m3, wiki/UI-Standards.md) instead:\n"
            .implode("\n", array_map(fn ($file, $hit) => "$file  ($hit)", array_keys($new), $new)));

        $finished = array_values(array_diff($baseline, array_keys($using)));
        $this->assertSame([], $finished,
            'These files no longer use Bootstrap. Remove them from '.self::BASELINE
            ." (or run with UPDATE_BOOTSTRAP_BASELINE=1):\n".implode("\n", $finished));
    }

    /** @return array<string, string> relative path => first Bootstrap use found */
    private function filesUsingBootstrap(string $root): array
    {
        $found = [];
        foreach (self::SCANNED as $path => $suffix) {
            foreach ($this->files($root.'/'.$path, $suffix) as $file) {
                $hit = $this->firstBootstrapUse(file_get_contents($file));
                if ($hit !== null) {
                    $found[substr($file, strlen($root) + 1)] = $hit;
                }
            }
        }
        ksort($found);

        return $found;
    }

    private function firstBootstrapUse(string $source): ?string
    {
        // Blade and HTML comments don't count.
        $source = preg_replace('/\{\{--.*?--\}\}|<!--.*?-->/s', '', $source);

        if (preg_match_all('/\bclass\s*=\s*(["\'])(.*?)\1/s', $source, $attributes)) {
            foreach ($attributes[2] as $value) {
                // Ignore Blade/PHP expressions inside the attribute.
                $value = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@[a-z]+\(.*?\)|<\?.*?\?>/s', ' ', $value);
                foreach (preg_split('/\s+/', trim($value)) as $token) {
                    if ($token !== '' && preg_match(self::CLASS_PATTERN, $token)) {
                        return 'class "'.$token.'"';
                    }
                }
            }
        }
        foreach (self::OTHER_PATTERNS as $label => $pattern) {
            if (preg_match($pattern, $source, $m)) {
                return $label.' "'.trim($m[0]).'"';
            }
        }

        return null;
    }

    /** @return list<string> */
    private function files(string $path, string $suffix): array
    {
        if (is_file($path)) {
            return [$path];
        }
        if (! is_dir($path)) {
            return [];
        }
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /** @return list<string> */
    private function baseline(string $root): array
    {
        $file = $root.'/'.self::BASELINE;
        if (! is_file($file)) {
            return [];
        }
        $lines = array_map('trim', file($file));

        return array_values(array_filter($lines, fn ($line) => $line !== '' && ! str_starts_with($line, '#')));
    }

    private function header(): string
    {
        return "# Files that still use Bootstrap. See tests/Unit/BootstrapFreeTest.php.\n"
            ."# This list only shrinks: build new and rewritten views with the Material 3\n"
            ."# library (public/assets/m3) and remove them from here.\n";
    }
}
