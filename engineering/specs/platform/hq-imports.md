# Imports on a school's behalf

Action Plan item SA.11, first part: onboarding imports that Navigate Ed
Operations run for a school, with a preview and the school's approval. The
imports themselves are the school's own ([school imports](../people/school-imports.md),
0.11): homerooms, learners with their guardians, and staff with their roles.

## User stories

- As Operations, I import a new school's learner list for them, so the school
  is set up without its staff having to learn the import screen first.
- As Operations, I see every row's result before anything is written, so I can
  fix the file with the school.
- As a school leader, I check exactly which rows Navigate Ed will import into
  my school, and approve or decline it, with a reason.
- As a school, we see every step Navigate Ed took with our data.

## How it works

1. **Check the file (preview).** At `/saas/schools/{school}/imports` (a link on
   each school's HQ page), Operations choose what to import, the leader at the
   school who will approve it, and the CSV file. The school's own import check
   (`SchoolImports::check()`) runs in that school as that leader, with their
   permissions there. It shows the column matching, the settings and every
   row's result, the same as the school's own screen. Nothing is written except
   the audit entry `platform_import_previewed`. Operations can change the
   columns or settings and check again. Every HQ step (check, send, apply,
   cancel) needs a **reason** (5 to 500 characters), recorded in its audit
   entry with who took it; the school sees it with the step.
2. **Send for approval.** Only a file with no row problems and at least one row
   to write can be sent. The check runs again and must give the same result as
   the preview (its fingerprint). The file is stored encrypted, with the
   preview's rows (also encrypted), the file's SHA-256 and the fingerprint. The
   leader is notified (`people.hq_import_approval`, linking to the import), and
   so are the school's other active Super Admins who are not platform staff
   (`school.hq_import_activity`: the kind, the number of rows and who will
   approve it; never a row).
3. **The school decides.** In the panel, `/hq-imports` ("Imports by Navigate Ed"
   under People) lists every import prepared for the school; each one shows the
   settings, every step, and every row with its result and what it will write:
   for learners the homeroom, admission number, date of birth, gender, LURITS
   number and each guardian who will be linked and invited (name, email, mobile,
   relationship); for staff the email, role, mobile, gender and qualification;
   for homerooms the class, letter, grade, medium and whether a class is new.
   Two files that differ in any of these give different approval pages. The leader it was sent to approves
   or declines, with a reason (5 to 500 characters). Before approving, the
   stored file's SHA-256 must match the preview's, and the check, run again as
   the leader, must give the preview's fingerprint. Approval records the SHA-256
   of the approved file.
4. **Apply.** Operations apply an approved import from its HQ page
   (`/saas/imports/{id}`). It runs in the import's own school, as the approving
   leader, through `SchoolImports::commit()` with the stored fingerprint: the
   school's own transaction, school lock, validation and writes. Before that,
   the stored file's SHA-256 must equal both the approved and the previewed
   one. The school's Super Admins are told it was applied
   (`school.hq_import_activity`).

## Rules

- Only platform staff with `imports.run` (Owner, Operations) use the HQ pages.
  Support, Finance, Content and Sales are refused (403), as is every school
  account.
- The approver must be an active staff member of the target school (not a
  learner or parent) whose roles there allow that kind of import
  (`class-create`, `student-create` or `staff-create`). HQ sees only the names
  of the school's staff who qualify, and which kinds each may import.
- Only the named leader decides. Another leader at the school can see the
  import but not approve it (403). A teacher who may not import cannot open it
  (403). Another school's import is not found (404).
- Writes happen only in the import's own school: the row is read again inside
  that school's scope, and the fingerprint names the school.
- **Stopped.** If the stored file is not the approved one, the school's records
  changed since the preview (so the rows would differ), or the approver may no
  longer import that kind, the import is stopped: status `void`, file deleted,
  `platform_import_stopped` recorded with the cause. Operations prepare it again.
- **Expiry.** An import waits at most 7 days from being sent, whether approved
  or not. After that it cannot be approved or applied. The hourly
  `platform:expire-school-imports` command (and any attempt to approve or apply
  it) marks it expired and deletes the file.
- Only active schools: a suspended or archived school is refused.
- Operations can cancel an open import, with a reason the school sees.

## Personal information

The file and the preview rows (with the values each row will write) are
personal information. Both are encrypted in the database (`encrypted` casts)
and set to null when the import is applied,
declined, cancelled, stopped or expires. Audit entries hold counts, kinds,
SHA-256 values, ids, statuses and the reasons, never the file or a row.

## Audit

Every step is an audit entry for the school (`school_id` set):
`platform_import_previewed`, `platform_import_requested`,
`platform_import_approved`, `platform_import_declined`,
`platform_import_applied`, `platform_import_cancelled`,
`platform_import_stopped` and `platform_import_expired`. The HQ audit log shows
them all (they are `platform_*` actions), with the reasons. The school's page
for each import lists the same steps. The school's own
`people.onboarding_import_committed` entry is written as well, now with the
school's id.

## Data

`platform_school_imports` (school-owned, required `school_id`): kind, status
(`awaiting_approval`, `approved`, `applied`, `declined`, `cancelled`, `void`,
`expired`), who sent it and the approver, the encrypted file and preview rows,
`file_sha256`, `fingerprint`, the column mapping and settings, the row counts,
`approved_sha256`, the decision's reason and time, who applied it and what was
written, who cancelled or stopped it and why, and `expires_at`.

## Acceptance tests

`tests/Feature/Platform/HqSchoolImportsTest.php`:

| Test | Shows |
| --- | --- |
| `test_operations_preview_send_and_apply_an_import_the_school_approved` | a reason is required to check and to apply, and is audited with the operator; the school's other Super Admin is told when it is sent, every Super Admin when it is applied; preview writes nothing; the file is stored encrypted; nothing applies before approval; the leader sees rows and steps and approves; applying writes in School A only; file deleted; steps in the HQ log and on the school's page |
| `test_the_approver_sees_each_learner_s_guardians_homeroom_and_settings_before_approving` | the approver sees each guardian's name, email and relationship, the homeroom and the settings; neither stored column holds the email in clear; HQ's page does not show it; School B gets 404 |
| `test_other_platform_roles_cannot_preview_send_apply_or_cancel` | Support, Finance, Content and Sales get 403 on every route; nothing changes |
| `test_school_staff_cannot_use_the_hq_import_routes` | School A's Super Admin, School B's leader and a teacher get 403 on every HQ route |
| `test_only_the_named_leader_of_the_target_school_decides` | a School B leader cannot be named; School B cannot see, approve or decline School A's import (404); another School A leader gets 403; a teacher gets 403; the named leader declines and the file is deleted |
| `test_a_file_changed_after_approval_is_never_applied` | a stored file replaced after approval stops the import; nothing written |
| `test_school_records_changed_after_approval_stop_the_import` | rows that changed since the preview stop the import |
| `test_an_import_not_applied_in_time_expires_and_its_file_is_deleted` | approval after 7 days is refused; the hourly command expires an approved import; applying is refused |
| `test_operations_cancel_an_open_import_with_a_reason` | a reason is required; the school sees the cancellation and cannot approve it |

## Not in this step

- The LURITS conflict queue and exports on a school's behalf (rest of SA.11's
  Build 1 part), POPIA data requests and school offboarding (Build 2).
- Downloading a kind's template from HQ (the school's own page has it).
- Applying in the background for very large files: imports are at most 1,000
  rows, applied in the request, as the school's own are.
- No API or `openapi.yaml` change: the panel and HQ only.
