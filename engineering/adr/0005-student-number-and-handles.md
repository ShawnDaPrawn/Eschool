# 0005. Lifelong student number and learner handle rules

Status: Accepted (Phase 0, defaulted). The rules are the defaults taken for Q0.5 and Q0.6 in [phase-0-questions.md](../phases/phase-0-questions.md) until the product owner answers. Nothing is built in Phase 0; Phase 1 (identity: `learners` view fields, `users.handle`, guardian links and approvals) consumes this record.

## Context

- Today a learner is identified inside one school by `students.admission_no` (the "GR number"), which is also stored in `users.email` as the student's sign-in name. It is chosen by the school, is not unique across schools, and changes when a learner moves.
- Phase 1 makes NavigateEd multi-school with one `users` row per human. A learner who moves from one NavigateEd school to another must keep their record, achievements and (later) portfolio, so they need an identifier that belongs to the platform, not to a school.
- Learners will have a handle for sign-in and for being found by teachers and peers inside a school. Most learners are children: POPIA treats everyone under 18 as a child, and the Bible requires parent approval before a learner signs in.

## Options

**Student number**

1. Keep `admission_no` per school. Familiar to schools, but not unique and not lifelong.
2. A UUID. Unique and opaque, but impossible to read out over the phone or type on a form.
3. A school-prefixed sequence (school code plus number). Readable, but it encodes the first school, which is wrong after a move.
4. **Year of first enrolment, a platform-wide sequence and a check digit.** Short, readable, typed safely (the check digit catches most typing errors), and says nothing about the person.

**Handle**

1. Free choice for everyone. Simple, but a child can publish an identifying or inappropriate name without a guardian seeing it.
2. **Free choice within rules, with guardian approval under 18.**
3. School-assigned handles only. Safe, but takes a sign-in identity away from older learners and adds admin work.

## Decision

**Student number** (option 4):

- Ten digits: `YY` + `NNNNNNN` + `C`.
  - `YY`: the last two digits of the calendar year of the learner's first enrolment at any NavigateEd school.
  - `NNNNNNN`: a seven-digit platform-wide sequence (zero-padded), allocated in a transaction from one counter, never reused.
  - `C`: a Luhn (mod 10) check digit over the first nine digits.
- Example: first enrolled in 2026, sequence 1234: payload `260001234`, Luhn check digit 4, student number **`2600012344`**.
- No school code, name, gender or birth date is encoded, so the number never changes when a learner moves school and reveals nothing about them.
- Allocated once, when a learner is first enrolled, and kept forever (soft-deleted learners keep theirs). Shown on ID cards, report cards and transfer packages; validated (length, digits, Luhn) wherever it is typed.
- `admission_no` stays as the school's own number beside it.

**Handle** (option 2):

- 3 to 20 characters: lower-case letters `a-z`, digits, dot and underscore; must start with a letter; no two consecutive dots or underscores; no leading or trailing dot or underscore.
- Unique across the platform, compared case-insensitively, with a reserved list (role names, `admin`, `support`, `navigateed`, school slugs).
- Must not contain the learner's surname (compared case-insensitively, ignoring dots and underscores), their student number or their date of birth in any common format.
- **Under 18**: the learner can propose a handle, but it takes effect only after an approving guardian (a `guardian_links` row with approval rights) accepts it; until then the learner signs in with their student number. **18 and over**: the learner sets it directly. Staff and parents choose their own handle under the same character rules.
- A handle can be changed at most once per term; every change is written to `audit_log`, and an old handle is held for 90 days before anyone else can take it.

## Consequences

- One counter allocates numbers platform-wide; the allocation must be inside a transaction and tested for concurrency in Phase 1.
- A ten-digit number with a check digit is easy to read out and hard to mistype; validation is a pure function the apps and the panel can share (`eschool_core` and a PHP service).
- The year prefix makes the number roughly sortable by first enrolment; it is not a secret and is not used for authentication on its own.
- The 2026 example in the questions file (`2600012347`) has the wrong check digit; this record carries the corrected example.
- Handle approval adds a guardian step for children, consistent with "no learner access without a verified, approving guardian". The approval request is a locked notification type (`approval.requested`).
