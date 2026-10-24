# Phase 2 decisions

Proceed with the agreed grade-to-phase mapping (R–3 Foundation, 4–6 Intermediate,
7–9 Senior, 10–12 FET) and the seven-point percentage reference. Grades are
explicitly mapped by authorized school administrators; existing class names are
never parsed to guess a grade. Unmapped classes remain valid legacy classes.

Pending before the affected feature: confirmed CAPS/IEB subject standards and
SBA/exam ratios; rounding, absent/excused/catch-up and mid-year mark rules; term
and report calendars; moderation authority and report sign-off; pilot school,
import sources and reconciliation tolerances; reviewed translations and devices.
Do not invent these choices or hold unrelated development while they are pending.

The first reference catalogue does not apply a new rating to existing marks,
change learner promotion, or calculate term/year results. Percentage thresholds
use integer basis points (100 basis points = 1 percent) to cover decimal marks
without gaps or floating-point boundary ambiguity.

The owner confirmed no installed semester clients and authorized retirement on
23 October. Terms replace that runtime in place; no v1 bridge is built. Dates for
marks lock and reports remain optional metadata until the assessment workflow.

## Autonomous implementation defaults (24 October 2026)

Use both CAPS and IEB as supported catalogue frameworks; only publish sourced,
versioned entries. Operator imports record provenance; school names/codes are
never guessed. School assessment plans explicitly select component weights and
missing/rounding policies. A reference ratio is not applied to existing marks
without an explicit published school plan. Components support SBA/exam and
subject-specific practical/oral components rather than assuming every subject
uses only two buckets. Policy changes create new versions.

Continue implementation while pilot school, official import files, language
reviewers, staging access and independent assessor remain unavailable. Keep
those evidence requirements open, with runnable rehearsal tools, not fabricated
results. Preserve the separate owner production-acceptance requirement.

Research references: DBE Circular S8 of 2023 and its subject annexure distinguish
phase-level SBA/exam shares from subject-specific PAT and oral components. See
https://www.education.gov.za/Curriculum/NationalCirculars.aspx and the linked
S8 documents. Later circulars retain or amend portions of the programme. Keep
source/version/effective dates visible and require an explicitly published school
plan; do not treat one generic ratio as every subject policy.


Attendance defaults: teacher capture/amendment is limited to school-local today;
current grade heads correct earlier days with a reason. An offline timestamp
never grants historical write rights. Cutoff starts editable at10:00, Monday
throughFriday; school managers select their actual cutoff/days before the pilot.
Missing registers are flagged rather than guessed absent. Period attendance
stays separate. Signed learner QR identifiers are rotatable and do not authorize
writes or implement Phase4 card sign-in.

Attendance delivery deduplicates a qualifying entry revision. Transport suppresses
a corrected/superseded absence or later approved leave; a genuinely new absence
after correction can notify without being blocked by an old suppressed claim.

Mid-year defaults: grade join/subject-change rules start at catch-up and are
explicitly editable by school academic managers. Join permits catch-up/excused;
carried subject changes reuse only same-subject/grade/year canonical evidence.
A different subject needs explicit catch-up/excused, never an invented transfer
score. Pro-rata is an opt-in request flag for the later billing phase. Leave means
last attended date (inclusive). Exit copies default to seven days, configurable
1-30, with ten-minute requester-bound links. Educational history is retained until
an approved school/platform retention schedule exists; no deletion is guessed.


Private content defaults: school/group-scoped drafts require explicit teacher review
before publication. Audio/video use offline local transcription through a limited
AiGateway boundary with moderation and duration caps, brought forward solely to
meet Phase 2 media conversion; no paid provider or external media disclosure.
Unavailable scanning/conversion blocks the affected item, never unrelated work.

Offline content copies are explicit and scoped to school/account/learner, with a
server lease no longer than one day. Online/resumed readers recheck rights; local
expiry and account/school replacement clear copies. Immediate revocation while a
device is disconnected is not claimed. Source uploads never enter this cache.
