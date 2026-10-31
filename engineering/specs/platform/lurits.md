# LURITS number as the primary learner identifier

Action Plan step 1.0. Decision record: [ADR 0007](../../adr/0007-lurits-primary-learner-identifier.md).

## This change (domain foundation)

- `learner_identifiers` gains `lurits_hash` (unique, HMAC-SHA256 with the app key), `lurits_number` (encrypted), `lurits_source`, `lurits_recorded_at`, `lurits_recorded_by`.
- New school-owned `lurits_conflicts`: the claiming school, learner, number hash, holder user, source, status (`open`, later `resolved` or `rejected`), who raised and resolved it.
- `App\Services\Platform\Identity\LuritsNumbers`:
  - `normalise()` removes spaces and dashes; `valid()` checks the configured pattern.
  - `record(learner, number, source, actor)` returns `recorded`, `unchanged` or `conflict`. It assigns the NavigateEd number first if needed, writes under a platform-wide claims lock (the `platform_counters` row `lurits_claims`, created by the migration and locked directly, so two schools claiming the same unused number at once get one `recorded` and one `conflict`, never a deadlock or unique-key error), audits hashes only, and opens one conflict per claim when another person holds the number. It refuses (404) a learner who is not in the current school, even a model loaded elsewhere.
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
| A learner of School B | School A records, reads or asks for the primary identifier | 404; nothing written, no conflict |
| The claims lock row | a number is recorded | the row is locked without an insert first (no shared-lock deadlock) |
| A number held at School B | matched from School A | the holder's user id (internal only) |

## Follow-ups (later changes)

1. ~~Panel and app display~~: panel and documents done, see "Headline number" below; the Flutter profile screens (`packages/eschool_people`) remain.
2. ~~API~~: `primary_identifier` added to the v2 learner resources, see "Headline number" below (no separate `lurits_number` field: `primary_identifier` carries it). The generated Dart SDK (`packages/eschool_api`) still needs regenerating.
3. ~~Entry and edit in the panel~~: done, see "School office and HQ claims" below (a page by admission number; the learner profile screen remains).
4. Admissions, mid-year Join and onboarding imports match on LURITS before creating a learner.
5. SA-SAMS exports keyed on LURITS, replacing the manual `learner_refs` map.
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

## Headline number (Action Plan 1.0b)

The LURITS number is the number a learner is known by wherever NavigateEd identifies them (ADR 0007). When a learner has no LURITS number yet, the NavigateEd number (ADR 0005) is shown in its place, labelled **NavigateEd number (temporary)**. The school's own admission number stays, shown after it as **Admission number**.

### User stories

- As the school office, when I open a learner's profile or report page I see their LURITS number first, so I can quote the number the Department and other schools use.
- As a guardian who approves for my child, the app can show my child's LURITS number.
- As a receiving school, a transfer package names the learner by LURITS number, so I can match the learner to the Department's records.
- As a learner leaving, my report cards, exit archive and leaving certificate carry my LURITS number.

### Rules

- **The headline identifier** is `LuritsNumbers::primary()`: `{type: 'lurits', value}` when a LURITS number is recorded, else `{type: 'navigateed', value}` with the NavigateEd number, else null.
- **Who sees it on screens and in the API** (`App\Services\Platform\Identity\LearnerIdentifiers`), at the learner's own school only:
  - the learner;
  - a guardian with an **active guardian link that may approve** (`can_approve`);
  - staff with **profile permission** (`profile-person-view` or `profile-person-edit`).
  - Everyone else gets null: a guardian whose link may not approve, a parent known only from the legacy father/mother/guardian fields (no guardian link yet), a teacher without profile permission (who may still open the profile of a learner they teach), and Navigate Ed staff during impersonation.
- **Documents** carry the headline identifier for whoever the document is issued to, because they are the school's official records of the learner. This includes a guardian whose link may not approve: they see the number on the report card (owner decision, 31 October 2026), even though screens and the API still show it only to approving guardians.
  - report card PDFs (read when the PDF is made, held only in the encrypted PDF, never in the card payload or its fingerprint);
  - transfer packages (frozen in the encrypted, signed package contents the guardian consents to);
  - exit archives (in the signed `manifest.json` with the admission number, read when the archive is built; the stored manifest and archive are encrypted);
  - the leaving certificate (row 2, before date of birth; staff with `generate-document`).
- **Never** in notifications, URLs, logs, audit rows or AI context. No v1 API change.
- `admission_no` is unchanged and still the school's own number.

### Where it shows

| Place | What changed |
| --- | --- |
| Panel: person profile (`/people-profiles/{person}`, learner profile) | Headline number, then admission number, under the page title |
| Panel: student report (`/student-report/{id}`) | The header's "ID: admission number" becomes headline number, then admission number |
| Panel: school transfers workbench | The package's headline number replaces "Student number"; packages prepared before this change still show the student number |
| Report card PDF (`academics.reports.pdf`) | A line under the learner's name: headline number · admission number |
| Transfer package contents (`navigateed-school-transfer-v1`) | New `primary_identifier` |
| Exit archive manifest (`navigateed-academic-exit-v1`) | New `primary_identifier` and `admission_no` |
| Leaving certificate PDF | New row 2; "GR Number" becomes "GR Number (admission number)" |
| API v2 `GET /me/profile`, `GET /people/{person}/profile` (ProfileForm) | New `primary_identifier` (null for non-learner profiles) |
| API v2 `GET /me/context` children (Child) | New `primary_identifier` |
| API v2 `GET /guardian/approvals` (ApprovalLearner) | New `primary_identifier` |
| API v2 `GET /wellbeing/configuration` learner choices (WellbeingLearnerChoice) | New `primary_identifier` |

The API field is additive and optional in `openapi.yaml` (`LearnerPrimaryIdentifier`, or null): no field is removed or renamed.

### Data

No new tables or migrations. `LuritsNumbers::primaryForUsers()` reads many people's numbers in one query for lists (the app context's children, the wellbeing learner picker); it does not check the school, so only `LearnerIdentifiers` and the context's own authorised children call it.

### Acceptance tests

`People/LuritsHeadlineTest`:

| Test | Checks |
| --- | --- |
| `test_the_panel_profile_shows_lurits_first_then_the_admission_number` | Profile and student report pages show "LURITS number", the number, then "Admission number" and GR-ASHA; no School B number |
| `test_the_panel_labels_the_navigateed_number_as_temporary_without_lurits` | Cara: "NavigateEd number (temporary)", her number, then her admission number |
| `test_a_teacher_without_profile_permission_sees_only_the_admission_number_in_the_panel` | Tara sees the admission number, not the LURITS number |
| `test_the_learner_and_an_approving_guardian_get_the_number_in_the_api` | Asha (me/profile), Farid with an approving link (person profile, approvals, context child); Cara, known to Farid only from the legacy father field, gets null |
| `test_a_guardian_without_approval_rights_gets_null` | Mona (link without `can_approve`): null on profile and context, the number absent from the responses, no approvals listed |
| `test_staff_with_profile_permission_get_it_and_a_teacher_without_it_gets_null` | Super Admin gets LURITS and the temporary number; Tara gets null on a learner profile she may open; staff profiles have null; staff lists follow the same rule |
| `test_another_schools_learner_is_not_found_and_its_number_never_appears` | School B's learner: 404 on API and panel profile and report page, null even for a model loaded in School B; School B's number in no School A response |
| `test_the_number_stays_out_of_the_audit_log_and_the_application_log` | Reads write nothing with the number to `audit_log` or the log file |
| `test_the_leaving_certificate_names_the_learner_by_lurits_first_or_the_temporary_number` | Row 2 is the LURITS number (Asha) or the temporary number (Cara), then the GR (admission) number |
| `test_a_teacher_cannot_print_a_leaving_certificate` | Refused, no PDF made |

Also:

| Test | Checks |
| --- | --- |
| `Academics/TransitionEngineTest::test_report_cards_and_exit_archives_name_the_learner_by_lurits_first` | Report card page: temporary number, then LURITS once recorded, with the admission number; not in the card payload; exit manifest `primary_identifier` and `admission_no`; never in the stored manifest or archive in the clear; no School B number |
| `Transfers/SchoolTransferTest::test_the_package_and_the_panel_name_the_learner_by_lurits_first` | Package contents and the workbench: temporary number, then LURITS; encrypted at rest; the receiving school's incoming view shows it |
| `Contracts/OpenApiContractTest::test_learner_headline_numbers_match_the_spec` | LURITS, temporary and null shapes match `openapi.yaml` on profile, person profile, approvals and context |
| `Wellbeing/WellbeingApiTest::test_management_choices_are_named_and_do_not_grant_report_or_case_body_access` | The learner picker carries the LURITS number for the Super Admin |

### Not in this step

- The Flutter profile screens (`packages/eschool_people`) and the generated Dart SDK (`packages/eschool_api`, `tool/generate.sh`): no Dart toolchain here. The new field is optional, so the current SDK keeps working.
- `primary_identifier` on the other v2 resources that carry `student_number` (transfer summaries and details, transition learners, the mark matrix): their callers include teachers without profile permission, so each needs its own visibility decision.
- Panel screens left as they are: the student list and its edit dialog (`students/details`, `students/stu-detail`), the academic transitions workbench (it reads the v2 transitions resource), ID cards, the bonafide certificate, and exam result sheets.
- Re-making existing documents: a report card PDF generated before this change has no number line, and a card keeps the number it was made with, until the PDF is regenerated; transfer packages prepared before this change have no `primary_identifier` (the workbench shows their student number).
- Report cards can be downloaded by any linked guardian, including one who may not approve, so such a guardian can read the LURITS number printed on the card. The product owner should confirm this for documents, or ask for cards to leave it out.

