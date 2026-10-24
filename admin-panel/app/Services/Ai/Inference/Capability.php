<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

enum Capability: string
{
    case Complete = 'complete';
    case Vision = 'vision';
    case Embed = 'embed';
}
