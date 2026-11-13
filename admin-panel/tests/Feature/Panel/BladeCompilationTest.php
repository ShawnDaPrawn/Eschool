<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BladeCompilationTest extends TestCase
{
    public function test_views_compile_without_syntax_errors_or_silently_uncompiled_flow_directives(): void
    {
        $compiler = app('blade.compiler');
        $count = 0;
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $count++;
            $tokens = token_get_all($compiler->compileString($file->getContents()), TOKEN_PARSE);
            foreach ($tokens as $token) {
                if (! is_array($token) || $token[0] !== T_INLINE_HTML) {
                    continue;
                }
                // Adjacent directives such as @endif@include can silently hide
                // a required control while producing syntactically valid PHP.
                $this->assertDoesNotMatchRegularExpression(
                    '/@(if|elseif|else|endif|foreach|endforeach|forelse|empty|endforelse|include|section|endsection|yield|php|endphp|csrf|method|can|endcan|isset|endisset|unless|endunless|auth|endauth)\b/',
                    $token[1],
                    $file->getRelativePathname().' contains an uncompiled Blade directive.'
                );
            }
        }
        $this->assertGreaterThan(0, $count);
    }
}
