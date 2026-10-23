<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Modules\ModuleGate;

/**
 * School module availability and configuration capabilities. Notification
 * rules are populated by their own configuration service as it arrives.
 */
class ConfigurationDefaultsContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'modules' => app(ModuleGate::class)->context(),
            'notification_rules' => [],
            'feature_flags' => (object) ['module_switches' => true],
        ];
    }
}
