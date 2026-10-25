<?php

use App\Services\Growth\ProgressPanelScope;
use App\Services\Growth\RiskPanelScope;
use App\Services\Pastoral\SupportPanelScope;
use App\Services\Transfers\TransferPanelScope;
use App\Services\Wellbeing\WellbeingPanelScope;

// Private form text may be returned to its author in a no-store refusal page,
// but must never be copied into Laravel's plain session old-input flash.
return [
    ['paths' => ['risk-interventions', 'risk-interventions/*'], 'title' => 'Early warning and interventions', 'route' => 'risk-interventions.index', 'guard' => RiskPanelScope::class,
        'fields' => ['action' => ['label' => 'Your intervention action draft', 'limit' => 20000], 'outcome' => ['label' => 'Your review outcome draft', 'limit' => 20000], 'reason' => ['label' => 'Your handoff reason draft', 'limit' => 1000]]],
    ['paths' => ['school-transfers', 'school-transfers/*'], 'title' => 'School transfers', 'route' => 'school-transfers.index', 'guard' => TransferPanelScope::class, 'fields' => ['external_school' => ['label' => 'Receiving school', 'limit' => 160]]],
    ['paths' => ['wellbeing', 'wellbeing/*'], 'title' => 'Incidents and counselling', 'route' => 'wellbeing.index', 'guard' => WellbeingPanelScope::class,
        'fields' => ['body' => ['label' => 'Private text', 'limit' => 20000], 'notes' => ['label' => 'Appointment notes', 'limit' => 20000], 'reason' => ['label' => 'Reason for this change', 'limit' => 2000],
            'location' => ['label' => 'Appointment location', 'limit' => 500], 'name' => ['label' => 'Category name', 'limit' => 191], 'emergency_guidance' => ['label' => 'Emergency guidance', 'limit' => 2000]]],
    ['paths' => ['learner-notes', 'learner-notes/*'], 'title' => 'Learner notes', 'route' => 'learner-notes.index', 'guard' => ProgressPanelScope::class,
        'fields' => ['body' => ['label' => 'Your private note draft', 'limit' => 20000]]],
    ['paths' => ['support-plans', 'support-plans/*'], 'title' => 'Support plans', 'route' => 'support-plans.index', 'guard' => SupportPanelScope::class, 'fields' => ['needs' => ['label' => 'Support needs', 'limit' => 20000], 'accommodations' => ['label' => 'Accommodations', 'limit' => 20000], 'outcome' => ['label' => 'Review outcome', 'limit' => 10000], 'reason' => ['label' => 'Reason for this change', 'limit' => 2000], 'sias_stage' => ['label' => 'SIAS stage', 'limit' => 100]]],
    ['paths' => ['relief-cover', 'relief-cover/*'], 'title' => 'Relief cover', 'route' => 'relief-cover.index',
        'fields' => ['reason' => ['label' => 'Private reason', 'limit' => 500], 'starts_on' => ['label' => 'First absence date', 'limit' => 10], 'ends_on' => ['label' => 'Last absence date', 'limit' => 10]]],
    ['paths' => ['activity-groups', 'activity-groups/*'], 'title' => 'Activity groups', 'route' => 'activity-groups.index',
        'fields' => ['reason' => ['label' => 'Reason for this change', 'limit' => 2000], 'title' => ['label' => 'Title', 'limit' => 191], 'markdown' => ['label' => 'Material text', 'limit' => 20000]]],
];
