<?php

declare(strict_types=1);

namespace App\Logging;

use BackedEnum;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\QueryException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;
use UnitEnum;

/**
 * Writes each log record as one line of JSON in the shape Cloud Logging reads
 * from a container's stderr: severity, message and time, the request fields
 * (request id, user id, school id, route, method), and for errors with an
 * exception a stack trace that Cloud Error Reporting groups.
 *
 * Logs carry no personal data beyond ids (Build Bible 5.6). The message is the
 * code's own text, with e-mail addresses, South African ID numbers and phone
 * numbers masked. Context and extra values are kept only when they cannot hold
 * personal data: numbers and booleans, values under keys ending in "id" or
 * "ids", and short strings under the descriptive keys in STRING_KEYS. Every
 * other string becomes "[omitted]", so a request body passed as context never
 * reaches the log, and the formatter itself never reads the request.
 */
final class JsonFormatter implements FormatterInterface
{
    /** Fields every line carries, null when unknown (filled by AddRequestContext). */
    public const REQUEST_FIELDS = ['request_id', 'user_id', 'school_id', 'route', 'method'];

    /** Keys whose short string values describe the event, not a person. */
    public const STRING_KEYS = [
        'action', 'channel', 'class', 'code', 'command', 'connection', 'driver', 'event',
        'exception_class', 'gateway', 'job', 'level', 'method', 'module', 'provider', 'queue',
        'result', 'route', 'status', 'task', 'type',
    ];

    public const MESSAGE_LIMIT = 8000;

    private const STRING_LIMIT = 200;

    private const DEPTH_LIMIT = 4;

    private const ITEMS_LIMIT = 50;

    private const TRACE_FRAMES = 40;

    private const REPORTED_ERROR_EVENT = 'type.googleapis.com/google.devtools.clouderrorreporting.v1beta1.ReportedErrorEvent';

    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION;

    public function __construct(
        private readonly ?string $project = null,
        private readonly ?string $service = null,
        private readonly ?string $version = null,
    ) {}

    public function format(LogRecord $record): string
    {
        $context = $record->context;
        $extra = $record->extra;
        $exception = $context['exception'] ?? null;
        unset($context['exception']);

        $entry = [
            'severity' => $record->level->getName(),
            'message' => $this->message($record, $exception instanceof Throwable ? $exception : null),
            'time' => $record->datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z'),
            'channel' => $record->channel,
        ];

        foreach (self::REQUEST_FIELDS as $field) {
            $value = $extra[$field] ?? null;
            $entry[$field] = is_int($value) || is_string($value) ? $value : null;
            unset($extra[$field]);
        }

        $traceId = $extra['trace_id'] ?? null;
        unset($extra['trace_id']);
        if (is_string($traceId) && $traceId !== '' && $this->project) {
            $entry['logging.googleapis.com/trace'] = 'projects/'.$this->project.'/traces/'.$traceId;
        }

        $entry['context'] = $this->filter($context);
        if ($extra !== []) {
            $entry['extra'] = $this->filter($extra);
        }

        if ($exception instanceof Throwable) {
            $entry['exception'] = $this->exceptionSummary($exception);
            if (Level::Error->includes($record->level)) {
                // Error Reporting groups entries of this type by the stack trace.
                $entry['@type'] = self::REPORTED_ERROR_EVENT;
                $entry['stack_trace'] = $this->stackTrace($exception);
                $entry['serviceContext'] = array_filter(['service' => $this->service, 'version' => $this->version]);
                $entry['context']['reportLocation'] = [
                    'filePath' => $exception->getFile(),
                    'lineNumber' => $exception->getLine(),
                    'functionName' => $this->throwingFunction($exception),
                ];
            }
        }

        if ($entry['context'] === []) {
            unset($entry['context']);
        }

        $json = json_encode($entry, self::JSON_FLAGS);
        if ($json === false) {
            $json = (string) json_encode([
                'severity' => $entry['severity'],
                'message' => 'Log entry could not be encoded as JSON',
                'time' => $entry['time'],
            ], self::JSON_FLAGS);
        }

        return $json."\n";
    }

    public function formatBatch(array $records): string
    {
        return implode('', array_map(fn (LogRecord $record) => $this->format($record), $records));
    }

    private function message(LogRecord $record, ?Throwable $exception): string
    {
        $message = $record->message;
        // Laravel reports an exception with its own message as the log message;
        // a query error's message holds the bound values, so use the safe one.
        if ($exception !== null && $message === $exception->getMessage()) {
            $message = $this->exceptionMessage($exception);
        }

        return $this->scrub($this->truncate($message, self::MESSAGE_LIMIT));
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function filter(array $values, int $depth = 0): array
    {
        $kept = [];
        $count = 0;
        foreach ($values as $key => $value) {
            if (++$count > self::ITEMS_LIMIT) {
                $kept['_truncated'] = count($values) - self::ITEMS_LIMIT;
                break;
            }
            $kept[$key] = $this->value((string) $key, $value, $depth);
        }

        return $kept;
    }

    private function value(string $key, mixed $value, int $depth): mixed
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $this->isPersonalNumberKey($key) && ! $this->isIdKey($key) ? '[omitted]' : $value;
        }
        if ($value instanceof Throwable) {
            return $this->exceptionSummary($value);
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof UnitEnum) {
            return $value->name;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }
        if (is_array($value)) {
            if ($depth >= self::DEPTH_LIMIT) {
                return '[omitted]';
            }
            if (array_is_list($value)) {
                // The items of a list are judged by the list's own key (student_ids => [1, 2]).
                return array_map(
                    fn (mixed $item) => $this->value($key, $item, $depth + 1),
                    array_slice($value, 0, self::ITEMS_LIMIT)
                );
            }

            return $this->filter($value, $depth + 1);
        }
        if (is_object($value)) {
            return '[object '.$value::class.']';
        }
        if (is_string($value) && ($this->isIdKey($key) || in_array(strtolower($key), self::STRING_KEYS, true))) {
            return $this->scrub($this->truncate($value, self::STRING_LIMIT));
        }

        return '[omitted]';
    }

    private function isIdKey(string $key): bool
    {
        return (bool) preg_match('/(^|_)(ids?|uuid)$|[a-z](Id|Ids|Uuid)$/', $key);
    }

    private function isPersonalNumberKey(string $key): bool
    {
        return (bool) preg_match('/phone|mobile|id_?number|identity|passport|account|card|birth|dob/i', $key);
    }

    /**
     * Masks personal data that code sometimes puts into a message: e-mail
     * addresses (mail errors quote the recipient), 13-digit South African ID
     * numbers and South African phone numbers.
     */
    private function scrub(string $text): string
    {
        return (string) preg_replace(
            [
                '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
                '/(?<!\d)\d{13}(?!\d)/',
                '/(?<![\d+])(?:\+27|0)\d{9}(?!\d)/',
            ],
            ['[email]', '[id-number]', '[phone]'],
            $text
        );
    }

    private function truncate(string $text, int $limit): string
    {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit).'… [truncated]' : $text;
    }

    /**
     * An exception's message, minus anything that can carry data: a query
     * error keeps its SQL with the placeholders, never the bound values or the
     * database's own message (which quotes them, as in "Duplicate entry").
     */
    private function exceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof QueryException) {
            return 'Database error SQLSTATE['.$exception->getCode().'] (SQL: '.$exception->getSql().')';
        }

        return $exception->getMessage();
    }

    /**
     * @return array{class: class-string, code: int|string, file: string, line: int}
     */
    private function exceptionSummary(Throwable $exception): array
    {
        return [
            'class' => $exception::class,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }

    /**
     * PHP's own format for an uncaught exception, which Error Reporting parses,
     * built from the frames without their arguments (an argument can be a
     * password or a name).
     */
    private function stackTrace(Throwable $exception): string
    {
        $lines = [];
        foreach (array_slice($exception->getTrace(), 0, self::TRACE_FRAMES) as $i => $frame) {
            $where = isset($frame['file']) ? $frame['file'].'('.($frame['line'] ?? 0).')' : '[internal function]';
            $lines[] = '#'.$i.' '.$where.': '.($frame['class'] ?? '').($frame['type'] ?? '').$frame['function'].'()';
        }
        $lines[] = '#'.count($lines).' {main}';

        $message = $this->scrub($this->truncate($this->exceptionMessage($exception), self::MESSAGE_LIMIT));

        return 'PHP Fatal error:  Uncaught '.$exception::class.': '.$message
            .' in '.$exception->getFile().':'.$exception->getLine()."\n"
            ."Stack trace:\n".implode("\n", $lines)."\n"
            .'  thrown in '.$exception->getFile().' on line '.$exception->getLine();
    }

    private function throwingFunction(Throwable $exception): string
    {
        $frame = $exception->getTrace()[0] ?? null;

        return $frame === null ? '{main}' : ($frame['class'] ?? '').($frame['type'] ?? '').$frame['function'];
    }
}
