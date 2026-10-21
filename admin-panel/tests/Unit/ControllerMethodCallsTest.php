<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Larastan can't see a call to a missing method on a controller, because
 * Illuminate's base Controller has a __call that accepts any name. Splitting or
 * moving controller methods is where this goes wrong, so check it here.
 */
class ControllerMethodCallsTest extends TestCase
{
    public function test_controllers_only_call_methods_they_have(): void
    {
        $missing = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/Http/Controllers'));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            if (! preg_match('/^namespace\s+([^;]+);/m', $source, $namespace)
                || ! preg_match('/^(?:abstract\s+)?(?:class|trait)\s+(\w+)/m', $source, $name)) {
                continue;
            }
            $class = $namespace[1].'\\'.$name[1];

            // PHP method names are case-insensitive, and method_exists() agrees.
            preg_match_all('/(?:\$this->|self::|static::)(\w+)\s*\(/', $source, $calls);
            foreach (array_unique($calls[1]) as $method) {
                if (! method_exists($class, $method)) {
                    $missing[] = "$class::$method()";
                }
            }
        }

        // dashboardData() is unrouted and its helper was commented out long ago.
        $missing = array_values(array_diff($missing, ['App\Http\Controllers\HomeController::getSuperAdminData()']));

        $this->assertSame([], $missing, 'Controllers call methods that do not exist');
    }
}
