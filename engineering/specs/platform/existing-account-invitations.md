# Inviting an existing account into another school

Action Plan step 1.3 (Build 1). Owner role: Backend and Identity. Builds on
[staff invitations](staff-invitations.md) (1.11), [tenancy](tenancy.md) and the
[platform admin area](saas-panel.md).

One person has one NavigateEd account, whatever schools they work at. Before
this step a school could not give a role to someone who already had an account:
the staff forms answered "The email has already been taken" (which also told the
school that the address was in use somewhere), HQ refused an existing address as
a new school's first administrator, and the staff import gave the role to the
existing account straight away, without asking its owner. Now the school records
an **invitation to join**, the person accepts it while signed in as their own
account, and only then do they get a membership and the role there. Their
password never changes.

## User stories

- **Teacher at two schools:** School B adds me as a teacher with the address I
  already use at School A. I get an email from School B, sign in with my usual
  password, accept, and see both schools in the school switcher. Nothing at
  School A changes, and School A is not told.
- **Principal moving schools:** HQ sets up my new school with me as its first
  administrator. I accept with my existing account and become its Super Admin;
  I stay at my old school until it ends my role there.
- **School office:** adding staff reads the same whether the address is new or
  not. I see invitations that are still waiting, and can withdraw one.
- **Invitee:** I can decline. A link forwarded to someone else does nothing for
  them.
- **HQ:** when the first administrator's address belongs to an account that may
  not be one, I am told and nothing is created.

## Rules

### Inviting

- **Where it starts.** The school panel's Add teacher and Add staff forms
  (`TeacherController::store`, `StaffController::store`), the staff import
  (`StaffImport`), and HQ's school set-up (`PlatformSchools::create`). Each
  first asks `SchoolJoinInvitations::classify(email)`:
  - *new*: no account has the address. The existing path creates one, as before.
  - *known*: the account already has a membership here (any role or status), an
    invitation to join here is waiting, or its owner declined one from this
    school in the last 180 days (`DECLINE_QUIET_DAYS`: they said no, and a
    refused re-add looks like the refused re-add of a new person already on the
    staff). Refused as a duplicate, with the same message as before ("The email
    has already been taken."). After an expiry or a withdrawal the school may add
    the address again, which sends a new invitation.
  - *elsewhere*: an account with no link to this school. It is invited; no
    second account is made.
- **The exact address.** The database matches email addresses without regard
  to case or accents, so "josé@" finds the account "jose@". Only the address the
  school typed, ignoring letter case, is the account's: anything else is
  withheld (never emailed, `address_mismatch`), and for HQ refused. The
  invitation keeps what the school typed (`encrypted_email`), never the account's
  own address.
- **Who may invite.** The same people who may add that kind of staff:
  `teacher-create` for Teacher, `staff-create` for the school's own roles
  (`custom_role`). Never Super Admin, Parent or Student from a school; only HQ
  invites a first administrator. The service checks this again
  (`SchoolJoinInvitations::mayAdd`), on fresh rows, with an active staff
  membership here.
- **One live invitation per person and school.** A newer one closes the older
  one (`used_at`), so an older link opens nothing.
- **Expiry.** Seven days (`identity.invitation_days`), like staff invitations.
- **The email** names the inviting school and the role, says the person already
  has an account and needs no new password, and links to the invitation page.
  It goes out after the transaction commits, and a queued email is suppressed at
  delivery if the invitation is no longer valid (`SchoolJoinInvitationMail::mayDeliver`).

### Who may not join this way (withheld)

An account is not invited, and the school is not told, when it is:

- deleted, deactivated, or without a valid email address;
- a **platform staff identity** (`platform_staff_identities`, active or not):
  HQ staff never get school roles;
- a **learner account**: any Student membership, at any school, of any status;
- unable to sign in to the panel today: it has no active membership, or its
  roles at its first school (where the sign-in form works) are Parent or
  Student, which the panel refuses.

For a school, such an address gets an invitation row with `withheld_at` set. It
is never emailed, its secret opens nothing, and the school sees it wait and then
expire like any unanswered invitation. The reason is recorded only in the audit
row `school_join_invitation_withheld`, which only HQ can read. For HQ's
first-administrator set-up the refusal is shown instead (below).

### Accepting and declining

- **Signed in as that account.** `/invite/join/{token}` sends a guest to the
  normal sign-in form (`redirect()->guest`, with "Sign in with your existing
  NavigateEd password to see your invitation."), the same for every link, valid
  or not; after signing in they come back to the link. Signed in, the page shows
  the school, the role, what the school will be able to see (name, email and the
  other details on their profile), and Accept and Decline.
- **Another account signed in:** 403, "This invitation is for another account",
  with a sign-out button; nothing changes and the invitation still waits for the
  right person.
- **Not valid any more:** "not found" (404) when the link is expired, used,
  declined, replaced by a newer one, withdrawn by the school, or withheld; when
  the school is suspended or archived; when the account's email no longer matches
  the address it was sent to (hash kept at issue); when the role was deleted; or
  when the account has since become a learner or platform staff.
- **Accepting** (`SchoolJoinInvitations::accept`, under lock, inside the
  inviting school): `MembershipService::grant` and the school's own role through
  Spatie (`assignRole`), a `teachers` row (with the qualification the school
  typed, or "Not recorded") for Teacher or a `staffs` row for other staff roles
  (none for a first administrator, as for a new one). The password, email
  verification, app tokens and browser sessions are left exactly as they were.
  The panel then opens in the new school, as the school switcher does.
  Audited at the inviting school: `school_join_invitation_accepted`.
- **Declining** marks it declined (`school_join_invitation_declined`). Nothing
  is attached.

### The school's side

- **Invitations to join** (`/join-invitations`, Staff menu, `staff-create` or
  `teacher-create`): the invitations still waiting, with only what the school
  typed (name, address) and the role and expiry. Withdrawing one
  (`school_join_invitation_withdrawn`) makes its link open nothing; the role's
  usual permission is needed, and a Super Admin for a first-administrator
  invitation. Answered, withdrawn and expired invitations are not listed.
- **The staff import** never gives a role to an existing account. Someone
  already on the staff here is *already here*; a learner's account here is an
  error ("A learner's account cannot be added as staff."); a deleted, inactive or
  suspended account here is an error, as before. Every other existing account (a
  parent here, someone who left, another school's teacher) is invited to join, so
  its owner decides; one that may not join is withheld, silently.
- **Set-password links.** Someone who also belongs to another school already
  chose their password there. A school's "Send sign-in invitation" button for
  them answers as usual but sends nothing (`staff_invitation_withheld`), and the
  staff import does not send one either, so no school can reset another school's
  colleague's password or sign them out everywhere. They use "Forgot password"
  if they need to.

### An account shared with another school belongs to its owner

Once an account belongs to more than one school (it accepted an invitation, or
"Add a guardian" linked it), no one school owns its `users` row. Otherwise
School B could change the person's email address, use "Forgot password" and
take over their access at School A, or deactivate or delete the account and
lock them out of School A.

- **Shared** (`SharedAccounts::shared`): the person has a membership that has
  not ended at a school other than the current one, or is platform staff.
- **Edits.** The teacher, staff and parent edit forms and the profile form may
  not change a shared account's name, email address, date of birth, gender,
  mobile number, addresses or photo. A changed value is refused with "This
  person's name, email address and other account details belong to their own
  NavigateEd account, and only they can change them." The school's own records
  (qualification, custom fields, its parent row, its roles) still save. Only the
  person, or HQ, changes the account.
- **The teacher form** now finds the teacher among this school's teachers by
  both ids (`teachers.id` and `user_id`) before anything is written. Before
  this, any user id a school could see (a guardian's, say) had its account
  rewritten.
- **Deactivate** (teacher status) ends or restores only this school's Teacher
  role and membership. The global `users.status` and the person's app sign-ins
  are untouched. The teacher list shows such a teacher as inactive.
- **Delete** (teacher, staff) deletes this school's teacher or staff record
  and ends this school's staff roles. The account, its photo and its sign-ins
  stay. Deleting a learner whose legacy parent also belongs elsewhere ends that
  parent's Parent role here instead of deleting their account.
- **Backstop.** `User` saving and deleting call `SharedAccounts::guard`. Inside a
  school's request, by anyone but the person, a change to those details or
  `status`, or a delete, of a shared account is refused (409). Platform code
  (no school, across schools, impersonation by HQ support), the console and
  queued work are not limited.
- **Dual-role learners stay possible.** The review asked for the role listener
  to refuse a staff role for any learner. Existing features rely on a learner
  who is also a teacher at the same school (`ConsultationStaffAudienceTest`,
  `GroupChatDomainTest`), so the listener is unchanged. The paths of this step
  (join invitations, the staff import) refuse learners themselves.

### HQ: the first administrator

- `PlatformSchools::create` with an address that already has an account makes no
  second account. The school is set up as before and the person is invited to be
  its first administrator (`purpose` `school_admin`, role Super Admin). Until
  they accept they have no role there; their password is never changed.
- An account that may not (the withheld list above) is refused before anything
  is created, with one of two messages: one for an account that cannot sign in
  to a school panel at the moment, one for every other reason (learner, platform
  staff, inactive). HQ is trusted with this; schools never are.
- The result page says the address already had an account and shows the link
  once, as for a new administrator (it only works signed in as that account).
  "Send the invitation again" on the school page replaces it while nobody has
  answered it (it may have expired). After the person accepts, declines, or the
  invitation is withdrawn, it is no longer offered. The onboarding checklist counts it as the administrator step.
- The create form no longer refuses an existing address (`StoreSchoolRequest`).

## Privacy decisions (account enumeration)

| Channel | Decision |
| --- | --- |
| The add-staff answer | Identical JSON for a new address, an account elsewhere and a withheld account. Only an address this school already knows is refused, as before. |
| Adding the same address again | Refused as a duplicate while an invitation waits or for 180 days after a decline, as a new person on the staff would be. **Known leak:** after an invitation expires or is withdrawn, adding the address again invites again, where a new person who was added would still be on the staff. A school can tell from this that the address had an account (never where). |
| The staff import | An account elsewhere reads exactly like a new person (*will be imported*, same notes, same counts). The "cannot be added" error is kept only for accounts this school already knows. |
| The school's audit | Every add writes one `staff_added` row at the school with the role and who added, no subject and no account id, whatever happened. The invitation's own rows (sent, withheld with its reason, accepted, declined, withdrawn) are for HQ: `audit_log` has no school-facing view. |
| Timing | The invitation path computes one password hash, about the cost of creating an account. It is not constant-time; it removes the obvious difference. **Known leak:** a sent invitation also renders and queues its email after the commit, and a withheld one does not, so a withheld address answers a little faster than an invited one. |
| Emails | The school never sees which email went out. A new person gets the existing welcome email; an existing account gets the invitation; a withheld one gets nothing. |
| The waiting list | Shows only what the school typed. Withheld rows look like any other waiting invitation, so the list cannot tell a learner or platform staff account from an ordinary one. **Known leak:** the list itself holds only addresses that had an account; a new person is never on it. It tells the school no more than the staff list does (below). |
| The invitation page | A guest is redirected to sign in for every link, valid or not. The school is named only to the signed-in invitee. |
| The school's other pages | The invited person is not on the staff list until they accept, while a new person is on it at once. A school that watches the staff list can infer that an address it added has an account somewhere, but never where. This is the price of asking the person first. Hiding it would need a placeholder staff record for every invitation (Not in this step). |
| The other school | Learns nothing: no notice, no audit row in its name, no change to its memberships, roles or records. |
| Edit forms (email uniqueness) | **Known leak, not changed here.** The edit forms for a school's own people still check the new address against every account (`unique:users,email` in `StaffController`, `TeacherController`, `ParentsController` and `ProfileForms`), and the learner admission form checks new parents' addresses the same way (`StudentController`). Changing someone's address to one that has an account anywhere is refused, which tells the school the address exists. One person has one address platform-wide, so such a change can never simply succeed. The fix is an address change confirmed from the new mailbox, answering the same either way. That is its own step. |
| Shared accounts' details | A school learns that one of its own people also belongs elsewhere (never where) only when it tries to change their account details and is refused. |

## Parents and learners at a second school (unchanged)

- **Parents** already work at a second school: the office's "Add a guardian"
  ([guardian management](guardian-management.md), `GuardianLinks::add`) finds the
  existing account by email and links it at once, with the Parent role and a
  `parents` row. **The person is not asked**: a verified guardian gets no email;
  an unverified one gets a guardian invitation that verifies the address but does
  not ask for consent. The add form's error for a deleted, inactive or
  learner-owned address can also tell the school that the address has an account.
  Not changed here; worth its own step (consent for linking an existing parent
  account, and the same enumeration rules as staff).
- **Learners** move schools through the transfer and LURITS flows
  ([LURITS](lurits.md)); a learner account is never invited as staff here.

## Data

`school_join_invitations` (migration `2026_11_01_160000`), required school-owned
(`BelongsToSchool`; found by its secret with `acrossSchools()` before the school is
known): `user_id`, `role_id` (null if the school deletes the role), `purpose`
(`staff` or `school_admin`), `created_by`, `encrypted_name` and `encrypted_email`
(what the school typed), `qualification`, `token_hash` (SHA-256 of the
64-character secret, unique), `encrypted_token` (to re-send), `email_hash`,
`expires_at`, `used_at`, `accepted_at`, `declined_at`, `withdrawn_at`,
`withdrawn_by`, `withheld_at`. The secret, the hashes and the typed values are
hidden from the model's array form and never audited. `down()` refuses to drop
the table while it holds rows.

## Routes

| Method | Path | |
| --- | --- | --- |
| GET | `/invite/join/{token}` | the invitation page; guests are sent to sign in (`throttle:30,1`) |
| POST | `/invite/join/{token}/accept`, `/decline` | `auth`, `throttle:20,1`, `AnswerJoinInvitationRequest` |
| GET | `/join-invitations` | the school's waiting invitations (panel, `school.context`) |
| POST | `/join-invitations/{invitation}/withdraw` | `WithdrawJoinInvitationRequest`, `throttle:30,1` |

No /saas route is added (re-sending uses the existing administrator-invitation
route) and no API route or `openapi.yaml` change.

## Acceptance tests (`Platform/ExistingAccountInvitationsTest`)

| Given | When | Then | Test |
| --- | --- | --- | --- |
| Tara, a teacher at School A | School B adds her as a teacher | the usual answer, no second account, nothing attached; she is emailed; as a guest she is sent to sign in and comes back; she accepts | Teacher membership and role at B, B's teacher row with the typed qualification, same password and app tokens, audited at B, link then 404, both schools in the switcher | `test_a_teacher_at_another_school_is_invited_signs_in_accepts_and_keeps_her_password` |
| Tara's invitation | she declines | nothing attached, declined, audited; accepting afterwards 404 | `test_the_invitee_can_decline_and_nothing_is_attached` |
| A new address, Tara's, a learner's and a platform staff member's | School B adds each as a teacher | one identical answer; four identical `staff_added` rows; the learner and platform staff are never emailed and get nothing; the waiting list shows all three alike with only the typed name; adding each again is the same duplicate refusal | `test_the_school_is_answered_and_audited_the_same_whatever_the_address` |
| An invitation | its secret | not in the audit log or the model's array; only its hash is stored | `test_the_invitation_secret_is_never_in_the_audit_log_or_the_models_array` |
| Theo signed in with Tara's link | he opens, accepts or declines | 403; nothing attached to either; Tara can still answer | `test_accepting_as_another_account_is_refused` |
| Tara's link | expired, replaced, withdrawn by School B, School B suspended, or her email changed | 404 to page and accept, the queued email is suppressed, nothing attached, same password | `test_an_expired_used_superseded_withdrawn_or_readdressed_link_or_a_suspended_school_opens_nothing` |
| A learner's withheld invitation; Tara made a learner or platform staff after being invited | they accept | 404, nothing attached | `test_learner_and_platform_staff_accounts_never_join_even_with_a_link` |
| School A invites School B's Tara | School B's admin lists or withdraws it; School A's teacher lists, withdraws or adds | not listed, 404 and unchanged; 403, 403 and no invitation | `test_school_b_cannot_see_or_withdraw_school_a_invitations_and_a_teacher_cannot_manage_them` |
| School B's staff import with a new address and Tara's | check, import, check again | both rows read the same; counts 2 and 2; Tara is invited, not given the role, and gets no set-password link; then both are *already here* | `test_staff_import_invites_an_account_from_another_school_instead_of_giving_it_the_role` |
| Tara, now at both schools | School B sends her a sign-in invitation | the usual answer; no link, same password; `staff_invitation_withheld` | `test_a_set_password_link_is_never_sent_to_someone_who_belongs_to_another_school` |
| Tara joins School B as a custom role with one permission | her roles and permissions are read at each school | School A's are exactly as before; School B's are exactly that role's; each school's teacher and staff rows stay its own; School A's permissions open nothing at School B | `test_the_new_membership_opens_nothing_at_the_first_school_and_the_first_opens_nothing_at_the_new_one` |
| Tara at both schools | School B edits her email, name or date of birth on the teacher form; saves only a new qualification; deactivates and reactivates her; deletes her | refused, nothing changed; the qualification saves; only School B's Teacher role ends and returns; only School B's teacher record and role go. Her account, sign-ins and School A access are unchanged throughout | `test_school_b_cannot_change_lock_out_or_delete_a_teacher_it_shares_with_school_a` |
| Tara at both schools, staff at School B | School B changes her email or surname on the staff form; saves unchanged; deletes her | refused; saved; only School B's staff record and roles end; account and School A unchanged | `test_school_b_cannot_change_or_delete_a_staff_member_it_shares_with_school_a` |
| Tara linked to School B only as a guardian | School B uses the teacher form with her user id and one of its own teachers, the parent form, the profile form; deletes the learner she is legacy guardian of | refused, 422; her account unchanged; her Parent role at School B ends, her account and School A access stay | `test_an_account_linked_to_school_b_only_as_a_guardian_is_not_school_bs_to_change_or_delete` |
| School A's staff import with its own learner, School B's learner and Farid (a parent at School A) | check, import | the learner here is an error; the others read like new people; neither gets a role or a set-password link; both get an invitation, the learner's withheld | `test_the_staff_import_never_gives_a_role_to_a_learner_or_a_known_non_staff_account_without_consent` |
| Tara declined School B | School B adds her again; after 180 days; after withdrawing that one | refused as a duplicate; invited; invited | `test_adding_someone_again_after_they_declined_reads_as_a_duplicate_but_after_a_withdrawal_invites_again` |
| An account "jose@", School B types "josé@"; Tara's address in capitals | add as teacher | the usual answer; José's invitation withheld, with the typed address kept; Tara's sent, with her typed capitals kept | `test_only_the_exact_address_is_invited_and_the_school_keeps_what_it_typed` |
| HQ's first administrator declined | HQ opens the school | no "Send the invitation again"; re-send does nothing | `test_hq_is_not_offered_a_resend_after_the_first_administrator_declines` |
| HQ sets up a school with Tara's address (any case) | a learner's, a platform staff member's or a parent-only address first; then Tara's; re-send; Tara accepts | refused with nothing created; then the school, no second account, nothing attached until she accepts; the old link 404; she becomes Super Admin there and stays a teacher at School A; re-send no longer offered | `test_hq_sets_up_a_school_whose_first_administrator_already_has_an_account` |

Changed: `People/SchoolImportsTest` (Farid, a parent at the school, is now invited rather than given the Teacher role, and gets no set-password link).

## Not in this step

- **Email changes confirmed by the new mailbox**, which would close the
  uniqueness leak in the edit forms (privacy table) and let a person change
  their own address with proof.

- **The apps.** Accepting is in the browser only. A v2 endpoint to list and
  answer one's invitations (and a push when one arrives) would need an API
  contract and app releases. `/me/context` already lists every school with its
  roles once accepted, so nothing in the API shape changes.
- **Parent-only accounts invited as staff.** The panel's sign-in refuses an
  account whose roles at its first school are Parent or Student, so such an
  account is withheld today. Supporting it needs the sign-in to work in the
  school the person chooses.
- **Consent and enumeration for parents** linked by "Add a guardian" (above).
- **A placeholder on the staff list** for an invited person, so the list itself
  cannot hint that an address had an account.
- **Teacher form extras** for an invited teacher: the photo, the school's custom
  teacher fields and the "grant learner permissions" box are not carried over;
  the school sets them after the person accepts.
- **Reminders** before an invitation expires, and a "send again" button on the
  school's list (withdraw, then add again, works).
- **Which side must accept** (an open product question): this step asks only
  the person, because the school's own staff already chose to invite them.
