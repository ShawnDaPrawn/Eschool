# Self-test runs

Before a feature is marked done, the Test role walks it as every profile it touches, on staging, in a browser and on a phone (Build Bible section 5.2, "Self-testing across profiles"). Each run is recorded in `phase-N.md` in this folder, one table row per feature, profile and device. Screenshots go in `evidence/` and are linked from the row. A feature with a failed row is not done.

## Profiles

The seeded users in `DemoSchoolSeeder` (one school in Phase 0, two from Phase 1):

| Profile | Seeded user |
| --- | --- |
| SaaS admin | platform staff, no school |
| School admin | super admin at School A and at School B |
| Finance | finance role at School A |
| Teacher | subject teacher and homeroom teacher at School A |
| Grade head | teacher with a grade assignment |
| Facilitator | sport coach, no teaching allocation |
| Counsellor | counsellor role |
| Learner | approved learner; unapproved learner |
| Parent | parent with two children at School A; parent with a child at each school |
| Vendor | tuck shop vendor at School A |

## Template (Appendix C)

```markdown
| Feature | Profile | Device or browser | Steps | Result | Evidence | Defect |
|---|---|---|---|---|---|---|
| Register by QR | Teacher (homeroom) | Pixel 6a, Android 15 | Open register, scan 3 cards, mark 1 absent, save offline, go online | Pass | evidence/p2-register-qr.png | |
| Register by QR | Grade head | Chrome 1280 px | Open yesterday's register, amend, save | Pass | evidence/p2-register-amend.png | |
```

- **Feature**: the feature name from the phase plan.
- **Device or browser**: model and OS version, or browser and window width (390, 820 or 1280 px).
- **Steps**: what was done, as a new user would do it.
- **Result**: Pass or Fail.
- **Evidence**: a screenshot path under `evidence/`, named `p<N>-<feature>-<step>.png`.
- **Defect**: the issue or pull request raised when the row failed.

## UX scripts

Five tasks per profile per phase, written as a sentence a new user would be told ("Find out which room your child's next lesson is in"), run on staging with no help, timed, with any wrong turn noted. A task passes if it is completed in under two minutes without a wrong turn; four of five must pass per profile, and failures are fixed before the gate.

Starter scripts for Phase 0:

| Profile | Tasks |
| --- | --- |
| Admin | Add a learner; set up terms; send a notice; switch to last year; create next year |
| Teacher | Today's timetable; take a register; capture a mark; see a learner's page; message a parent |
| Learner | Next lesson and room; assignments due; results; a notice; change language |
| Parent | Child's attendance today; fees balance; a report card; a notice; switch child |

Record UX script runs in the same `phase-N.md`, in a second table: profile, task, time taken, wrong turns, result.
