<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Services\Learning\Marking\Accuracy\IdentifyingData;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use Illuminate\Validation\ValidationException;

/**
 * Keeps identifying data out of what a school shares (Action Plan 5.4): a
 * question offered to the library and a rating's comment. It refuses an
 * e-mail address, a South African identity number or a phone number (the
 * patterns IdentifyingData uses for evaluation scripts), and the first name
 * or surname of any current learner on the school's roster, as a whole word.
 * Runs inside the school whose roster it reads; nothing is stored.
 */
class SharedLibraryScan
{
    /** Names shorter than this are too common as ordinary words to match safely. */
    private const MIN_NAME = 3;

    /** @var list<string>|null */
    private ?array $names = null;

    /**
     * Refuse the texts (a validation error on $field) when any holds identifying data.
     *
     * @param  array<mixed>  $texts  strings, or nested arrays of strings (a question definition)
     */
    public function refuse(array $texts, string $field): void
    {
        $found = $this->find($texts);
        if ($found !== null) {
            throw ValidationException::withMessages([$field => $found]);
        }
    }

    /** @param  array<mixed>  $texts */
    public function find(array $texts): ?string
    {
        $strings = [];
        array_walk_recursive($texts, function ($value) use (&$strings) {
            if (is_string($value) && trim($value) !== '') {
                $strings[] = $value;
            }
        });
        foreach ($strings as $text) {
            if (($kind = IdentifyingData::find($text)) !== null) {
                return __('This contains :what. What you share never identifies anyone.', ['what' => IdentifyingData::describe($kind)]);
            }
        }
        foreach (array_chunk($this->names(), 200) as $chunk) {
            $pattern = '/(?<![\pL\pN])('.implode('|', array_map(fn ($name) => preg_quote($name, '/'), $chunk)).')(?![\pL\pN])/iu';
            foreach ($strings as $text) {
                if (preg_match($pattern, $text, $match) === 1) {
                    return __('This contains the name ":name", the name of a learner at your school. What you share never names a learner: use another name.', ['name' => $match[1]]);
                }
            }
        }

        return null;
    }

    /** @return list<string> the current learners' first names and surnames */
    private function names(): array
    {
        if ($this->names !== null) {
            return $this->names;
        }
        $names = [];
        foreach (app(CurrentLearnerMembership::class)->users()->limit(20000)->get(['first_name', 'last_name']) as $user) {
            foreach ([$user->first_name, $user->last_name] as $name) {
                foreach (preg_split('/[\s,]+/u', trim((string) $name)) ?: [] as $part) {
                    $part = trim($part, " \t\n\r\0\x0B.'-");
                    if (mb_strlen($part) >= self::MIN_NAME) {
                        $names[mb_strtolower($part)] = $part;
                    }
                }
            }
        }

        return $this->names = array_values($names);
    }
}
