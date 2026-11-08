<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests\Marking;

/** The outcome for one answer: marked now (correct is known), or waiting for a marker that does not exist yet. */
final class TestMark
{
    public const AUTO = 'auto';

    public const AWAITING_MARKER = 'awaiting_marker';

    private function __construct(public readonly string $marking, public readonly ?bool $correct) {}

    public static function marked(bool $correct): self
    {
        return new self(self::AUTO, $correct);
    }

    public static function awaiting(): self
    {
        return new self(self::AWAITING_MARKER, null);
    }
}
