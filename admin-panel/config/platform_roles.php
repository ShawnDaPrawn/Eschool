<?php

declare(strict_types=1);

/*
 * Platform staff roles (Action Plan SA.1). Each HQ admin grant carries one of
 * these; PlatformStaffAccess::can() checks the ability named on every /saas
 * route (the platform.can middleware). Owner holds every ability. Only Owners
 * manage platform staff. engineering/specs/platform/platform-roles.md.
 *
 * Abilities:
 *  schools.view     the school list and each school's page (counts only)
 *  schools.create   set up a new school and its first administrator
 *  schools.package  change a school's package
 *  schools.status   suspend, archive or restore a school
 *  schools.modules  switch a school's modules
 *  accounts.manage  customer records: stage, contacts, contracts, notes
 *  packages.view    the product catalogue and packages
 *  identity.review  LURITS claims between schools: see and close them
 *  incidents.manage the breach and incident register: record, update, close
 *  ai.view          the AI control centre: qualification, caps, spend, queue counts
 *  ai.manage        switch a school's AI features on or off (Owner only)
 *  ai.caps          set the AI spending caps that replace config/ai.php's defaults (Owner only)
 *  offboarding.view    a leaving school's offboarding: states, dates and counts
 *  offboarding.manage  start an offboarding, archive the school, run the deletion dry run, ask for deletion, place a legal hold
 *  offboarding.approve the second approval of a school's deletion, and lifting a legal hold (Owner only)
 *  audit.view       the platform audit log
 *  audit.export     export the platform audit log
 *  staff.manage     invite platform staff, change their role, revoke access
 */
return [
    'owner' => ['label' => 'Owner', 'abilities' => ['*']],
    'operations' => ['label' => 'Operations', 'abilities' => ['schools.view', 'schools.create', 'schools.package', 'schools.status', 'schools.modules', 'accounts.manage', 'packages.view', 'audit.view', 'identity.review', 'incidents.manage', 'ai.view', 'offboarding.view', 'offboarding.manage']],
    'support' => ['label' => 'Support', 'abilities' => ['schools.view', 'audit.view', 'identity.review', 'incidents.manage', 'ai.view']],
    'finance' => ['label' => 'Finance', 'abilities' => ['schools.view', 'schools.package', 'accounts.manage', 'packages.view', 'audit.view', 'audit.export']],
    'content' => ['label' => 'Content', 'abilities' => ['schools.view', 'packages.view']],
    'sales' => ['label' => 'Sales (read-only)', 'abilities' => ['schools.view', 'packages.view']],
];
