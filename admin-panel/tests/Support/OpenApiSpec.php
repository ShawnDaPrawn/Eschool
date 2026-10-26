<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Psr7\Response as PsrResponse;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * admin-panel/openapi.yaml, the API v2 contract, for tests: which operations
 * it describes, and whether a response matches it (league's
 * openapi-psr7-validator checks the status, headers and body schema).
 */
final class OpenApiSpec
{
    /** Where the spec's paths are served; its servers entry. */
    public const BASE_PATH = 'api/v2';

    private static ?ResponseValidator $validator = null;

    /** @var array<string, list<string>>|null */
    private static ?array $operations = null;

    public static function file(): string
    {
        return dirname(__DIR__, 2).'/openapi.yaml';
    }

    /**
     * Every operation in the spec: path template (as written in the spec,
     * relative to /api/v2) => upper-case methods.
     *
     * @return array<string, list<string>>
     */
    public static function operations(): array
    {
        if (self::$operations === null) {
            self::$operations = [];
            foreach (Yaml::parseFile(self::file())['paths'] ?? [] as $path => $item) {
                $methods = array_intersect(array_keys($item), ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace']);
                self::$operations[$path] = array_values(array_map('strtoupper', $methods));
            }
        }

        return self::$operations;
    }

    /**
     * The spec path for a route or request path such as api/v2/me/context or
     * api/v2/children/{child}; null when it is not under /api/v2 or the spec
     * has no such path.
     */
    public static function pathFor(string $path): ?string
    {
        $path = '/'.ltrim($path, '/');
        $prefix = '/'.self::BASE_PATH;
        if (! str_starts_with($path, $prefix.'/')) {
            return null;
        }
        $relative = substr($path, strlen($prefix));

        // OpenAPI gives a concrete path priority over a parameter template,
        // regardless of declaration order (for example /digests/templates).
        if (array_key_exists($relative, self::operations())) {
            return $relative;
        }
        foreach (array_keys(self::operations()) as $template) {
            $pattern = '#^'.preg_replace('#\\\\\{[^/]+\\\\\}#', '[^/]+', preg_quote($template, '#')).'$#';
            if ($template === $relative || preg_match($pattern, $relative) === 1) {
                return $template;
            }
        }

        return null;
    }

    public static function describes(string $method, string $path): bool
    {
        $template = self::pathFor($path);

        return $template !== null && in_array(strtoupper($method), self::operations()[$template], true);
    }

    /**
     * Fails the test unless the response to `$method $path` matches the spec:
     * a documented status, its headers and its body schema.
     */
    public static function assertResponseMatches(string $method, string $path, Response|ResponseInterface $response): void
    {
        $template = self::pathFor($path);
        Assert::assertNotNull($template, "openapi.yaml has no path for $method /".ltrim($path, '/'));
        Assert::assertTrue(self::describes($method, $path), "openapi.yaml has no $method operation on $template");

        $psr = $response instanceof ResponseInterface
            ? $response
            : new PsrResponse($response->getStatusCode(), $response->headers->all(), (string) $response->getContent());

        try {
            self::validator()->validate(new OperationAddress($template, strtolower($method)), $psr);
        } catch (ValidationFailed $e) {
            Assert::fail(sprintf(
                "The %d response to %s /%s does not match openapi.yaml:\n%s\nBody: %s",
                $psr->getStatusCode(), strtoupper($method), ltrim($path, '/'), self::explain($e), substr((string) $psr->getBody(), 0, 2000),
            ));
        }

        Assert::assertTrue(true);
    }

    private static function validator(): ResponseValidator
    {
        return self::$validator ??= (new ValidatorBuilder)->fromYamlFile(self::file())->getResponseValidator();
    }

    /** The validator's reasons, outermost first, with where in the body each applies. */
    private static function explain(Throwable $e): string
    {
        $lines = [];
        for ($error = $e; $error !== null; $error = $error->getPrevious()) {
            $where = $error instanceof SchemaMismatch && $error->dataBreadCrumb() !== null
                ? ' (at '.implode('.', $error->dataBreadCrumb()->buildChain()).')'
                : '';
            $lines[] = '- '.$error->getMessage().$where;
        }

        return implode("\n", $lines);
    }
}
