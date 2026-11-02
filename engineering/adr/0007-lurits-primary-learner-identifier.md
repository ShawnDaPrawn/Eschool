# 0007. The LURITS number is the learner's primary identifier

Status: Accepted (30 October 2026, product owner). Amends [ADR 0005](0005-student-number-and-handles.md). Headline display built in Action Plan 1.0b ([spec](../specs/platform/lurits.md#headline-number-action-plan-10b)).

## Context

- ADR 0005 gave every learner a NavigateEd lifelong student number (year, seven-digit sequence, Luhn check digit), because NavigateEd needed an identifier that belongs to the platform, not to a school.
- South African learners already have such an identifier: the **LURITS number** (Learner Unit Record Information and Tracking System), issued through the Department of Basic Education and used by SA-SAMS. It follows a learner to any school in the country, including schools not on NavigateEd.
- The product owner decided (Action Plan, step 1.0) that the LURITS number must be the main number on every learner's profile, because it creates cross-school compatibility and makes transfers reliable.
- Not every learner has one: pre-school learners (Grade 000 and 00), newly admitted learners awaiting allocation, learners from outside South Africa, schools that do not submit to LURITS, and NavigateEd Learn users.

## Decision

- **The LURITS number is the headline identifier** wherever a learner is identified (profile, report cards, transfer and exit packages, API learner resources). The NavigateEd number is shown only when no LURITS number is recorded, labelled as temporary. The school's `admission_no` stays alongside.
- **One LURITS number per human, on the global identity** (`learner_identifiers`), never per school.
- **Stored encrypted,** with a keyed hash (HMAC with the application key) for uniqueness and lookup. The number never enters audit rows (hashes only), notifications, URLs, logs or AI context.
- **A number another person holds is never written.** It opens a `lurits_conflicts` row for the claiming school and Navigate Ed support to resolve. The claiming school is not told who holds it or at which school.
- **Matching at admission and import uses LURITS** (`LuritsNumbers::holderUserId`), internally only: an existing holder means "start a consented transfer", never a silent merge or a disclosure.
- **Internal keys stay.** History (marks, reports, enrolments, transfers) remains linked to NavigateEd's records of the person. LURITS is a verified label on that record, so a mistyped or Department-corrected number can be fixed without breaking any history.
- **The NavigateEd number is not retired.** It is still allocated on first enrolment and remains the fallback, the sign-in alternative and the identifier for learners without a LURITS number.
- **Format.** Validation is configurable (`config/identity.php`, `lurits.pattern`, default digits only, 6 to 16) until the official format is confirmed from the SA-SAMS or DBE specification.

## Consequences

- SA-SAMS exchange can key on LURITS instead of the hand-made `learner_refs` map (a follow-up change to `SasamsExport`).
- Onboarding imports capture LURITS and match on it, which prevents duplicate learners across schools.
- A conflict queue needs a resolution screen in Navigate Ed HQ (Action Plan SA.11) and a school-side "learners missing LURITS" list.
- The keyed hash depends on `APP_KEY`; rotating the key requires re-hashing stored numbers (they are decryptable with the old key during rotation).
