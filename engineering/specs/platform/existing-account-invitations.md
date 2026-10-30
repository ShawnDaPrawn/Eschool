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
  - *known*: the account already has a membership here (any role or status), or
    an invitation to join here is waiting. Refused as a duplicate, with the same
    message as before ("The email has already been taken."). The school already
    knows this person, or added them a moment ago.
  - *elsewhere*: an account with no link to this school. It is invited; no
    second account is made.
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
- **Set-password links.** Someone who also belongs to another school already
  chose their password there. A school's "Send sign-in invitation" button for
  them answers as usual but sends nothing (`staff_invitation_withheld`), and the
  staff import does not send one either, so no school can reset another school's
  colleague's password or sign them out everywhere. They use "Forgot password"
  if they need to.

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
  "Send the invitation again" on the school page replaces it until it is
  accepted. The onboarding checklist counts it as the administrator step.
- The create form no longer refuses an existing address (`StoreSchoolRequest`).

## Privacy decisions (account enumeration)

| Channel | Decision |
| --- | --- |
| The add-staff answer | Identical JSON for a new address, an account elsewhere and a withheld account. Only an address this school already knows is refused, as before. |
| Adding the same address again | Refused as a duplicate in every case: a new person is on the staff by then, and an invited or withheld one has a waiting invitation. |
| The staff import | An account elsewhere reads exactly like a new person (*will be imported*, same notes, same counts). The "cannot be added" error is kept only for accounts this school already knows. |
| The school's audit | Every add writes one `staff_added` row at the school with the role and who added, no subject and no account id, whatever happened. The invitation's own rows (sent, withheld with its reason, accepted, declined, withdrawn) are for HQ: `audit_log` has no school-facing view. |
| Timing | The invitation path computes one password hash, about the cost of creating an account. It is not constant-time; it removes the obvious difference. |
| Emails | The school never sees which email went out. A new person gets the existing welcome email; an existing account gets the invitation; a withheld one gets nothing. |
| The waiting list | Shows only what the school typed. Withheld rows look like any other waiting invitation, so the list cannot tell a learner or platform staff account from an ordinary one. |
| The invitation page | A guest is redirected to sign in for every link, valid or not. The school is named only to the signed-in invitee. |
| The school's other pages | The invited person is not on the staff list until they accept, while a new person is on it at once. A school that watches the staff list can infer that an address it added has an account somewhere, but never where. This is the price of asking the person first. Hiding it would need a placeholder staff record for every invitation (Not in this step). |
| The other school | Learns nothing: no notice, no audit row in its name, no change to its memberships, roles or records. |

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
| HQ sets up a school with Tara's address (any case) | a learner's, a platform staff member's or a parent-only address first; then Tara's; re-send; Tara accepts | refused with nothing created; then the school, no second account, nothing attached until she accepts; the old link 404; she becomes Super Admin there and stays a teacher at School A; re-send no longer offered | `test_hq_sets_up_a_school_whose_first_administrator_already_has_an_account` |

## Not in this step

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
