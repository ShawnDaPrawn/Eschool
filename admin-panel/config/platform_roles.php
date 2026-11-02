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
 *  packages.view    the product catalogue, packages and prices (read-only)
 *  packages.manage  create, edit, activate and retire packages; set their
 *                   modules and prices; re-apply a package to its schools
 *  identity.review  LURITS claims between schools: see and close them
 *  incidents.manage the breach and incident register: record, update, close
 *  support.manage   the support desk: every school's tickets, replies, internal notes, assignee, status
 *  mail.view        each school's SES mail streams, their status and rates
 *  mail.manage      set up or check a school's SES mail streams now
 *  flags.manage     feature flags: create, roll out, switch schools, kill
 *  announcements.manage announcements to schools: draft, publish, withdraw
 *  health.view      school health and usage, per school and compared (counts only)
 *  ai.view          the AI control centre: qualification, caps, spend, queue counts
 *  ai.manage        switch a school's AI features on or off (Owner only)
 *  ai.caps          set the AI spending caps that replace config/ai.php's defaults (Owner only)
 *  messaging.view   SMS and WhatsApp counts and pass-through cost per school
 *  billing.view     invoices, statements, and overdue school accounts
 *  billing.manage   issue, credit and settle school invoices
 *  support.access   ask a school for audited, time-limited, read-only access
 *  support.emergency open a pending request in an emergency (Owner only)
 *  audit.view       the platform audit log
 *  audit.export     export the platform audit log
 *  staff.manage     invite platform staff, change their role, revoke access
 */
return [
    'owner' => ['label' => 'Owner', 'abilities' => ['*']],
    'operations' => ['label' => 'Operations', 'abilities' => ['health.view', 'schools.view', 'schools.create', 'schools.package', 'schools.status', 'schools.modules', 'accounts.manage', 'packages.view', 'audit.view', 'identity.review', 'incidents.manage', 'announcements.manage', 'flags.manage', 'ai.view', 'support.access', 'billing.view', 'mail.view', 'mail.manage', 'support.manage', 'messaging.view']],
    'support' => ['label' => 'Support', 'abilities' => ['health.view', 'schools.view', 'audit.view', 'identity.review', 'incidents.manage', 'ai.view', 'support.access', 'mail.view', 'support.manage']],
    'finance' => ['label' => 'Finance', 'abilities' => ['health.view', 'schools.view', 'schools.package', 'accounts.manage', 'packages.view', 'packages.manage', 'audit.view', 'audit.export', 'billing.view', 'billing.manage', 'messaging.view']],
    'content' => ['label' => 'Content', 'abilities' => ['schools.view', 'packages.view']],
    'sales' => ['label' => 'Sales (read-only)', 'abilities' => ['schools.view', 'packages.view']],
];
