<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Only explicit local validation/qualification failures before HTTP may use this type. */
class InferenceNotDispatched extends AiUnavailable {}
