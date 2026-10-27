<?php

use App\Services\Admissions\IntakePanelScope;
use App\Services\Ai\Quarantine\AiReviewPanelScope;
use App\Services\Assistance\AssistancePanelScope;
use App\Services\Consultations\ConsultationPanelScope;
use App\Services\Discipline\DisciplinePanelScope;
use App\Services\Gallery\GalleryPanelScope;
use App\Services\Growth\ProgressPanelScope;
use App\Services\Growth\RiskPanelScope;
use App\Services\Learning\Digests\DigestPanelScope;
use App\Services\Pastoral\SupportPanelScope;
use App\Services\Recognition\RecognitionPanelScope;
use App\Services\Transfers\TransferPanelScope;
use App\Services\Wellbeing\WellbeingPanelScope;

// Private form text may be returned to its author in a no-store refusal page,
// but must never be copied into Laravel's plain session old-input flash.
return [
    ['paths' => ['fee-reminders', 'fee-reminders/*'], 'title' => 'Fee reminder schedule', 'route' => 'fee-reminders.index', 'fields' => []],
    ['paths' => ['fee-reporting', 'fee-reporting/*'], 'title' => 'Arrears and accounting', 'route' => 'fee-reporting.index', 'fields' => []],
    ['paths' => ['bank-statements', 'bank-statements/*'], 'title' => 'Bank statement matching', 'route' => 'bank-statements.index', 'fields' => []],
    ['paths' => ['fee-items', 'fee-items/*', 'fee-item-charges', 'fee-item-charges/*', 'fee-item-batches', 'fee-item-batches/*'], 'title' => 'Fee items and charges', 'route' => 'fee-items.index', 'fields' => []],
    ['paths' => ['fee-credit-notes', 'fee-credit-notes/*'], 'title' => 'Fee credit notes', 'route' => 'fee-credit-notes.index', 'fields' => []],
    ['paths' => ['fee-payments', 'fee-payments/*'], 'title' => 'Fee payments', 'route' => 'fee-payments.index', 'fields' => []],
    ['paths' => ['fee-plans', 'fee-plans/*'], 'title' => 'Fee plans', 'route' => 'fee-plans.index', 'fields' => []],
    ['paths' => ['fee-accounts', 'fee-accounts/*'], 'title' => 'Family accounts', 'route' => 'fee-accounts.index', 'fields' => []],
    ['paths' => ['fee-bill-runs', 'fee-bill-runs/*', 'fee-invoices/*'], 'title' => 'Bill runs', 'route' => 'fee-bill-runs.index', 'fields' => []],
    ['paths' => ['ask-school', 'ask-school/*'], 'title' => 'School support office', 'route' => 'assistance-panel.index', 'guard' => AssistancePanelScope::class,
        'fields' => ['text' => ['label' => 'Your reply draft', 'limit' => 4000], 'reason' => ['label' => 'Your handoff reason draft', 'limit' => 1000], 'title' => ['label' => 'Document title draft', 'limit' => 191], 'markdown' => ['label' => 'Approved school text draft', 'limit' => 16000], 'source_reference' => ['label' => 'Source reference draft', 'limit' => 2048]]],
    ['paths' => ['ai-flags', 'ai-flags/*'], 'title' => 'Flagged learning content', 'route' => 'ai-review.index', 'guard' => AiReviewPanelScope::class, 'fields' => ['reason' => ['label' => 'Your private review note', 'limit' => 2000]]],
    ['paths' => ['weekly-digest-templates', 'weekly-digest-templates/*'], 'title' => 'Weekly digest templates', 'route' => 'digest-panel.index', 'guard' => DigestPanelScope::class, 'fields' => ['title' => ['label' => 'Digest title draft', 'limit' => 120], 'body' => ['label' => 'Digest wording draft', 'limit' => 1200]]],
    ['paths' => ['school-gallery', 'school-gallery/*'], 'title' => 'Private photos', 'route' => 'gallery-panel.index', 'guard' => GalleryPanelScope::class, 'fields' => ['caption' => ['label' => 'Your private caption draft', 'limit' => 2000], 'handle' => ['label' => 'Approved learner handle', 'limit' => 21]]],
    ['paths' => ['school-consultations', 'school-consultations/*'], 'title' => 'Polls, surveys and elections', 'route' => 'consultations-panel.index', 'guard' => ConsultationPanelScope::class, 'fields' => []],
    ['paths' => ['admissions', 'admissions/*'], 'title' => 'Admissions', 'route' => 'admissions-panel.index', 'guard' => IntakePanelScope::class, 'fields' => ['reason' => ['label' => 'Review reason', 'limit' => 2000], 'reference' => ['label' => 'Office receipt reference', 'limit' => 191], 'name' => ['label' => 'Intake name', 'limit' => 160]]],
    ['paths' => ['school-discipline', 'school-discipline/*'], 'title' => 'School discipline', 'route' => 'discipline-panel.index', 'guard' => DisciplinePanelScope::class,
        'fields' => ['description' => ['label' => 'Your factual description draft', 'limit' => 2000], 'staff_note' => ['label' => 'Your private staff note draft', 'limit' => 2000], 'reason' => ['label' => 'Your review reason draft', 'limit' => 1000], 'family_reason' => ['label' => 'Your family-facing reason draft', 'limit' => 1000], 'location' => ['label' => 'Your supervised location draft', 'limit' => 200]]],
    ['paths' => ['recognition', 'recognition/*'], 'title' => 'Recognition', 'route' => 'home', 'guard' => RecognitionPanelScope::class, 'fields' => ['reason' => ['label' => 'Reason for this change', 'limit' => 4000], 'criteria' => ['label' => 'Award criteria', 'limit' => 4000], 'title' => ['label' => 'Award title', 'limit' => 160], 'event' => ['label' => 'Assembly or event', 'limit' => 191], 'outside_organisation' => ['label' => 'Outside organisation', 'limit' => 160]]],
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
