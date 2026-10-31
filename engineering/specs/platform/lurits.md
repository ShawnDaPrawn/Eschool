# LURITS number as the primary learner identifier

Action Plan step 1.0. Decision record: [ADR 0007](../../adr/0007-lurits-primary-learner-identifier.md).

## This change (domain foundation)

- `learner_identifiers` gains `lurits_hash` (unique, HMAC-SHA256 with the app key), `lurits_number` (encrypted), `lurits_source`, `lurits_recorded_at`, `lurits_recorded_by`.
- New school-owned `lurits_conflicts`: the claiming school, learner, number hash, holder user, source, status (`open`, later `resolved` or `rejected`), who raised and resolved it.
- `App\Services\Platform\Identity\LuritsNumbers`:
  - `normalise()` removes spaces and dashes; `valid()` checks the configured pattern.
  - `record(learner, number, source, actor)` returns `recorded`, `unchanged` or `conflict`. It assigns the NavigateEd number first if needed, writes under a platform-wide claims lock (the `platform_counters` row `lurits_claims`, created by the migration and locked directly, so two schools claiming the same unused number at once get one `recorded` and one `conflict`, never a deadlock or unique-key error), audits hashes only, and opens one conflict per claim when another person holds the number. It refuses (404) a learner who is not in the current school, even a model loaded elsewhere.
  - `forLearner()`, `primary()` (LURITS, else the NavigateEd number labelled temporary) and `holderUserId()` for internal matching.
  - `forLearners()` (1.0d): the numbers of many learners at once, for exports. It refuses (404) if any learner is not in the current school.
- Sources: `school_entry`, `sasams_import`, `onboarding_import`, `transfer`, `dbe_correction`.

## Privacy rules

- The number is encrypted at rest and never in audit rows, notifications, URLs, logs or AI context.
- A school sees only its own conflicts (`lurits_conflicts` is school-owned). The holder school is never told of a claim; the claiming school is never told who holds the number.
- Visible (in the follow-up screens) to the learner, approving guardians and staff with profile permission.

## Acceptance tests (`Platform/LuritsNumbersTest`)

| Given | When | Then |
| --- | --- | --- |
| A learner | a LURITS number with spaces and dashes is recorded | it is normalised, encrypted, hashed, and becomes the primary identifier |
| A learner without LURITS | the primary identifier is asked for | the NavigateEd number, typed `navigateed` |
| A recorded number | it is recorded again from another source | `unchanged`, original source kept |
| A number held by a learner at School A | School B records it for its learner | `conflict`; not written; one open conflict owned by School B |
| A repeated claim | recorded twice | still one open conflict |
| A conflict raised by School B | School A lists its conflicts | none |
| Any recording | audited | the number is absent from the audit log |
| Invalid number or source | recorded | refused |
| A learner of School B | School A records, reads or asks for the primary identifier | 404; nothing written, no conflict |
| The claims lock row | a number is recorded | the row is locked without an insert first (no shared-lock deadlock) |
| A number held at School B | matched from School A | the holder's user id (internal only) |

## Follow-ups (later changes)

1. Panel and app display: LURITS first on learner profiles, report cards, transfer and exit packages; the NavigateEd number labelled temporary when no LURITS exists.
2. API: additive `lurits_number` and `primary_identifier` on v2 learner resources (OpenAPI and generated SDK).
3. ~~Entry and edit in the panel~~: done, see "School office and HQ claims" below (a page by admission number; the learner profile screen remains).
4. Admissions, mid-year Join and onboarding imports match on LURITS before creating a learner. (Onboarding imports: done in 0.11; since 1.0d they also record a LURITS number for a learner already at the school, see [school imports](../people/school-imports.md).)
5. ~~SA-SAMS exports keyed on LURITS~~: done in 1.0d, see [SA-SAMS layouts](../academics/sasams-layouts.md). The manual `learner_refs` map stays as the fallback for learners without a LURITS number.
6. ~~A "learners missing LURITS" list for the school office, and the conflict resolution screen in Navigate Ed HQ~~: done, see below.
7. Confirm the official LURITS format and tighten `identity.lurits.pattern`.

## School office and HQ claims (Action Plan 1.0e and 1.0f)

**LURITS numbers** (`/learners/lurits`, People, `student-edit`):
- How many of the school's learners have a number, and how many still use the temporary NavigateEd number.
- The learners without one, by surname, with admission number and homeroom, 50 to a page.
- Recording or correcting a number by admission number (`LuritsNumbers::record`, source `school_entry`). A correction replaces the number; the audit keeps the hashes before and after.
- A number another learner holds is not written. The office is told only that it is already registered, with the advice to start a transfer request if the learner is joining, and that Navigate Ed support will check it otherwise. It never learns who holds the number or where.
- The school's own open claims (learner, admission number, date).
- Another school's learner is not found by admission number.

**LURITS claims** in HQ (`/saas/lurits`, new ability `identity.review` for Owner, Operations and Support):
- Each open claim shows the claiming school, the school or schools where the number's holder is a learner, the source and the date, never a learner's name or the number.
- Support works it out with the schools and closes it:
  - *a school corrected the number* (`resolved`)
  - *the learner is moving: transfer started* (`resolved`)
  - *the claim was wrong* (`rejected`)
- Closing records who closed it and when, is audited (`platform_lurits_claim_closed`, with the claiming school), and cannot be done twice (409). Nothing is merged automatically.
- Recently closed claims are listed below the open ones.

### Acceptance tests (`People/LuritsOfficeTest`)

| Given | When | Then |
| --- | --- | --- |
| No numbers; Ben at School B holds 8800000002 | the office opens the page, records Asha's number with spaces, records it again, claims Ben's number for Cara, uses an unknown admission number, a too-short number, a School B admission number | 0 of 3, Asha listed; recorded and normalised; "already has"; a warning, not written, the claim listed without naming School B; errors for the rest |
| A teacher | the page or a record | 403; nothing written |
| Cara's claim on Ben's number | Support opens HQ; closes it as corrected; closes it again; Sales opens HQ | both school names, the source, no learner name, number or admission number; closed and audited; 409; 403 |
