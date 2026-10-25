<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/** Private learner projections and graph receipts must forbid caches on every method. */
class PrivateMastery extends PrivateDomainResponse {}
