<?php

declare(strict_types=1);

namespace App\Services\Learning;

use Normalizer;
use RuntimeException;

/**
 * The local safeguarding phrase guard (Action Plan 3.2). Each platform
 * language has its own detection list in resources/ai/safeguarding/<code>.json,
 * grouped by the categories of the original English list. Every message is
 * checked against every list, reviewed or still draft, so a risk signal in any
 * language (or in a mix of languages) is caught locally before any AI call.
 *
 * Matching normalises the way people really write: case, diacritics,
 * apostrophes, hyphens, repeated letters, words run together or split apart,
 * and common digit look-alikes. A word in a list may carry a leading or
 * trailing * for languages that join markers to the verb (isiZulu).
 *
 * The phrases are never sent to an AI provider, never logged and never
 * audited: callers learn only whether, and in which category, a message
 * matched. engineering/specs/ai/safeguarding-phrases.md.
 */
final class SafeguardingPhrases
{
    /** Every platform language must have a list; a missing or broken list fails closed. */
    public const LANGUAGES = ['en' => 'English', 'af' => 'Afrikaans', 'zu' => 'isiZulu'];

    public const CATEGORIES = ['self_harm', 'abuse', 'sexual_violence'];

    /** The longest run of consecutive words a phrase may span once run together. */
    private const WINDOW = 8;

    /** A wildcard word must keep at least this many letters, so it stays specific. */
    private const MINIMUM_STEM = 5;

    /** @var array<string, array{name: string, sha256: string, categories: array<string, list<string>>, rules: list<array{category: string, compact: string|null, words: list<array{core: string, lead: bool, trail: bool}>}>}>|null */
    private static ?array $lists = null;

    /**
     * The categories a text matches, across every language's list.
     *
     * @return list<string>
     */
    public function categories(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }
        $forms = $this->forms($text);
        $matched = [];
        foreach (self::lists() as $list) {
            foreach ($list['rules'] as $rule) {
                if (! isset($matched[$rule['category']]) && $this->matches($rule, $forms)) {
                    $matched[$rule['category']] = true;
                }
            }
        }

        return array_values(array_intersect(self::CATEGORIES, array_keys($matched)));
    }

    public function caught(string $text): bool
    {
        return $this->categories($text) !== [];
    }

    /**
     * One language's list for HQ's read-only view and for its review hash.
     *
     * @return array{language: string, name: string, sha256: string, categories: array<string, list<string>>}
     */
    public function list(string $language): array
    {
        $lists = self::lists();
        abort_unless(isset($lists[$language]), 404);

        return ['language' => $language, 'name' => $lists[$language]['name'], 'sha256' => $lists[$language]['sha256'], 'categories' => $lists[$language]['categories']];
    }

    /** The hash a professional review is recorded against: any change to the list makes it draft again. */
    public function sha256(string $language): string
    {
        return $this->list($language)['sha256'];
    }

    /** Lower case, no diacritics, apostrophes or soft hyphens; anything else that is not a letter or digit is a space. */
    public static function normalise(string $text, bool $joinHyphens = false, string $one = 'i'): string
    {
        // Broken bytes never switch the guard off: they are replaced, and the rest is still read.
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = (string) mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        if (class_exists(Normalizer::class)) {
            $text = (string) Normalizer::normalize($text, Normalizer::FORM_KD);
            $text = (string) preg_replace('/\p{Mn}+/u', '', $text);
        }
        $text = mb_strtolower(strtr($text, ['ß' => 'ss', 'æ' => 'ae', 'ø' => 'o', 'đ' => 'd', 'ł' => 'l']), 'UTF-8');
        $text = (string) preg_replace("/['`\x{2018}\x{2019}\x{201B}\x{00B4}\x{02BC}\x{00AD}\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u", '', $text);
        if ($joinHyphens) {
            $text = (string) preg_replace('/(?<=\p{L})[-\x{2010}\x{2011}_.]+(?=\p{L})/u', '', $text);
        }
        // Look-alike digits and symbols, only inside a word that also has letters.
        $text = (string) preg_replace_callback('/[\p{L}\p{N}@$]*\p{L}[\p{L}\p{N}@$]*/u',
            fn (array $word) => strtr($word[0], ['0' => 'o', '1' => $one, '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's']), $text);
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim(self::collapse($text));
    }

    /** Repeated letters count once ("killl", "pleeease"), on both the list and the message. */
    private static function collapse(string $text): string
    {
        return (string) preg_replace('/(\p{L})\1+/u', '$1', $text);
    }

    /**
     * The message's words in each way it could be read, with every run of up
     * to WINDOW consecutive words joined, so "my self" and "myself" match alike.
     *
     * @return list<array{tokens: list<string>, joined: array<string, true>}>
     */
    private function forms(string $text): array
    {
        $variants = [];
        foreach ([false, true] as $join) {
            foreach (str_contains($text, '1') ? ['i', 'l'] : ['i'] as $one) {
                $variants[self::normalise($text, $join, $one)] = true;
            }
        }
        $forms = [];
        foreach (array_keys($variants) as $normalised) {
            $tokens = $normalised === '' ? [] : explode(' ', (string) $normalised);
            $joined = [];
            $count = count($tokens);
            for ($i = 0; $i < $count; $i++) {
                $run = '';
                for ($j = $i; $j < min($count, $i + self::WINDOW); $j++) {
                    $run .= $tokens[$j];
                    $joined[self::collapse($run)] = true;
                }
            }
            $forms[] = ['tokens' => $tokens, 'joined' => $joined];
        }

        return $forms;
    }

    /**
     * @param  array{category: string, compact: string|null, words: list<array{core: string, lead: bool, trail: bool}>}  $rule
     * @param  list<array{tokens: list<string>, joined: array<string, true>}>  $forms
     */
    private function matches(array $rule, array $forms): bool
    {
        foreach ($forms as $form) {
            if ($rule['compact'] !== null) {
                if (isset($form['joined'][$rule['compact']])) {
                    return true;
                }

                continue;
            }
            $tokens = $form['tokens'];
            $size = count($rule['words']);
            $count = count($tokens);
            for ($i = 0; $i + $size <= $count; $i++) {
                $all = true;
                foreach ($rule['words'] as $offset => $word) {
                    if (! $this->word($word, $tokens[$i + $offset])) {
                        $all = false;
                        break;
                    }
                }
                if ($all) {
                    return true;
                }
            }
            // A single joined-on word may also be split by a space or hyphen ("zi bulala").
            if ($size === 1) {
                for ($i = 0; $i + 1 < $count; $i++) {
                    if ($this->word($rule['words'][0], self::collapse($tokens[$i].$tokens[$i + 1]))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** @param  array{core: string, lead: bool, trail: bool}  $word */
    private function word(array $word, string $token): bool
    {
        return match (true) {
            $word['lead'] && $word['trail'] => str_contains($token, $word['core']),
            $word['lead'] => str_ends_with($token, $word['core']),
            $word['trail'] => str_starts_with($token, $word['core']),
            default => $token === $word['core'],
        };
    }

    /**
     * Every list, loaded once per process and validated: an unknown category,
     * an empty list or a too-short wildcard is refused rather than skipped.
     *
     * @return array<string, array{name: string, sha256: string, categories: array<string, list<string>>, rules: list<array{category: string, compact: string|null, words: list<array{core: string, lead: bool, trail: bool}>}>}>
     */
    private static function lists(): array
    {
        if (self::$lists !== null) {
            return self::$lists;
        }
        $lists = [];
        foreach (self::LANGUAGES as $language => $name) {
            $path = resource_path('ai/safeguarding/'.$language.'.json');
            $raw = is_file($path) ? file_get_contents($path) : false;
            if (! is_string($raw)) {
                throw new RuntimeException('A safeguarding phrase list is missing.');
            }
            $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
            if (! is_array($data) || ($data['language'] ?? null) !== $language || ! is_array($data['categories'] ?? null)
                || array_diff(array_keys($data['categories']), self::CATEGORIES) !== [] || array_diff(self::CATEGORIES, array_keys($data['categories'])) !== []) {
                throw new RuntimeException('A safeguarding phrase list is malformed.');
            }
            $categories = [];
            $rules = [];
            foreach (self::CATEGORIES as $category) {
                $phrases = $data['categories'][$category];
                if (! is_array($phrases) || $phrases === [] || ! array_is_list($phrases)) {
                    throw new RuntimeException('A safeguarding phrase list is malformed.');
                }
                foreach ($phrases as $phrase) {
                    if (! is_string($phrase)) {
                        throw new RuntimeException('A safeguarding phrase list is malformed.');
                    }
                    $categories[$category][] = $phrase;
                    $rules[] = self::rule($category, $phrase);
                }
            }
            $lists[$language] = ['name' => $name, 'sha256' => hash('sha256', json_encode($categories, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
                'categories' => $categories, 'rules' => $rules];
        }

        return self::$lists = $lists;
    }

    /** @return array{category: string, compact: string|null, words: list<array{core: string, lead: bool, trail: bool}>} */
    private static function rule(string $category, string $phrase): array
    {
        $words = [];
        $wildcard = false;
        foreach (preg_split('/\s+/u', trim($phrase)) ?: [] as $raw) {
            $lead = str_starts_with($raw, '*');
            $trail = strlen($raw) > 1 && str_ends_with($raw, '*');
            $parts = array_values(array_filter(explode(' ', self::normalise(trim($raw, '*'))), fn ($part) => $part !== ''));
            foreach ($parts as $index => $core) {
                $word = ['core' => $core, 'lead' => $lead && $index === 0, 'trail' => $trail && $index === count($parts) - 1];
                $wildcard = $wildcard || $word['lead'] || $word['trail'];
                $words[] = $word;
            }
        }
        if ($words === [] || mb_strlen(implode('', array_column($words, 'core'))) < 4) {
            throw new RuntimeException('A safeguarding phrase is too short.');
        }
        // A lone wildcard word needs a longer stem than one anchored by other words.
        $minimum = count($words) === 1 ? self::MINIMUM_STEM : self::MINIMUM_STEM - 1;
        foreach ($words as $word) {
            if (($word['lead'] || $word['trail']) && mb_strlen($word['core']) < $minimum) {
                throw new RuntimeException('A safeguarding phrase wildcard is too short.');
            }
        }

        return ['category' => $category, 'compact' => $wildcard ? null : self::collapse(implode('', array_column($words, 'core'))), 'words' => $words];
    }
}
