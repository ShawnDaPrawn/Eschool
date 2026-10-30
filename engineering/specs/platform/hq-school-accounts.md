# HQ school accounts

Action Plan SA.4 (school record and lifecycle) and SA.5 (contracts and POPIA
records). Navigate Ed's own record of each customer school, kept in the
platform area (`/saas`). It is about the school as a customer; it never shows
the school's learners, parents or staff, and the school never sees it.

## Model

School-owned tables written only from `/saas` through
`PlatformSchoolAccounts`, inside the school (`CurrentSchool::run`), so every
row and audit entry carries the school:

| Table | Holds |
| --- | --- |
| `school_accounts` | one row per school: stage, school type, phase, curriculum, province, district, EMIS number, address, expected learners, revision |
| `school_contacts` | the people Navigate Ed works with there: role (principal, deputy principal, bursar, IT, Information Officer, other), name, email and/or phone |
| `school_contracts` | start, end and renewal dates, contracted learners, monthly price per learner before VAT in integer cents, signatory, operator agreement version and signed date, data processing addendum date, notes |
| `school_account_events` | the timeline: notes, stage changes, contacts added or removed, contracts recorded, with who and when |

## Rules

- **Stages:** Lead, Demo, Contracted, Onboarding, Live, At risk, Churned. A
  school without a record shows as Onboarding. The HQ list shows each school's
  stage; a change of stage is written to the timeline.
- **Optimistic edits:** the profile carries a revision; a stale save is a 409
  and changes nothing.
- **Contracts are history.** They are recorded once and never edited or
  deleted; a change is a new contract. The operator agreement must already be
  signed (not a future date), the end date is after the start, and a rand price
  with at most two decimals becomes integer cents without floating point.
- **A contact needs a way to reach them:** an email address or a phone number.
- **Isolation:** each id resolves inside the school in the URL, so another
  school's contact is "not found". Only an active platform staff identity with a
  fresh grant reaches any of it; a school's Super Admin does not.
- **Audited:** `platform_school_account_saved`, `platform_school_contact_saved`,
  `platform_school_contact_removed`, `platform_school_contract_recorded`.

## Routes (`/saas/schools/{school}/…`)

| Method | Path | |
| --- | --- | --- |
| GET, PUT | `account` | the record page; save profile and stage |
| POST, PUT, DELETE | `contacts`, `contacts/{contact}` | add, change, remove a contact |
| POST | `contracts` | record a contract |
| POST | `notes` | add a timeline note |

## Acceptance tests (`Platform/HqSchoolAccountsTest`)

| Given | When | Then |
| --- | --- | --- |
| Platform staff | they record profile, a contact, a contract and a note | saved, R24.50 stored as 2450 cents, timeline has stage, contact, contract and note, the list shows Live, the contract is audited, nothing reaches the other school |
| A saved profile | a stale save; an unknown stage or province; a contact with no email or phone; a three-decimal price; an end before the start; a future agreement date | 409 or validation errors, nothing changed; contracts have no edit or delete route |
| Another school's contact | edited or removed through this school's URL | not found; the right school's URL works |
| A school Super Admin, a teacher, a guest | they open or write the record | 403, or sign-in |

Browser: `e2e/tests/saas-panel.spec.ts` opens the record at 390, 820 and 1280
pixels (accessibility and fit).

## Not yet

Uploading the signed contract document, renewal reminders, the onboarding
checklist (SA.6) and invoices from the contract (SA.8, 1.17).
