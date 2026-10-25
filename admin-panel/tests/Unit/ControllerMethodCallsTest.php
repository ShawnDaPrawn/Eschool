<?php

namespace Tests\Unit;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
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
    /** ResponseService methods that build a response the caller must return or throw. */
    private const RESPONDING = [
        'successResponse', 'errorResponse', 'warningResponse', 'validationError',
        'successRedirectResponse', 'errorRedirectResponse',
    ];

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

    /**
     * ResponseService builds the response and returns it; it no longer sends it
     * and exits. A call whose value is dropped answers nothing and lets the code
     * after it run, so every one must be returned (or thrown, from a helper that
     * has to stop the request: throw new HttpResponseException(...)).
     */
    public function test_every_response_from_response_service_is_returned_or_thrown(): void
    {
        $root = dirname(__DIR__, 2);
        $parser = (new ParserFactory)->createForHostVersion();
        $finder = new NodeFinder;
        $dropped = [];
        $checked = 0;

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            if (! str_contains($source, 'ResponseService')) {
                continue;
            }
            $isResponseService = str_ends_with($file->getPathname(), '/app/Services/ResponseService.php');

            $traverser = new NodeTraverser;
            $traverser->addVisitor(new NameResolver);
            $traverser->addVisitor(new ParentConnectingVisitor);
            $ast = $traverser->traverse($parser->parse($source));

            $calls = $finder->find($ast, function (Node $node) use ($isResponseService) {
                if (! $node instanceof Node\Expr\StaticCall || ! $node->class instanceof Node\Name || ! $node->name instanceof Node\Identifier) {
                    return false;
                }
                $class = $node->class->toString();

                return in_array($node->name->toString(), self::RESPONDING, true)
                    && ($class === 'App\Services\ResponseService' || ($isResponseService && in_array($class, ['self', 'static'], true)));
            });

            foreach ($calls as $call) {
                $checked++;
                if (! $this->valueIsReturnedOrThrown($call)) {
                    $dropped[] = str_replace($root.'/', '', $file->getPathname()).':'.$call->getStartLine().' ResponseService::'.$call->name->toString().'()';
                }
            }
        }

        $this->assertGreaterThan(500, $checked, 'Expected to find the ResponseService call sites');
        $this->assertSame([], $dropped, 'Return these responses (or throw them in an HttpResponseException)');
    }

    public function test_returned_response_chains_are_accepted_but_dropped_chains_are_refused(): void
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        foreach ([
            'return ResponseService::successResponse()->header("Cache-Control", "private")->header("Pragma", "no-cache");' => true,
            'ResponseService::successResponse()->header("Cache-Control", "private");' => false,
            'return consumes(ResponseService::successResponse());' => false,
        ] as $statement => $returned) {
            $nodes = $parser->parse('<?php function example() { '.$statement.' }');
            $traverser = new NodeTraverser;
            $traverser->addVisitor(new ParentConnectingVisitor);
            $nodes = $traverser->traverse($nodes);
            $call = (new NodeFinder)->findFirstInstanceOf($nodes, Node\Expr\StaticCall::class);
            $this->assertNotNull($call);
            $this->assertSame($returned, $this->valueIsReturnedOrThrown($call), $statement);
        }
    }

    /** Whether an expression's value ends up returned or thrown by its function. */
    private function valueIsReturnedOrThrown(Node\Expr $expr): bool
    {
        $parent = $expr->getAttribute('parent');

        if ($parent instanceof Node\Stmt\Return_ || $parent instanceof Node\Expr\Throw_) {
            return true;
        }
        // A returned response may set headers through a fluent receiver chain.
        // Calls passed as arguments remain excluded: their value can be ignored.
        if ($parent instanceof Node\Expr\MethodCall && $parent->var === $expr) {
            return $this->valueIsReturnedOrThrown($parent);
        }
        // return $cond ? ResponseService::a() : ResponseService::b();
        if ($parent instanceof Node\Expr\Ternary && $parent->cond !== $expr) {
            return $this->valueIsReturnedOrThrown($parent);
        }
        // throw new HttpResponseException(ResponseService::...());
        if ($parent instanceof Node\Arg) {
            $new = $parent->getAttribute('parent');

            return $new instanceof Node\Expr\New_
                && $new->class instanceof Node\Name
                && $new->class->toString() === 'Illuminate\Http\Exceptions\HttpResponseException'
                && $this->valueIsReturnedOrThrown($new);
        }

        return false;
    }
}
