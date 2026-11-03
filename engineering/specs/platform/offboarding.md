# School offboarding

Action Plan SA.11, last part: "School offboarding (full export to the school,
then archive and deletion as the contract requires)." Navigate Ed is the
operator under POPIA and the school is the responsible party (sections 20 and
21). So a school that leaves first gets all of its own data back, then is
archived, and its data is deleted when the contract says. This step builds the
workflow up to and including archiving, plus a **deletion dry run** behind two
approvals. It deletes nothing.

## User stories

- As **Operations** (or an Owner), I start an offboarding for a leaving school
  with a reason, a deadline for the school to collect its export, and the
  contract's retention terms, so the school gets its data and we know when to
  delete it.
- As the leaving school's **Super Admin**, I download the full export of my
  school's data from my own panel and confirm that we received it.
- As **Operations**, I archive the school once it has its export, and run a
  dry run that shows what deleting the school would remove.
- As an **Owner**, I give the second approval for deletion, which only works if
  someone else asked for it, the export was delivered and is intact, and no
  legal hold is on.
- As **HQ staff**, I see states, dates and counts only, never a row of the
  school's data or its export.

## Steps and rules

1. **Start** (`offboarding.manage`: Owner, Operations), at
   `/saas/schools/{school}/offboarding`.
   - The install's default school cannot be offboarded (public pages and sign-in
     run in it). The school must be active, so its Super Admin can sign in, and
     it must have at least one active Super Admin who is not platform staff.
   - One offboarding at a time: a second start is 409 until the first is
     cancelled.
   - The receipt deadline is at least `offboarding.minimum_receipt_days` (7)
     days away.
   - **Retention terms.** When the school's latest contract (`school_contracts`,
     SA.5) records an end date and a deletion term (the new optional
     `deletion_days_after_end`, "Days after the end before the school's data is
     deleted" on the contract form), the deletion date is the end date plus
     that term, and anything typed is ignored (`retention_source = contract`).
     Otherwise Operations enters the deletion date and the terms followed
     (`entered`). The data is never deleted before the school's deadline: an
     entered date on or before the deadline is refused, and a contract date on
     or before it moves to the day after the deadline, which the terms record.
   - The export is queued (`PrepareOffboardingExport`) on the `database`
     connection's `default` queue, which the provisioned worker consumes. Its
     timeout (150 seconds) is shorter than that connection's `retry_after`
     (180), so a slow build is never handed to a second worker. The longer
     `academic_content` connection is not used: its worker is an optional
     profile that production does not provision.
2. **Export** (`OffboardingExport`), built inside the school with the academic
   exit building blocks: a ZIP with one JSON Lines file per table, a
   `manifest.json` (each file's rows, bytes and SHA-256, what is not exported
   and why), its Ed25519 signature with the school's export key
   (`ExitSigning`), the public key, the school's own row and a README. It is
   encrypted with the app key (`Crypt`) on the private academic disk under
   `schools/{id}/offboarding/{offboarding}/{uuid}.enc`, with the archive's and
   the ciphertext's SHA-256 recorded. What it holds:
   - every `SchoolOwnedTables::REQUIRED` table's rows of the school, except
     Navigate Ed's own records of the school as a customer (`school_accounts`,
     `school_contacts`, `school_contracts`, `school_account_events`,
     `platform_incident_schools`, `school_offboardings`);
   - the school's `audit_log`, `notification_deliveries` and `settings` rows
     (without the secret settings of `SecretSettings`), its roles and role
     grants (`roles`, `model_has_roles`, `model_has_permissions`,
     `role_has_permissions`, and the `permissions` names they refer to), its
     `school_memberships`, and the `users` rows of its members;
   - not `domain_events` or `idempotency_keys` (delivery machinery), and never
     a credential or key, in any table, encrypted or decrypted
     (`OffboardingExport::NEVER_EXPORTED_COLUMNS`; the manifest lists what it
     left out per table): sign-in and device credentials (`password`,
     `remember_token`, `fcm_id`); invitation, calendar, lease and reader
     secrets and their hashes (`token`, `token_hash`, `encrypted_token`,
     `code_hash`, `secret_hash`, `secret_digest`, `key_hash`); card keys (the
     card signing `secret_key`, the chip's live SUN `metadata_key` and
     `file_read_key`, and the `encoding_package` that carries them); and bearer
     codes that admit or redeem (`private_qr_token`, `qr_hash`,
     `private_number`, `number_hash`, online exam `exam_key`). The rows
     themselves are still exported, without those columns;
   - values the models keep encrypted at rest (their `encrypted` casts) are
     decrypted so the school can read them; a value that cannot be decrypted is
     exported as null and counted in the manifest.
   - A build larger than `offboarding.maximum_export_bytes` (256 MB) fails with
     `too_large`; any failure leaves the offboarding `failed`, and Operations can
     prepare it again.
   - When it is ready, the school's active Super Admins get the
     `school.offboarding_export` notice (push, email and in-app, mandatory),
     linking to `/school-exit`.
3. **Delivery and receipt**, in the school's panel at `/school-exit` ("Data
   export (leaving)" under Settings, shown only to Super Admins while the school
   is leaving).
   - Only an active Super Admin of the school who is not platform staff sees the
     page, downloads or confirms. HQ staff have no school membership, so the
     panel signs them out; a platform staff identity that also holds a Super
     Admin role is refused.
   - The download is a signed link valid for 15 minutes
     (`offboarding.download_link_minutes`) and bound to the Super Admin it was
     made for (`reader`). Another person, another school (its id is not found
     there), a changed link or an expired link is refused. After the receipt
     deadline the export cannot be downloaded (410). Each download is counted
     and audited; the bytes are checked against both recorded hashes.
   - The Super Admin confirms receipt after at least one download. If the
     school does not confirm by its deadline, the daily
     `offboarding:receipt-deadlines` command (03:10) records receipt with basis
     `deadline`.
4. **Archive** (`offboarding.manage`), once the export is received: the school
   is archived through `PlatformSchools::changeStatus` (its people cannot sign
   in; nothing is deleted) and deletion is scheduled for the deletion date.
5. **Deletion approvals and dry run.**
   - Operations or an Owner can run the **dry run** at any time once archived:
     `OffboardingDeletionPlan` counts, per table, the rows of the school that
     deletion would remove (the REQUIRED tables less the kept ones, the school's
     settings, deliveries, outbox and idempotency rows, roles and grants,
     memberships), how many accounts only this school knows and how many also
     belong to another school (those keep their account), and what is kept and
     why. It only counts. The report is stored on the offboarding.
   - **Kept:** the audit log, Navigate Ed's customer records listed above, and
     the archived `schools` row itself.
   - **First approval** (`offboarding.manage`) and **second approval**
     (`offboarding.approve`, Owner only) must be different people. Each checks
     again that the school is still archived, that receipt is recorded, that
     the stored export is still there with the recorded ciphertext hash, and
     that no legal hold is on. The second approval stores a fresh dry run.
     Nothing is deleted in this step.
   - **Legal hold** (place: `offboarding.manage`, with a reason; lift:
     `offboarding.approve`) blocks both approvals and voids any approvals given
     so far.
   - **Cancel** (`offboarding.manage`, with a reason) at any point discards the
     export copy. An archived school stays archived until its status is
     restored on the school page.

Every step is audited (`platform_school_offboarding_started`, `_export_ready`,
`_export_failed`, `_export_retried`, `_export_downloaded`, `_receipt_confirmed`,
`_receipt_deadline_passed`, `_archived` with the existing
`platform_school_status_changed`, `_dry_run`, `_deletion_requested`,
`_deletion_approved`, `_legal_hold_placed`, `_legal_hold_lifted`,
`_cancelled`) with ids, states, dates and counts, never the reason text.

## Data

`school_offboardings` (school-owned, REQUIRED: `school_id` not null, indexed,
foreign key; written from `/saas` inside the school, and the receipt from the
school's panel): state (`preparing`, `failed`, `ready`, `received`,
`archived`, `cancelled`), reason, contract, retention source and terms,
deletion date, receipt deadline; the export's path, hashes, size, signing key
id, table and row counts, failure code, ready time, downloads; receipt time,
person and basis; archive, cancel, legal hold, deletion request and approval
(who and when); the dry-run report (JSON counts) and who ran it.
`school_contracts.deletion_days_after_end` (nullable) is the contract's
retention term. Both from `2026_11_04_100000_create_school_offboardings`.

## Routes

| Method | Path | Ability |
| --- | --- | --- |
| GET | `/saas/schools/{school}/offboarding` | `offboarding.view` |
| POST | `…/offboarding`, `/retry`, `/archive`, `/cancel`, `/dry-run`, `/deletion`, `/legal-hold` | `offboarding.manage` |
| POST, DELETE | `…/offboarding/deletion/approval`, `…/offboarding/legal-hold` | `offboarding.approve` |
| GET | `/school-exit` | the school's Super Admin |
| GET | `/school-exit/{offboarding}/download?reader=…` (signed, 15 minutes) | that Super Admin |
| POST | `/school-exit/{offboarding}/receipt` | the school's Super Admin |

## Acceptance tests (`Platform/SchoolOffboardingTest`)

School B (school 2) is the leaving school, because school 1 is the install's
default school and can never be archived; school 1 is the other school.

| Test | Given | Then |
| --- | --- | --- |
| `test_card_keys_and_other_credentials_never_leave_in_the_export_encrypted_or_decrypted` | a card signing key and a chip profile with its SUN keys and encoding plan | neither the plaintext nor the ciphertext of any key is anywhere in the archive; the chip row is exported with its UID and without its keys; no excluded column is in any row or decrypted; the manifest names each omitted credential column (cards, readers, tickets, invitations, exam keys, users) |
| `test_the_export_job_runs_on_the_provisioned_worker_and_finishes_before_its_connection_retries_it` | the export job | it is on the `database` connection's `default` queue and its timeout is shorter than that connection's `retry_after` |
| `test_operations_offboards_a_school_whose_super_admin_collects_and_confirms_its_export` | a contract with a 30-day deletion term; Operations starts | deletion on end + 30 days from the contract; export built and encrypted at rest; HQ page shows counts and the hash, no person and no link; the Super Admin is notified, downloads a signed archive whose manifest signature verifies, every row is the school's, no password column, card keys omitted, contracts not exported; receipt confirmed; school archived; every step audited without the reason text |
| `test_every_step_refuses_the_wrong_role_and_writes_nothing` | Support, Finance, Content, Sales and a school Super Admin on every HQ route; Operations on the second approval and lifting a hold; a teacher in the school's panel | 403 on each, no offboarding or audit row written, no download counted |
| `test_the_leaving_schools_offboarding_never_touches_the_other_schools_rows` | the whole flow up to the second approval | every school-owned table's row count, memberships and status of the other school are unchanged; the dry run counts only the leaving school; the other school's Super Admin sees no offboarding and the leaving school's id is not found |
| `test_the_download_link_is_refused_to_hq_staff_other_schools_and_after_expiry` | the Super Admin's link | HQ staff are signed out of the panel; platform staff with a Super Admin role, another school's Super Admin, a changed reader, a second Super Admin are refused; the link expires after 15 minutes; after the deadline the export is gone (410); the deadline command records receipt |
| `test_the_deletion_dry_run_counts_rows_and_deletes_nothing` | an archived school | every table's row count is unchanged, the school stays archived, the report matches the real counts, kept tables are not in the delete list, `deleted` is 0 |
| `test_deletion_needs_a_second_approval_from_a_different_owner_after_the_export_is_rechecked` | two Owners | approving before a request is 409; the requester cannot approve; a legal hold voids the request and blocks it; a tampered export copy blocks it; a different Owner approves, a fresh dry run is stored and no row is deleted |
| `test_starting_is_validated_and_each_step_waits_for_the_one_before` | Operations | the default school, no Super Admin, a deadline under a week, missing or early deletion terms, a deletion term without an end date are refused; one offboarding at a time; no archive, dry run or deletion before receipt; a failed build can be retried; receipt needs a download; cancelling discards the copy |

`SaasPanelTest` counts the new `/saas` routes and checks each names its ability.

## Not in this step

- **Deleting the school's data.** The approvals and the dry run are built; the
  deletion itself (removing the counted rows through `SchoolOwnedTables` in
  dependency order, ending or removing accounts only this school knows,
  removing the school's stored files and the export copy, on or after the
  deletion date) is not. It needs its own review and a restore-tested backup.
- **Stored files** (documents, photos, report PDFs, gallery) are not in the
  export and not counted by the dry run; the manifest says so.
- **Large schools:** the archive is built and encrypted in one piece (up to
  256 MB), within the 150-second job timeout; a build that takes longer fails
  and can be prepared again. Chunked encryption, streaming downloads and a
  provisioned long-running worker for big exports are for a later step.
- **Copying the export to the school another way** (a second channel, a
  hardware copy) and a school-side verification tool for the signature.
- **HQ timeline entries** on the school record for each step (the audit log
  has them).
- No app or `/api/v2` part: the receipt is in the panel only.
