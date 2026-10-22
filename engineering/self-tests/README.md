# Self-test runs

A feature is not done until someone in the Test role has used it as each profile it affects, in a browser and on a phone, on staging (the Build Bible, section 5.2, sets the rule). Each run goes in `phase-N.md` in this folder as one table row per feature, profile and device, with its screenshots in `evidence/`. Any failed row keeps the feature open.

## Profiles

The demo accounts come from `DemoSchoolSeeder` (see its header for the sign-in details). Phase 0 has one school:

| Profile | Demo account |
| --- | --- |
| School admin | Super Admin of the demo school |
| Finance | staff member with the custom Finance role |
| Teacher | homeroom and subject teacher; a second homeroom teacher |
| Learner | approved learner, signing in to the app with an admission number |
| Parent | parent of two learners in different grades |

Phase 1 adds a second school and the profiles it introduces; this table grows with the seeder.

## Recording a run

One row per feature, profile and device:

```markdown
| Feature | Profile | Device or browser | Steps | Result | Evidence | Defect |
|---|---|---|---|---|---|---|
| Year switcher | School admin | Chrome 1280 px | Switch to last year, open Learners, try to edit one | Pass | evidence/p0-year-switcher-edit.png | |
```

- **Feature**: as named in the phase plan.
- **Device or browser**: the phone model and OS version, or the browser and window width (390, 820 or 1280 px).
- **Steps**: what you did, the way a first-time user would.
- **Result**: Pass or Fail.
- **Evidence**: a screenshot under `evidence/`, named `p<N>-<feature>-<step>.png`.
- **Defect**: the issue or pull request opened for a failure.

## UX scripts

Each phase also tries its screens on people who have not seen them. Give them a task in one plain sentence (for example "Find out when your child's next lesson starts and where"), offer no help, time it on staging and note every wrong turn. A quick task with no wrong turn passes; the Build Bible, section 5.2, sets the time limit and how many tasks each profile must pass, and failures are fixed before the phase closes.

Phase 0's tasks follow its acceptance paths: for an admin, the jobs the new navigation is built around (adding a learner, setting up terms, sending a notice) and the year tools; for teachers, learners and parents, the everyday screens of their app.

Record the runs in the same `phase-N.md`, in a second table: profile, task, time taken, wrong turns, result.
