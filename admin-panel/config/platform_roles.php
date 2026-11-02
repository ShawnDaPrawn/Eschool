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
 *  billing.view     the billing page, statements and invoices (read-only)
 *  billing.manage   run, recompute, issue and discard invoices; credit notes;
 *                   record and reverse EFT payments; apply account credit;
 *                   send overdue reminders
 *  identity.review  LURITS claims between schools: see and close them
 *  incidents.manage the breach and incident register: record, update, close
 *  health.view      school health and usage, per school and compared (counts only)
 *  announcements.manage announcements to schools: draft, publish, withdraw
 *  support.access   ask a school for audited, time-limited, read-only access
 *                   and open it once the school's Super Admin approves (SA.9)
 *  support.emergency record an emergency override that opens a pending
 *                   support request at once and tells the school (Owner only)
 *  flags.manage     feature flags: create, roll out, switch schools, kill
 *  mail.view        each school's SES mail streams, their status and rates
 *  mail.manage      set up or check a school's SES mail streams now
 *  support.manage   the support desk: every school's tickets, replies, internal notes, assignee, status
 *  ai.view          the AI control centre: qualification, caps, spend, queue counts
 *  ai.manage        switch a school's AI features on or off (Owner only)
 *  ai.caps          set the AI spending caps that replace config/ai.php's defaults (Owner only)
 *  messaging.view   SMS and WhatsApp counts and pass-through cost per school
 *  health.view      platform health on the HQ home page: queue backlog, failed jobs, delivery failures
 *  library.manage   the platform content library: frameworks, topics, sources, items, publishing, reviewers, each school's library switch
 *  library.review   approve or reject library items, for the subjects the reviewer is assigned to
 *  audit.view       the platform audit log
 *  audit.export     export the platform audit log
 *  staff.manage     invite platform staff, change their role, revoke access
 */
return [
    'owner' => ['label' => 'Owner', 'abilities' => ['*']],
    'operations' => ['label' => 'Operations', 'abilities' => ['schools.view', 'schools.create', 'schools.package', 'schools.status', 'schools.modules', 'accounts.manage', 'packages.view', 'billing.view', 'audit.view', 'identity.review', 'incidents.manage', 'health.view', 'announcements.manage', 'support.access', 'flags.manage', 'ai.view', 'mail.view', 'mail.manage', 'support.manage', 'messaging.view']],
    'support' => ['label' => 'Support', 'abilities' => ['schools.view', 'audit.view', 'identity.review', 'incidents.manage', 'health.view', 'support.access', 'ai.view', 'mail.view', 'support.manage']],
    'finance' => ['label' => 'Finance', 'abilities' => ['schools.view', 'schools.package', 'accounts.manage', 'packages.view', 'packages.manage', 'billing.view', 'billing.manage', 'audit.view', 'audit.export', 'health.view', 'messaging.view']],
    'content' => ['label' => 'Content', 'abilities' => ['schools.view', 'packages.view', 'library.manage']],
    'reviewer' => ['label' => 'Content reviewer', 'abilities' => ['schools.view', 'library.review']],
    'sales' => ['label' => 'Sales (read-only)', 'abilities' => ['schools.view', 'packages.view']],
];
