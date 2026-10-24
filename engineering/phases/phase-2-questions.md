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
