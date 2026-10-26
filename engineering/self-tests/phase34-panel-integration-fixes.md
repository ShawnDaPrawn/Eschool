# Phase 3/4 integration corrections

The protected context now advertises the existing `my_grade` capability. Student, Parent and Teacher context shape snapshots each gain exactly that boolean; all other response shapes remain unchanged. A disabled My Grade module short-circuits visibility using the request's module snapshot. Enabled access retains fresh school, module, membership and grade-appointment checks. The existing context query ceilings stay unchanged.

Communication rehearsal now uses a current synthetic learner with an active dated Student membership, consistent with the strengthened current-recipient policy. It refuses non-test databases. The custom-audience scenario checks that the exact withdrawn contact disappears and that no remaining contact is selected, while allowing another current, unselected contact to remain available. Production recipient checks are unchanged.

2026-10-25 local verification:

- API shape regression: 1 test / 180 assertions passed; reviewed three additive boolean snapshot lines only.
- Context and My Grade domain: 14 tests / 130 assertions passed, including unchanged query limits and child-count independence.
- My Grade HTTP: 5 tests / 125 assertions passed.
- Full PHP static analysis, changed-file Pint and TypeScript passed.
- Actual browser cohorts: reports 3, communication 4, gallery 3 and recognition 3 passed at their existing viewport sizes. The initial combined run exposed one additional fixture-only contact-count assumption; the complete four-case communication cohort passed after its correction. No privacy, accessibility, recipient or publication assertions were removed.

Old Docker-owned academic files and cache directories initially prevented local fixture writes. They were preserved in place and replaced with writable isolated test directories; the original public-storage link target was recorded before correcting it. These are workspace repairs, not repository changes.

The original CI report request also recorded an empty HTTP response, while local report flows pass. Final integration CI must still verify the complete merged tree; the targeted checks do not certify an unreproduced intermittent failure as fixed.
