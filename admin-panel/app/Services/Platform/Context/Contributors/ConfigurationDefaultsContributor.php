<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;

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
            'notification_rules' => app(NotificationRules::class)->context($scope->user),
            'feature_flags' => (object) ['module_switches' => true, 'academic_marks' => true, 'academic_timetable' => true, 'academic_registers' => true, 'academic_reports' => true, 'academic_content' => true],
        ];
    }
}
