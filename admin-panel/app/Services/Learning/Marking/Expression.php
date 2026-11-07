<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use UnexpectedValueException;

/**
 * A small, bounded algebra reader for marking (Action Plan 3.16): numbers,
 * variables, + − × ÷ ^, brackets, implicit multiplication (2x, 3(x + 1)), π
 * and a few functions (sqrt, sin, cos, tan, ln, log, abs). Nothing is executed:
 * the text becomes a tree that is evaluated with chosen values.
 *
 * Two identifier styles follow the subject: "letters" (Mathematics: xy is
 * x times y) and "symbols" (Physical Sciences: vf, Fnet and Ek are one
 * quantity each; v_f is the same as vf).
 *
 * Two expressions are equivalent when they agree at ten fixed sets of values
 * (positive and negative), ignoring points where either is undefined; at least
 * four must be defined. Equations are equivalent when one is a non-zero multiple
 * of the other, or when one is solved for a variable and substituting it makes
 * the other true (v = d/t and d = vt).
 */
final class Expression
{
    public const FUNCTIONS = ['sqrt', 'sin', 'cos', 'tan', 'ln', 'log', 'abs'];

    private const MAX_TOKENS = 160;

    private const MAX_DEPTH = 32;

    private const MAX_VARIABLES = 8;

    private const SAMPLES = [1.37, 2.71, 0.61, 3.19, 1.93, -1.41, -2.29, 0.83, 2.47, -0.77];

    private array $tokens = [];

    private int $at = 0;

    private int $depth = 0;

    /** @param list<string> $vocabulary quantity names to split a run of letters by, in the symbols style */
    private function __construct(private readonly string $style, private readonly array $vocabulary = []) {}

    /**
     * The tree of an expression, or null when it cannot be read. In the symbols style a
     * run of letters is one quantity, unless it is made of the given quantity names
     * (the memo's): with m, g and h known, mgh is m times g times h.
     *
     * @param  list<string>  $vocabulary
     */
    public static function parse(string $text, string $style = 'letters', array $vocabulary = []): ?array
    {
        usort($vocabulary, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $parser = new self($style === 'symbols' ? 'symbols' : 'letters', $vocabulary);
        $tokens = $parser->tokenize($text);
        if ($tokens === null || $tokens === [] || count($tokens) > self::MAX_TOKENS) {
            return null;
        }
        $parser->tokens = $tokens;
        try {
            $tree = $parser->sum();
        } catch (UnexpectedValueException) {
            return null;
        }

        return $parser->at === count($tokens) ? $tree : null;
    }

    /** Both sides of an equation with exactly one equals sign, or null. @return array{0: array, 1: array}|null */
    public static function equation(string $text, string $style = 'letters', array $vocabulary = []): ?array
    {
        $sides = preg_split('/(?<![<>!])=/', AnswerText::normalize($text));
        if (count($sides) !== 2) {
            return null;
        }
        $left = self::parse($sides[0], $style, $vocabulary);
        $right = self::parse($sides[1], $style, $vocabulary);

        return $left === null || $right === null ? null : [$left, $right];
    }

    /** @return list<array> */
    private function tokenize(string $text): ?array
    {
        $text = AnswerText::normalize($text);
        $text = (string) preg_replace('/(?<=\d) (?=\d{3}(?!\d))/', '', $text);
        $tokens = [];
        $offset = 0;
        $length = strlen($text);
        while ($offset < $length) {
            if (! preg_match('/\G(?:(?<space>\s+)|(?<number>\d+(?:[.,]\d+)?(?:[eE][-+]?\d{1,3}(?![\p{L}\d]))?)|(?<word>[\p{L}µ_][\p{L}\d_]*)|(?<op>[-+*\/^()\[\]×·]))/u', $text, $m, 0, $offset)) {
                return null;
            }
            $offset += strlen($m[0]);
            if (($m['number'] ?? '') !== '') {
                $tokens[] = ['n', (float) str_replace(',', '.', $m['number'])];
            } elseif (($m['word'] ?? '') !== '') {
                foreach ($this->words($m['word']) as $token) {
                    $tokens[] = $token;
                }
            } elseif (($m['op'] ?? '') !== '') {
                $tokens[] = ['o', strtr($m['op'], ['×' => '*', '·' => '*', '[' => '(', ']' => ')'])];
            }
            if (count($tokens) > self::MAX_TOKENS) {
                return null;
            }
        }

        return $tokens;
    }

    /** Split a run of letters into functions, constants and variables for the subject's style. */
    private function words(string $word): array
    {
        $tokens = [];
        while ($word !== '') {
            if (preg_match('/^\d+/', $word, $digits)) {
                // sqrt16 (from √16): the function's number.
                $tokens[] = ['n', (float) $digits[0]];
                $word = substr($word, strlen($digits[0]));

                continue;
            }
            foreach ([...self::FUNCTIONS, 'pi'] as $name) {
                if (str_starts_with($word, $name)) {
                    $tokens[] = $name === 'pi' ? ['n', M_PI] : ['f', $name];
                    $word = substr($word, strlen($name));

                    continue 2;
                }
            }
            if ($this->style === 'symbols') {
                // One quantity: letters, digits and subscripts together (vf, v_f, Fnet, x1).
                // A Greek letter starts a new quantity: aΔx is a and Δx.
                foreach (preg_split('/(?<=.)(?=[\x{0370}-\x{03FF}])/u', str_replace('_', '', $word)) ?: [] as $run) {
                    foreach ($this->quantities($run) as $name) {
                        $tokens[] = $name === 'π' ? ['n', M_PI] : ['v', $name];
                    }
                }

                return $tokens;
            }
            $letter = mb_substr($word, 0, 1);
            $word = mb_substr($word, 1);
            if ($letter === '_') {
                continue;
            }
            if (preg_match('/^\d+/', $word, $digits)) {
                // x2 in Mathematics is x times 2.
                $tokens[] = $letter === 'π' ? ['n', M_PI] : ['v', $letter];
                $tokens[] = ['n', (float) $digits[0]];
                $word = substr($word, strlen($digits[0]));

                continue;
            }
            if (str_starts_with($word, '_') && preg_match('/^_([\p{L}\d]+)/u', $word, $sub)) {
                $tokens[] = ['v', $letter.'_'.$sub[1]];
                $word = substr($word, strlen($sub[0]));

                continue;
            }
            $tokens[] = $letter === 'π' ? ['n', M_PI] : ['v', $letter];
        }

        return $tokens;
    }

    /** @return list<string> */
    private function quantities(string $run): array
    {
        if ($this->vocabulary === [] || in_array($run, $this->vocabulary, true)) {
            return [$run];
        }
        $parts = [];
        $rest = $run;
        while ($rest !== '') {
            foreach ($this->vocabulary as $name) {
                if ($name !== '' && str_starts_with($rest, $name)) {
                    $parts[] = $name;
                    $rest = substr($rest, strlen($name));

                    continue 2;
                }
            }

            return [$run];
        }

        return $parts;
    }

    private function enter(): void
    {
        if (++$this->depth > self::MAX_DEPTH) {
            throw new UnexpectedValueException('too deep');
        }
    }

    private function op(string $op): bool
    {
        $token = $this->tokens[$this->at] ?? null;

        return $token !== null && $token[0] === 'o' && $token[1] === $op;
    }

    private function sum(): array
    {
        $this->enter();
        $left = $this->product();
        while ($this->op('+') || $this->op('-')) {
            $op = $this->tokens[$this->at++][1];
            $left = [$op, $left, $this->product()];
        }
        $this->depth--;

        return $left;
    }

    private function product(): array
    {
        $left = $this->unary();
        while (true) {
            if ($this->op('*') || $this->op('/')) {
                $op = $this->tokens[$this->at++][1];
                $left = [$op, $left, $this->unary()];
            } elseif ($this->startsPrimary()) {
                $left = ['*', $left, $this->power()];
            } else {
                return $left;
            }
        }
    }

    private function startsPrimary(): bool
    {
        $token = $this->tokens[$this->at] ?? null;

        return $token !== null && ($token[0] !== 'o' || $token[1] === '(');
    }

    private function unary(): array
    {
        if ($this->op('-') || $this->op('+')) {
            $negative = $this->tokens[$this->at++][1] === '-';
            $this->enter();
            $value = $this->unary();
            $this->depth--;

            return $negative ? ['neg', $value] : $value;
        }

        return $this->power();
    }

    private function power(): array
    {
        $base = $this->primary();
        if ($this->op('^')) {
            $this->at++;
            $this->enter();
            $exponent = $this->unary();
            $this->depth--;

            return ['^', $base, $exponent];
        }

        return $base;
    }

    private function primary(): array
    {
        $token = $this->tokens[$this->at] ?? throw new UnexpectedValueException('end');
        if ($token[0] === 'n' || $token[0] === 'v') {
            $this->at++;

            return $token;
        }
        if ($token[0] === 'f') {
            $this->at++;
            $this->enter();
            $argument = $this->op('(') ? $this->primary() : $this->power();
            $this->depth--;

            return ['f', $token[1], $argument];
        }
        if ($this->op('(')) {
            $this->at++;
            $inner = $this->sum();
            if (! $this->op(')')) {
                throw new UnexpectedValueException('bracket');
            }
            $this->at++;

            return $inner;
        }
        throw new UnexpectedValueException('token');
    }

    /** The value of a tree for the given variable values; NAN where it is undefined. */
    public static function evaluate(array $node, array $values = []): float
    {
        $result = match ($node[0]) {
            'n' => (float) $node[1],
            'v' => (float) ($values[$node[1]] ?? NAN),
            'neg' => -self::evaluate($node[1], $values),
            '+' => self::evaluate($node[1], $values) + self::evaluate($node[2], $values),
            '-' => self::evaluate($node[1], $values) - self::evaluate($node[2], $values),
            '*' => self::evaluate($node[1], $values) * self::evaluate($node[2], $values),
            '/' => self::divide(self::evaluate($node[1], $values), self::evaluate($node[2], $values)),
            '^' => self::power2(self::evaluate($node[1], $values), self::evaluate($node[2], $values)),
            'f' => self::call($node[1], self::evaluate($node[2], $values)),
            default => NAN,
        };

        return is_finite($result) ? $result : NAN;
    }

    private static function divide(float $a, float $b): float
    {
        return $b == 0.0 ? NAN : $a / $b;
    }

    private static function power2(float $base, float $exponent): float
    {
        if (is_nan($base) || is_nan($exponent) || abs($exponent) > 64 || ($base < 0 && floor($exponent) != $exponent) || ($base == 0.0 && $exponent < 0)) {
            return NAN;
        }

        return $base ** $exponent;
    }

    private static function call(string $name, float $x): float
    {
        return match ($name) {
            'sqrt' => $x < 0 ? NAN : sqrt($x),
            'sin' => sin($x), 'cos' => cos($x), 'tan' => tan($x),
            'ln' => $x <= 0 ? NAN : log($x),
            'log' => $x <= 0 ? NAN : log10($x),
            'abs' => abs($x),
            default => NAN,
        };
    }

    /** The variable names in a tree. @return list<string> */
    public static function variables(array $node): array
    {
        $found = [];
        $walk = function (array $n) use (&$walk, &$found) {
            if ($n[0] === 'v') {
                $found[$n[1]] = true;
            } elseif ($n[0] === 'f') {
                $walk($n[2]);
            } elseif ($n[0] !== 'n') {
                foreach (array_slice($n, 1) as $child) {
                    $walk($child);
                }
            }
        };
        $walk($node);

        return array_keys($found);
    }

    /** The value of a tree without variables, or null. */
    public static function constant(array $node): ?float
    {
        if (self::variables($node) !== []) {
            return null;
        }
        $value = self::evaluate($node);

        return is_nan($value) ? null : $value;
    }

    /** @return list<array<string, float>> */
    private static function samples(array $variables): array
    {
        $sets = [];
        foreach (array_keys(self::SAMPLES) as $trial) {
            $values = [];
            foreach (array_values($variables) as $i => $name) {
                $values[$name] = self::SAMPLES[($trial + 3 * $i) % count(self::SAMPLES)] + 0.11 * $i;
            }
            $sets[] = $values;
        }

        return $sets;
    }

    /** Agreement relative to the values compared, with no absolute floor, so tiny constants (6,63 × 10^-34) still count. */
    public static function close(float $a, float $b): bool
    {
        return $a === $b || abs($a - $b) <= 1e-7 * max(abs($a), abs($b));
    }

    public static function equivalent(array $a, array $b): bool
    {
        $variables = array_values(array_unique([...self::variables($a), ...self::variables($b)]));
        if (count($variables) > self::MAX_VARIABLES) {
            return false;
        }
        $defined = 0;
        foreach (self::samples($variables) as $values) {
            $x = self::evaluate($a, $values);
            $y = self::evaluate($b, $values);
            if (is_nan($x) || is_nan($y)) {
                continue;
            }
            if (! self::close($x, $y)) {
                return false;
            }
            $defined++;
        }

        return $defined >= 4;
    }

    /** Whether two equations say the same thing. @param array{0: array, 1: array} $memo @param array{0: array, 1: array} $learner */
    public static function equationsEquivalent(array $memo, array $learner): bool
    {
        $variables = array_values(array_unique([...self::variables($memo[0]), ...self::variables($memo[1]), ...self::variables($learner[0]), ...self::variables($learner[1])]));
        if ($variables === [] || count($variables) > self::MAX_VARIABLES) {
            return false;
        }
        if (self::proportional($memo, $learner, $variables)) {
            return true;
        }
        foreach ([[$memo, $learner], [$learner, $memo]] as [$solved, $other]) {
            foreach ([[0, 1], [1, 0]] as [$side, $rest]) {
                if ($solved[$side][0] === 'v' && ! in_array($solved[$side][1], self::variables($solved[$rest]), true)
                    && self::holdsWhen($solved[$side][1], $solved[$rest], $other, $variables)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function proportional(array $memo, array $learner, array $variables): bool
    {
        $ratio = null;
        $defined = 0;
        foreach (self::samples($variables) as $values) {
            $a = self::evaluate($memo[0], $values) - self::evaluate($memo[1], $values);
            $b = self::evaluate($learner[0], $values) - self::evaluate($learner[1], $values);
            if (is_nan($a) || is_nan($b)) {
                continue;
            }
            if (abs($a) < 1e-12) {
                if (abs($b) > 1e-9) {
                    return false;
                }

                continue;
            }
            $r = $b / $a;
            if ($ratio !== null && ! self::close($r, $ratio)) {
                return false;
            }
            $ratio ??= $r;
            $defined++;
        }

        return $defined >= 4 && $ratio !== null && abs($ratio) > 1e-9;
    }

    /** Whether the other equation holds whenever $name equals $value, and not always. */
    private static function holdsWhen(string $name, array $value, array $other, array $variables): bool
    {
        $defined = 0;
        $alwaysTrue = true;
        foreach (self::samples($variables) as $values) {
            $free = [self::evaluate($other[0], $values), self::evaluate($other[1], $values)];
            if (! is_nan($free[0]) && ! is_nan($free[1]) && ! self::close($free[0], $free[1])) {
                $alwaysTrue = false;
            }
            $values[$name] = self::evaluate($value, $values);
            if (is_nan($values[$name])) {
                continue;
            }
            $left = self::evaluate($other[0], $values);
            $right = self::evaluate($other[1], $values);
            if (is_nan($left) || is_nan($right)) {
                continue;
            }
            if (! self::close($left, $right)) {
                return false;
            }
            $defined++;
        }

        return $defined >= 4 && ! $alwaysTrue;
    }

    /**
     * The written form, for subjects that do not accept equivalent forms: the
     * tokens in order with explicit multiplication signs left out, so 2*x and
     * 2x match but 2*3 and 23 never do (each number stays one token). Each side
     * of an equation is read on its own. Text that cannot be read keeps its
     * spacing, with multiplication signs unified.
     */
    public static function written(string $text): string
    {
        $sides = [];
        foreach (preg_split('/(?<![<>!])=/', AnswerText::normalize($text)) ?: [] as $side) {
            $tokens = (new self('letters'))->tokenize($side);
            if ($tokens === null || $tokens === []) {
                return 'text:'.trim((string) preg_replace(['/\s+/u', '/\s*[*×·]\s*/u'], [' ', '*'], AnswerText::normalize($text)));
            }
            $parts = [];
            foreach ($tokens as $token) {
                if ($token === ['o', '*']) {
                    continue;
                }
                $parts[] = $token[0].':'.($token[0] === 'n' ? var_export((float) $token[1], true) : $token[1]);
            }
            $sides[] = implode(' ', $parts);
        }

        return implode(' = ', $sides);
    }
}
