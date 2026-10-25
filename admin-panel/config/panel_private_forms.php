<?php

use App\Services\Pastoral\SupportPanelScope;

// Private form text may be returned to its author in a no-store refusal page,
// but must never be copied into Laravel's plain session old-input flash.
return [
    ['paths' => ['support-plans', 'support-plans/*'], 'title' => 'Support plans', 'route' => 'support-plans.index', 'guard' => SupportPanelScope::class, 'fields' => ['needs' => ['label' => 'Support needs', 'limit' => 20000], 'accommodations' => ['label' => 'Accommodations', 'limit' => 20000], 'outcome' => ['label' => 'Review outcome', 'limit' => 10000], 'reason' => ['label' => 'Reason for this change', 'limit' => 2000], 'sias_stage' => ['label' => 'SIAS stage', 'limit' => 100]]],
    ['paths' => ['relief-cover', 'relief-cover/*'], 'title' => 'Relief cover', 'route' => 'relief-cover.index',
        'fields' => ['reason' => ['label' => 'Private reason', 'limit' => 500], 'starts_on' => ['label' => 'First absence date', 'limit' => 10], 'ends_on' => ['label' => 'Last absence date', 'limit' => 10]]],
    ['paths' => ['activity-groups', 'activity-groups/*'], 'title' => 'Activity groups', 'route' => 'activity-groups.index',
        'fields' => ['reason' => ['label' => 'Reason for this change', 'limit' => 2000], 'title' => ['label' => 'Title', 'limit' => 191], 'markdown' => ['label' => 'Material text', 'limit' => 20000]]],
];
