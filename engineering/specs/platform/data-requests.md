# POPIA data requests

Action Plan SA.11 (the data request part). Owner role: Backend and Admin UX (HQ and school panel).

Under POPIA a person may ask to see the personal information held about them (section 23), to have it corrected or deleted (section 24), or object to how it is used (section 11(3)). Each school is the **responsible party** for its learners', parents' and staff's information, and Navigate Ed is its **operator** (section 21). When such a request reaches Navigate Ed, by email, phone, letter or the POPIA form, Navigate Ed does not decide it. It logs the request with a deadline, passes it to the school, and follows it until the school has answered. This page is where that record is kept.

## User stories

- As Navigate Ed support or operations, I log a request the day it arrives, with who asked, how to reach them, what they asked for and the school it is about, so it never depends on someone's inbox.
- As Navigate Ed, I pass it to the school straight away. The school's Super Admins are told without anyone being named, and I see every open request by its deadline, with overdue ones marked.
- As a school's Super Admin (by default its Information Officer), I see the requests passed to my school with the requester's details, decide them, answer the person, and record what we did, so Navigate Ed can close the request.
- As another school, I never see a request about a different school.
- As Navigate Ed, I can show the Regulator, from one page, when each request arrived, when it was passed on, what the school answered and when it closed.

## Rules

- **HQ: who.** The ability `privacy.manage` (Owner, Operations, Support) reaches every HQ page and form. Sales, Finance and Content do not see the "Data requests" link and are refused (403). School accounts never reach `/saas`.
- **School: who.** Only the school's Super Admin, in that school, can see the school's Data requests page (Settings > Policies > Data requests) or answer a request (route `role:Super Admin`, the form request and the service each check). Teachers and other staff are refused (403).
- **Log.** Each request has:
  - the school it is about;
  - what is asked (access, correction, deletion, objection);
  - how it reached us (email, phone call, letter, POPIA form, in person);
  - who is asking (parent or guardian, learner, former learner, staff member, someone else), with their name and how to reach them;
  - what they asked for, in their words where possible;
  - the day it was received: not in the future and not more than a year ago;
  - the answer-due date. It defaults to 30 days after receipt, the PAIA section 25 period for access requests, used for every kind unless HQ sets another. It may be any day from receipt to 90 days after (an agreed extension).
- **Reference.** `DR-0001`, from the row id.
- **Status.** Received → with the school (passed) → answered by the school → closed. A request that has not been answered can instead be recorded as **withdrawn**, with how. Any other move is refused (409): passing twice, answering twice, or closing before an answer.
- **Passing.** Passing a request tells each of the school's active Super Admins with a `school.data_request` notice. The notice is mandatory and locked, and says only that a request is waiting and when it is due, with a link to `/data-requests`. Its email links to the staff panel (`app.url`), not the learner portal. A school with no active Super Admin cannot be passed a request: HQ is told to contact the school first. The school's Information Officer and principal from HQ's school record (SA.4) are shown on the request page.
- **Answering.** The school records what it did (done as asked, partly done, refused) and what it told the person, including why if it refused any part. The answer cannot be changed afterwards.
- **Closing.** HQ closes an answered request with a short note of what Navigate Ed did to finish, for example deleting backups the school asked about.
- **Overdue.** A request that is received or with the school after its due date shows "Overdue" in the HQ list and on the school's page. The HQ list also shows the days left.
- **Privacy.**
  - The requester's name, contact and request, the school's note and the closing note are encrypted at rest (`encrypted` casts) and hidden from model serialisation.
  - The HQ list shows only reference, school, kind, status and dates. The requester appears only on the request's own page.
  - Audit records ids, kinds, dates, states and outcomes, never the free text: `platform_data_request_logged`, `_passed` (with the number of people told), `_answered`, `_closed`, `_withdrawn`. Each is recorded against the school and shows in the HQ audit log (SA.3).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_data_requests` | required school-owned (foreign key to the school) | the request, its deadline, who passed, answered and closed it and when, the outcome and notes |

Rows are history: the migration's `down` refuses to drop the table while it holds rows.

## Acceptance tests (`Platform/DataRequestsTest`)

| Given | When | Then |
| --- | --- | --- |
| Operations logs an access request for School A | it is saved | it is due in 30 days. The name and request are not readable in the raw columns. The list shows the reference and school but not the requester; the request page shows both. School A's Super Admin does not see it yet. |
| Support passes it | School A's Super Admin is told | one `school.data_request` notice to the Super Admin, naming no one; passing again is refused (409) |
| School A's Super Admin | opens Data requests and answers "done as asked" | the request is answered; a second answer is refused (409) and does not replace the first |
| Distinct staff and family origins; an in-person request | the notice is emailed; HQ opens the request | the link is the staff panel's `/data-requests`; the channel is stored whole and shown as "In person" (`test_the_email_links_to_the_staff_panel_and_in_person_requests_are_kept_whole`) |
| Operations | closes it with a note | it is closed; withdrawing it now is refused (409). The four audit entries hold no name or note. (`test_hq_logs_and_passes_a_request_the_school_answers_and_hq_closes_it`) |
| Sales, or a school Super Admin | uses any HQ data request page or form | 403, and nothing is written or changed |
| A teacher at School A | opens Data requests or answers | 403, and the request is unchanged (`test_roles_without_the_ability_are_refused_and_nothing_changes`) |
| A request for School B, which has no Super Admin | HQ passes it | refused with "contact the school first". Once School B has one, it passes, and nobody at School A is told. |
| School A's Super Admin | opens Data requests, or answers School B's request by id | does not see it; the answer is 404 and changes nothing. School B's Super Admin answers it. (`test_a_school_sees_and_answers_only_its_own_requests`) |
| HQ | logs a request received in the future, due over 90 days later or before it was received, or for a school that does not exist | refused, and nothing is logged |
| A request due on 10 November | HQ looks on 2 November, then on 12 November | "8 days left", then "Overdue". Once withdrawn, it is under "Recently closed" and no longer overdue. (`test_dates_are_checked_and_overdue_requests_stand_out`) |

## Not in this step

- Requests a school receives directly. The school answers those itself today; a school-side log could reuse this table later.
- Reminders before the due date, and telling HQ when the school answers. HQ sees it in the list.
- Attaching documents, such as identity proof or the copy sent.
- The rest of SA.11:
  - imports on a school's behalf;
  - exports;
  - visible retention runs;
  - school offboarding (full export, then archive and deletion).
- The apps (no API change).
