# Guardian management

Action Plan step 1.2. The school office manages a learner's guardians
directly: any number of them, not only the legacy father, mother and guardian
fields, each with a relationship and two rights.

## Model

`guardian_links` gains `source` (`legacy` for links that mirror the old
father/mother/guardian fields, set only when `syncLegacy` creates them;
`managed`, the default, for links the office adds or other paths such as a
transfer create),
`ended_at`, `ended_by` and `ended_reason`. Relationships: mother, father,
guardian, grandparent, aunt or uncle, sibling, foster parent, caregiver, other.
Rights: `can_approve` (approve the learner's access and school decisions) and
`can_see_fees`.

## Rules

- **Who:** a Super Admin, or office staff (not a learner, parent or teacher)
  with `student-edit` (`LearnerAccessPolicy::manageGuardians`). Learners and
  links resolve inside the current school; a link id from another learner's
  page is not found.
- **Legacy parents first.** Before any change, parents still held only in the
  legacy fields get their link (`GuardianService::syncLegacy`), so the list and
  the approver rule see every guardian.
- **Adding** by email links an existing account, or creates one (random
  password, change required). The person gets the school's Parent role (and a
  `parents` row, which the installed parent app needs). An unverified guardian
  is emailed a guardian invitation, which verifies the address before they can
  sign in or approve. The same person cannot be linked twice; a learner's own
  account cannot be their guardian; a deactivated or deleted account, or a
  suspended parent membership, needs review first.
- **At least one approver.** The learner's last active guardian who can
  approve cannot be removed or lose that right, and a guardian without the
  right cannot be added or reactivated while the learner has no approver; the
  office adds an approver first.
- **Ending** a link records who, when and why. When the person has no other
  child at the school, their Parent role there ends (so every school-scoped read
  refuses at once); when they have no child at any school, their parent-app
  sign-ins are revoked. Approvals they already gave stay as history.
- **Legacy edits never end a managed link.** Saving the old student form still
  keeps legacy links in step with its fields, but only links whose source is
  `legacy`. Clearing a field ends its legacy link even after the office has
  changed that link's relationship (say, to caregiver).
- **Audited** (`guardian_link_added`, `guardian_link_changed`,
  `guardian_link_ended`) with a `people.guardians_changed` context event.

## Panel

`/learners/{learner}/guardians`, linked as "Manage guardians" from the
learner's profile: current guardians with their rights (change, or remove with
a reason), an "Add a guardian" form, and past guardians.

## Acceptance tests (`Platform/GuardianManagementTest`)

| Given | When | Then |
| --- | --- | --- |
| Asha with two legacy parents | the office adds her grandmother | listed with the two parents; an account, Parent role, parent row and invitation; she is one of Asha's guardians; audited; adding her again is refused |
| Ben with one approving guardian | she is removed or loses the right | refused; after a second approver is added, she can be removed with a reason |
| Ben with no guardian left | a guardian without approval is added, or Gina reactivated so | refused; once an approver is added, both are allowed |
| Gina, guardian of Ben only | she is removed | no longer his guardian, Parent role ended, parent-app tokens revoked; Farid, removed from Asha, keeps access for Cara |
| A guardian the office added | the legacy student form clears a field | the managed link stays; the legacy link for that field ends, also after the office changed its relationship to caregiver |
| A teacher, a parent, a learner; another school's learner or link; another learner's link | they manage guardians | 403; not found; nothing changed |
| The panel | add, change (unticked boxes switch rights off), remove with a reason | saved; a too-short reason is refused; past guardians listed |

Browser: `e2e/tests/profile-fields.spec.ts` opens the guardians page at 1024
and 1280 pixels (accessibility and fit).

## Not yet

The approving guardian managing co-guardians from the parent app (needs a v2
API and an app release), and moving a guardian's consent history when a link
ends. Asking an existing account's owner before linking them as a guardian at a
second school, as staff are asked
([existing-account-invitations.md](existing-account-invitations.md)): today the
link is made at once, and the add form's refusal of a deleted, inactive or
learner-owned address can tell the school that the address has an account.
