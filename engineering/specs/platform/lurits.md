# LURITS number as the primary learner identifier

Action Plan step 1.0. Decision record: [ADR 0007](../../adr/0007-lurits-primary-learner-identifier.md).

## This change (domain foundation)

- `learner_identifiers` gains `lurits_hash` (unique, HMAC-SHA256 with the app key), `lurits_number` (encrypted), `lurits_source`, `lurits_recorded_at`, `lurits_recorded_by`.
- New school-owned `lurits_conflicts`: the claiming school, learner, number hash, holder user, source, status (`open`, later `resolved` or `rejected`), who raised and resolved it.
- `App\Services\Platform\Identity\LuritsNumbers`:
  - `normalise()` removes spaces and dashes; `valid()` checks the configured pattern.
  - `record(learner, number, source, actor)` returns `recorded`, `unchanged` or `conflict`. It assigns the NavigateEd number first if needed, writes under a lock, audits hashes only, and opens one conflict per claim when another person holds the number.
  - `forLearner()`, `primary()` (LURITS, else the NavigateEd number labelled temporary) and `holderUserId()` for internal matching.
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
| A number held at School B | matched from School A | the holder's user id (internal only) |

## Follow-ups (later changes)

1. Panel and app display: LURITS first on learner profiles, report cards, transfer and exit packages; the NavigateEd number labelled temporary when no LURITS exists.
2. API: additive `lurits_number` and `primary_identifier` on v2 learner resources (OpenAPI and generated SDK).
3. Entry and edit in the panel learner profile (staff with profile permission), with conflict messages that say only "this number is already registered; start a transfer request".
4. Admissions, mid-year Join and onboarding imports match on LURITS before creating a learner.
5. SA-SAMS exports keyed on LURITS, replacing the manual `learner_refs` map.
6. A "learners missing LURITS" list for the school office, and the conflict resolution screen in Navigate Ed HQ.
7. Confirm the official LURITS format and tighten `identity.lurits.pattern`.
