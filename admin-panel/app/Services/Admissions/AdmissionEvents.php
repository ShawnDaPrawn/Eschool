<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationEvent;
use App\Models\User;
use App\Services\Platform\Audit\Audit;

class AdmissionEvents
{
    public function record(Application $row, string $kind, ?User $actor = null, array $details = []): void
    {
        ApplicationEvent::create(['application_id' => $row->id, 'actor_id' => $actor?->id, 'kind' => $kind, 'revision' => $row->revision, 'details' => $details]);
        Audit::record('admissions.'.$kind, $row, [], ['revision' => (int) $row->revision]);
    }
}
