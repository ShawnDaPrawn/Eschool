<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;

/**
 * modules, notification_rules and feature_flags as Phase 0 answered them:
 * empty (the apps already read them).
 *
 * Stream T3 (School configuration) owns these keys: it replaces this class
 * with its own contributors (school_modules, notification_rules, feature
 * flags, profile fields) and removes it from config/context.php.
 */
class ConfigurationDefaultsContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'modules' => [],
            'notification_rules' => [],
            // An object, also when empty.
            'feature_flags' => (object) [],
        ];
    }
}
