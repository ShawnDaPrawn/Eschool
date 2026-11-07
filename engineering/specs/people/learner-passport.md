# Learner passport v1

Action Plan 4.10. Owner roles: Backend, Admin UX. Decision record for the identifier: [ADR 0007](../../adr/0007-lurits-primary-learner-identifier.md).

A read-only record of a learner across the NavigateEd schools they attended, headed by their LURITS number (or the temporary NavigateEd number until one is recorded). It brings together:

- **Awards** from each school: permanent award presentations, including outside awards and persistence badges ([houses and awards](../community/houses-and-awards.md)). Withdrawn awards are left out.
- **Activities**: each school's activity group memberships (group, discipline, season, school year, member or captain, dates) ([activity groups](../activities/groups.md)).
- **Service**: verified supervised peer tutoring minutes per school, net of corrections, and the number of sessions ([peer tutoring](../community/peer-tutoring.md)).
- **Results carried with a transfer**: from each signed transfer package a school received ([school transfers](../academics/school-transfers.md)): the sending and receiving school, the last day and start date, the report cards sent (year and term) and the released results (subject, percentage, rating level, final or "so far", release date). A package is used only after its signature is checked against the sending school's key, its contents match the signed manifest and it is addressed to the school holding it.
- **Reading**: an empty section, marked unavailable, until the reading library (Action Plan 4.4) is in use. The seam is the `ReadingRecords` interface; the library binds its own implementation in place of `NoReadingRecords`.

Open Badges or CLR issuance is a later phase (Action Plan 5.9).

## Boundaries

The passport is about one learner across schools, so who sees which school is explicit:

| Viewer | Which schools | Why |
| --- | --- | --- |
| The learner (approved, signed in at a school where they are a learner) | Every NavigateEd school where their own account has a learner record, plus the packages those schools received | These are the learner's own records |
| A guardian with an **active link that may approve** (`can_approve`) at the current school | The same as the learner | They act for the learner |
| Staff of the current school with profile permission (`profile-person-view` or `profile-person-edit`) | **This school only**: what it holds, plus the signed packages addressed to it | A school never reads another school's live records |
| Anyone else | Nothing | |

- The learner is always named by the current school's own learner id. There is **no search or lookup by LURITS number**: the API takes a numeric learner id (a LURITS number in that place is just an unknown id, 404), and the panel finds learners by first name or surname only. A school with no learner record of the person cannot open their passport, so matching by LURITS never becomes a lookup tool.
- The learner's other schools are found by their own account (`students.user_id`), the platform's internal key for the person (ADR 0007: "internal keys stay"), never by matching numbers. This is the passport's one read across schools, and it happens only for the learner and their approving guardians. Each school's records are then read inside that school (`CurrentSchool::run`), with that school's module switches.
- A staff view never switches school and never reads across schools: every query runs in the current school (tested by recording the school of every query). What School A knows about the learner reaches School B only through the package the guardian consented to.
- Each school's section shows only if that school has the module on (`awards`, `groups`, `peer_tutoring`). Received packages are the holding school's own records and show regardless of the transfer module.
- Refusals: another learner, another guardian (including a guardian whose link may not approve, or a parent known only from the legacy father/mother/guardian fields), and another school's learner get 404, so nothing says the learner exists. Staff without profile permission get 403. Navigate Ed staff, impersonation and support sessions get 403. With the `passport` module off nothing answers (404).
- The headline number follows the LURITS visibility rule (`LearnerIdentifiers`).

## Never included

By construction (only the fields listed above are read):

- health and clinical records, counselling and wellbeing (incident reports, cases, check-ins), discipline, support plans and safeguarding;
- house points and their reasons ("House history is not a permanent-profile field");
- private reasons and notes of any kind: award withdrawal reasons, activity membership reasons, peer tutoring correction reasons;
- the documents in a transfer package (they may be health documents), attendance, guardians' names, staff names, other learners (a tutoring partner is never named);
- the learner's date of birth.

The tests seed a discipline record, a wellbeing check-in, a support plan, house points, a withdrawn award, a membership reason and a health document in the transfer package with markers, and check that no passport answer, panel page or download contains any of them, nor the guardian's name or the date of birth.

## Export

- Only the learner themself may download their passport, as JSON (`navigateed-learner-passport-v1`) or PDF, and only **from their 18th birthday by the school's calendar** (`AcademicAuthority::today()`, the school's timezone). A 29 February birthday turns 18 on 1 March in a year without one. An unknown or unreadable date of birth counts as under 18.
- Before that, the download is refused (403) and nothing is written. The learner's own view carries `export.available`, so the app can say "from your 18th birthday" without ever being sent the date of birth.
- Guardians and staff never download (403). **Open question for the owner:** may a guardian download a child's passport before 18? Default: no.
- Each download is audited (`passport.exported`, against the learner's record at the current school) with the format and the number of schools and transfers, never the LURITS number or the date of birth.

## User stories

- As a learner, I see my awards, activities, service and the results that came with me from my last school, all in one place, under my LURITS number.
- As a learner who has turned 18, I can download my passport to keep or share.
- As a guardian who approves for my child, I see my child's passport, including the schools they attended before.
- As the receiving school's office, I see what we hold about the learner and the results the sending school signed over, and nothing more.
- As the sending school, I see only my own records of the learner, never what the new school adds.

## Acceptance tests (`Passport/LearnerPassportTest`)

| Test | Checks |
| --- | --- |
| `test_the_learner_sees_their_own_passport_across_the_schools_they_attended` | At School B, Asha sees School A (dates, award, chess club as captain, 45 minutes in 1 session) and School B (attending, its award), the signed Science result (72.00%, level 6), LURITS, an empty reading section and no download before 18; her own id works; a LURITS number in the id place is 404 |
| `test_an_approving_guardian_sees_the_childs_passport_and_cannot_download_it` | Both schools' awards; no export block; no own passport and no download (403), nothing audited |
| `test_staff_see_only_what_their_school_holds_and_the_signed_package_addressed_to_it` | School B staff: only School B's award and the signed result, never School A's award, group or minutes; panel finds by name, not by LURITS; panel page shows LURITS, the award and 72.00% |
| `test_the_staff_view_never_reads_another_school_and_the_learner_view_does_only_for_her_own_rows` | Every query of a staff view runs in School B; the learner's view reads School A's records inside School A |
| `test_a_package_whose_signature_does_not_verify_is_left_out` | A tampered signature drops the package |
| `test_wrong_role_wrong_school_and_another_family_are_refused` | Teacher without profile permission 403 (API and panel); another learner, legacy-only parent, School B's own learner and parent, a guardian whose link may not approve, each school's staff for the other school's learner row, a guardian asking about a School A row from School B: 404; module off: 404 |
| `test_the_former_school_sees_only_its_own_records_never_the_new_schools` | School A staff: School A's award, no packages, never School B's award |
| `test_excluded_records_never_appear_anywhere` | Learner, guardian and staff views, the panel page, School A's view and the JSON download contain none of the markers (withdrawn award and reason, house points, discipline, wellbeing check-in, support plan, membership reason, health document in the package, guardian name, date of birth) |
| `test_the_download_is_refused_before_18_and_allowed_from_the_18th_birthday_in_school_time` | Refused the day before; allowed at 22:30 UTC on the eve, already the birthday in Johannesburg; JSON and PDF; bad format 422; two audit rows, School B, the learner as actor, no number or date of birth; staff 403 |
| `test_the_date_of_birth_never_leaves_and_an_unknown_one_means_no_download` | No date of birth: no download; no answer carries the date of birth |
| `test_an_unapproved_learner_is_refused_when_approval_is_enforced` | 403 |

Also `Contracts/OpenApiContractTest` (each route without a token, and the module switched off) and the tenancy sweep (`Tenancy/ApiRouteTenancyTest`, which covers new routes automatically).

## Data changes

None. No new tables or columns: the passport is assembled on read. New module key `passport` (`config/modules.php`, off by default, depends on people and academics), in the Futures product module and the legacy, core and standard packages.

## Endpoints

All `GET`, under `/api/v2/passport`, with `auth:sanctum`, `school.context`, `role.any`, `learner.approved`, `module:passport,read`, `throttle:60,1`, and private no-store responses:

| Path | Who | Answer |
| --- | --- | --- |
| `me` | The learner | `LearnerPassportResponse` |
| `me/export?format=json\|pdf` | The learner, from 18 (`throttle:10,1`) | The file, as an attachment (`LearnerPassportExport` or PDF) |
| `learners/{learner}` | The learner, an approving guardian, staff with profile permission | `LearnerPassportResponse` |

`openapi.yaml` gains the three operations (tag `LearnerPassport`) and the `LearnerPassport*` schemas. **The Dart client (`packages/eschool_api`, `tool/generate.sh`) must be regenerated**; the app screens are not in this step.

## Screens

- Panel, People, Learners, **Learner passports** (`/learner-passports`, shown to staff with profile permission when the module is on): find a learner by name; open their passport (`/learner-passports/learners/{learner}`): headline number and admission number, a note on what the view covers, then Schools, Results carried with a transfer and Reading, each with a plain empty state.
- PDF (`passport.pdf`): the same sections for the learner's download.
- Apps: not in this step (see below).

## Notification types

None.

## Module switch

`passport`. When it is off, the API routes and panel pages answer 404 and the menu item is hidden.

## Security notes

- Authorisation in `PassportAuthority`, checked before any learner record is read; the same rules for the API and the panel.
- School isolation: the learner id is looked up in the current school; staff views never leave it; family views read other schools only for the learner's own account.
- Transfer packages are verified (`TransferVault::verifyReceipt`, shared with the archive download) with the sending school's public key (`ExitSigning::publicKey`), which is derived without switching to that school.
- No LURITS search; the number is never in a URL, log or audit row.
- Downloads are audited; the date of birth stays inside `PassportExports`.

## Self-test steps

- Switch the passport on at two test schools, transfer a learner between them with a released result, add an award at each school, and check the learner's, the guardian's and each school's office view against the boundaries above.
- Set the learner's date of birth to tomorrow 18 years ago: no download; the next day: JSON and PDF download, and an audit entry.

## Not in this step

- The learner and parent app screens (Flutter) and the regenerated Dart client: no Dart toolchain here. The API is ready for them.
- A link from the learner profile page to the passport.
- The current school's own results and report cards (the apps already show them); attendance; documents from packages.
- Open Badges 3.0 or CLR issuance (Action Plan 5.9); sharing a passport with a third party.
- Reading records: the seam is ready for the reading library (Action Plan 4.4).
- A learner who has left every NavigateEd school can no longer sign in, so cannot download at 18; a later step could offer the download when leaving.

## Open questions

1. May a guardian download a child's passport before the child turns 18? Default: no.
2. Should a guardian see a former school's records when their link at that school was ended by the school (for example a court order), rather than by the learner leaving? Today the rule is the current school's approving link.
3. Should the passport include attendance or the current school's own released results?
