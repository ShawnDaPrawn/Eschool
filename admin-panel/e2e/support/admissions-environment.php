<?php

declare(strict_types=1);

// The repository CI uses this reserved database for Playwright. Ordinary local
// qualification uses *_test. A production environment is always refused.
return static fn (string $environment, string $database, bool $ci): bool => in_array($environment, ['local', 'testing'], true)
    && (str_ends_with($database, '_test') || ($ci && $database === 'eschool_e2e'));
