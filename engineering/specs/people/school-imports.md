# School onboarding imports

Action Plan step 0.11 (homerooms, learners and guardians; staff follows once staff invitations are merged). Owner role: Backend and Admin UX. It uses the LURITS identifier (step 1.0, `engineering/specs/platform/lurits.md`) and applies the matching rule of step 1.0c.

## User stories

- As a school setting up NavigateEd, I import my homerooms from a list of grades and letters, so I don't create them one by one.
- As a school, I import my learner list (from SA-SAMS or my own spreadsheet), with each learner's LURITS number and up to two guardians, and see every problem before anything is saved.
- As a school, I can import the same file again after fixing rows; learners already imported are left alone.
- As a guardian, I get one invitation to sign in and approve my children when the school chooses to send them, even if I have several children at the school.
- As a learner who already has a NavigateEd record at another school, I am not duplicated: the school is told to start a transfer, and is not told where my record is.

## Flow

1. **Choose the file.** A CSV upload or pasted rows (UTF-8 or Windows-1252, comma, semicolon or tab, at most 1 MB and 1,000 rows, a header row with unique names). A template with the header row can be downloaded.
2. **Match the columns.** Each NavigateEd field is matched to the column whose header names it, ignoring case, spaces, underscores and dots, including common SA-SAMS and Afrikaans names ("Admission No", "Surname", "First Names", "Date of Birth", "Class", "LURITS Number", "Parent Email", "Van", "Geboortedatum"). The school can change any match. A column can serve only one field.
3. **Settings.** Homerooms: the medium for rows that name none (defaults to the only medium). Learners: the school year (current), the date they are in their homerooms from (today, or the year's first day if it has not started), the learner category (the first), and whether to email guardians their invitations now (off).
4. **Check the rows.** Each row is *will be imported*, *already here, left alone*, *start a transfer* or *fix this row*, with notes. Problems are listed first.
5. **Import.** Only when no row needs fixing. Everything is written in one transaction, with the school row locked so two imports cannot run at once, after the file is checked again. If anything differs from the check the school saw (a fingerprint of the file, matches, settings and every row's result), nothing is written and the school checks again. The fingerprint ignores CRLF line ends and surrounding whitespace, which browsers and request trimming add.

## Rows

**Homerooms**: grade (R, 1 to 12, "Gr 8", "Grade 8", "Graad 8"), letter, optional class name (default "Grade N") and medium (by name).
- The class with that name and medium is reused, and its grade is set through `AcademicReferences::map` if it had none. A class already set to another grade is an error.
- Letters (sections) are reused by name.
- A homeroom that exists is left alone. Duplicate rows are errors.
- New classes get a no-terms `class_session_configs` row for every school year, as the class screen does.

**Learners**: admission number, first name and surname (required); date of birth (day first: 09/03/2015, 2015-03-09, 9.3.2015 or a spreadsheet day number), gender, LURITS number, and the homeroom by full name ("Grade 8 B"), short form ("8B") or grade and letter; up to two guardians (first name, surname, email, mobile, relationship).
- An admission number already at the school, archived or not, means *already here*.
- A LURITS number held by a learner of this school means *already here*. One held by any other NavigateEd learner means *start a transfer*, with no school or record named. An invalid number is an error, as is one repeated in the file.
- A guardian without an email address is not added (noted). A learner without any guardian email is imported with a note that they cannot be approved until a guardian is linked.
- An existing account with the guardian's email is linked, not duplicated, unless it is deleted, inactive or suspended as a parent here (error, without saying more).

**Writes for each learner**: as an accepted admission does (`EnrolApplication`).
- A user with an unusable `@learners.invalid` email and a random password, `password_change_required`, the Student role and its membership from the start date.
- A `students` row (admission date = the start date, the first guardian as the legacy guardian).
- An active `guardian_links` row per guardian (`can_approve`, `can_see_fees`; relationship mother, father, guardian, grandmother, grandfather, aunt, uncle, sister, brother, foster parent or other).
- `EnrolmentService::place` in the homeroom from the start date, which assigns the NavigateEd student number.
- `LuritsNumbers::record` with source `onboarding_import`.
- Guardians get the Parent role and a `parents` row.
- Invitations are `GuardianInvitations::issue`, once per guardian, only when chosen. Learner approval is never granted by an import.
- One `academics.learner_changed` outbox event.

**Audit**: `people.onboarding_import_committed` with the kind, the file's SHA-256, row and skipped counts and the result counts. No row contents.

## Acceptance tests (`People/SchoolImportsTest`)

| Given | When | Then |
| --- | --- | --- |
| Homerooms 8A, 8b, RA, a duplicate 8A, grade 13, and the existing 5A | check | three to create, one already here, two errors; import refused while errors remain |
| The same without the bad rows | import, then check again | Grade 8 (grade 8) with two homerooms and its year config; audit row; everything already here |
| SA-SAMS style headers | suggest | each field matched to its column, Parent Email to guardian 1 and Father Email to guardian 2 |
| Learners: new with two guardians (one existing account) and a LURITS number; "6C" with a guardian without email; an existing admission number; 31 February; an unknown homeroom; a LURITS number held at School B; a repeated admission number | check | create, create with a note, already here, error, error, start a transfer (School B not named), error |
| The two good rows | import with invitations | two learners, enrolled in the right homerooms; the LURITS number recorded; the new guardian created with the Parent role and relationship; the existing guardian linked, not duplicated; two invitations; School B untouched; importing again leaves everything alone |
| A learner added by hand with the same admission number after the check | import | refused |
| A file with a BOM, semicolons, CRLF and a blank line; a Windows-1252 file; 9.3.2015, 42072, 2015-02-30; 1,001 rows | read | read as expected; the bad date refused; too many rows refused |
| A school administrator | open, download a template, upload, import (CRLF as a browser sends it), choose a missing column | page, CSV header, rows checked, learner imported, the missing column reported |
| A teacher without the permissions | open, check, or call the service | 403 |

## Endpoints (panel)

| Method and path | Permission | Notes |
| --- | --- | --- |
| `GET /school-imports?kind=` | `class-create` (homerooms) or `student-create` (learners) | the kinds offered are the ones the person may import |
| `GET /school-imports/templates/{homerooms\|learners}` | the kind's permission | CSV header row, `no-store` |
| `POST /school-imports/check` | the kind's permission, throttled 30 a minute | `kind`, `file` or `csv`, `mapped`, `mapping[field]`, settings |
| `POST /school-imports` | the kind's permission, throttled 10 a minute | the same plus `fingerprint` |

No API change.

## Screens

`people/school-imports` (People, "Import school data"):
- kind tabs, the file step with a template link, column selects, settings, the counts and the rows table (problems first);
- "Check again", and "Import N rows", which is disabled while any row needs fixing or nothing is new;
- the file travels in a hidden field between steps.

## Security notes

- Tenant-scoped reads for homerooms, learners and categories.
- Guardian accounts are global (unique email), so an existing account is linked but never changed, and its school memberships are not revealed.
- LURITS matching follows the 1.0c privacy rule.
- Learner accounts cannot be signed into until set up through the guardian invitation or a set-up code.
- File limits as above; there is no file storage, the file lives only in the request.

## Deferred

- Staff import: after staff invitations (PR #358) merge, so staff get invitations rather than passwords.
- Import on a school's behalf by HQ operations (SA.11).
