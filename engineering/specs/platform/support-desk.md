# Support desk

Action Plan SA.17. Owner role: Backend and Admin UX (school panel and HQ).

A school asks Navigate Ed for help from its own panel, and Navigate Ed answers from HQ (`/saas`). The plan row allowed a third-party help desk or a simple ticket table; this step builds the **simple ticket table**: no new package, no outside service and no network calls. Each ticket belongs to one school, shows on HQ's page for that school and writes a line on the school's timeline (SA.4) when it opens and when it closes.

## User stories

- As a school's Super Admin, or office staff who look after the school's settings, I open a ticket to Navigate Ed with a subject, what it is about (question, problem, request, billing), how urgent it is (normal or urgent) and a message, and I am told in the panel when Navigate Ed replies.
- As that school, I see all my school's tickets and Navigate Ed's replies, I add a message, and I close a ticket I no longer need.
- As Navigate Ed support or operations, I work one queue across every school, urgent first and then the oldest, I see what changed since I last looked, I reply, I keep internal notes the school never sees, I assign the ticket to myself or a colleague and I set its status.
- As anyone in HQ who can see a school, I see on its page how many tickets it has open; support staff also see its latest tickets and open them from there.
- As a school, nothing about my tickets reaches another school, and nothing my staff write lands in the audit log.

## Rules

- **Who, at the school.** The school's Super Admin, or office staff who hold `setting-create` and are not a teacher, parent or learner (the same rule as Settings > Email from your domain). Anyone else is refused (403), on every page and form. The menu item is Settings > School > "Navigate Ed support" (`nav_support_tickets`, permission `setting-create`). Every member who passes the rule sees all of the school's tickets: a ticket is the school's, not one person's.
- **Who, at HQ.** The ability `support.manage` (Owner, Operations, Support) reaches the queue, each ticket and every form; the platform bar shows "Support desk" only to them. Sales, Finance and Content are refused (403). School accounts never reach `/saas`.
- **Opening.** Subject (5 to 150 characters), category, priority and a message (10 to 5,000). The form asks the school not to paste learners' personal information (no names, ID numbers, contact details, marks or health details), as the HQ incident register asks of HQ staff. Reference `SUP-0001`, from the row id.
- **Statuses.** Open (the school sees "With Navigate Ed"), waiting on school ("Waiting for your reply"), resolved and closed. HQ sets any status. A message from the school puts a ticket that is waiting on the school or resolved back to open. The school can close its ticket; once closed, the school cannot add to it or close it again (409) and opens a new ticket instead. HQ can reopen a closed ticket by setting another status, which clears the closing time and who closed it.
- **HQ messages.** A **reply** is shown to the school as "Navigate Ed support" (never the staff member's name) and tells the person who opened the ticket. An **internal note** is never shown to the school and tells nobody. A reply to a closed ticket is refused (409, reopen it first); a note is allowed.
- **Assignment.** To any active platform staff member whose role holds `support.manage`, or to nobody. "Assign to me" is offered when the viewer can be assigned. Anyone else (a Sales operator, a school account, an unknown id) is a validation error.
- **The queue.** Open and waiting-on-school tickets across every school, urgent first, then oldest first (by when the ticket was opened), up to 200; below it the 50 most recently changed resolved or closed tickets. Each row shows the school, priority, status, when it opened and how many days ago, and the assignee.
- **New since you last looked.** Opening the queue records the time against the staff member (`platform_support_views`); a ticket is marked "New" when its `updated_at` is later than that staff member's previous visit (on a first visit every ticket is new). A message from either side, a status change and an assignment change `updated_at`, so the mark also shows a colleague's work and, after the staff member's own reply, their own; per-ticket read state is not kept.
- **Notification.** HQ's reply sends `school.support_reply` (module `school`, admin portal only, standard channel preferences) to the opener: title "Navigate Ed replied to your support ticket", body the ticket's subject, link `/support-tickets/{id}`. It never carries the message. The email link goes to the admin panel, not the family portal. HQ staff get no in-app notifications.
- **Timeline (SA.4).** When a ticket opens: "Support ticket SUP-0001 opened (problem, urgent)." When it closes: "… closed by the school." or "… closed by Navigate Ed."; when HQ reopens it: "… reopened by Navigate Ed.". Kind `support`, in English, never the subject or message. They show on HQ's school record with the other timeline lines.
- **HQ's school page** (`/saas/schools/{id}`). Every role that sees the page sees the open count (open and waiting on school). Roles with `support.manage` also see the school's five most recently changed tickets with links, and a link to the desk.
- **Privacy.** Messages are encrypted at rest (the model's `encrypted` cast; the column holds ciphertext). The audit log records ids and states only: `support_ticket_opened` (ticket and message ids, category, priority, status), `support_ticket_message_added` (ticket and message ids, side, internal, status; before: the status), `support_ticket_closed`, `support_ticket_status_changed` (status before and after) and `support_ticket_assigned` (assignee before and after), all against the ticket's school. None holds the subject or a message.
- **Past school years.** Tickets belong to no school year, so `support-tickets` and `support-tickets/*` are on the read-only-years allow list: a school viewing a past year can still ask for help.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `school_support_tickets` | required school-owned (foreign key to the school) | subject, category, priority, status, who opened it, assignee, when and by whom it closed |
| `school_support_ticket_messages` | required school-owned | each message: side (school or hq), internal (HQ note), encrypted body, author, time |
| `platform_support_views` | global | when each HQ staff member last opened the queue (one row per staff user) |

Models `App\Models\Platform\SupportTicket` and `SupportTicketMessage` use `BelongsToSchool`; HQ reads across schools explicitly and writes inside the ticket's school (`CurrentSchool::run`), so a message, timeline line or notice always belongs to the ticket's school. The migration's `down` refuses to drop the ticket tables while they hold rows.

## Acceptance tests (`Platform/SupportDeskTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_a_school_opens_a_ticket_hq_answers_it_and_the_school_closes_it` | School A's Super Admin; Support | the school opens an urgent problem ticket; Support replies, adds a note, sets "waiting on school" and assigns it to themself; the school reads, adds a message and closes it | stored for School A; the message is ciphertext at rest and reads back; the timeline says it opened and closed; HQ's queue shows it as new with the school and Urgent; one notice; the school sees the reply as "Navigate Ed support" and never the note or the staff name; the school's message reopens it; the audit actions in order |
| `test_office_staff_with_the_settings_permission_use_it_and_see_it_in_the_menu` | a custom "Front office" role with `setting-create` | open a billing ticket | it works; the menu has "Navigate Ed support"; a teacher's menu does not |
| `test_the_queue_is_urgent_first_then_oldest_and_marks_what_changed_since_the_last_look` | three School A tickets and a closed School B ticket | Support opens the queue; the school adds a message to one; Support opens it again | urgent, then oldest, then newer; the closed one is under recently resolved or closed; all new on the first visit; then only the changed ticket is new |
| `test_teachers_and_parents_are_refused_and_nothing_is_written` | a School A ticket | a teacher and a parent try all five school endpoints | 403 each; no ticket, message, timeline line, notice or audit row; ticket unchanged |
| `test_sales_finance_content_and_school_accounts_are_refused_on_hq_and_nothing_is_written` | a School A ticket | Sales, Finance and Content try all five HQ endpoints; a school Super Admin tries `/saas/support` | no menu link; the school page shows the count but no subject or reference; 403 everywhere; nothing written; no queue visit recorded |
| `test_another_school_cannot_see_reply_to_or_close_a_ticket` | a School A ticket | School B's Super Admin lists tickets, opens, replies to and closes it by id | not listed; 404 each; School A's ticket and messages unchanged; nothing written |
| `test_a_school_never_reads_hq_internal_notes` | Operations adds a note | the school opens the ticket and the list | the note is not shown; the service returns only the school's message; no notice |
| `test_the_reply_notice_tells_the_opener_with_the_subject_only` | Support replies | the notice is made and delivered (called directly) | one `school.support_reply` in School A, title, subject as body, link to the ticket, no reply text anywhere in it; the outbox event names only the opener; the opener gets it in the app; a teacher is not offered it |
| `test_the_audit_log_holds_ids_and_states_never_the_free_text` | a full exchange with marked words | read the audit rows and the timeline | five rows against School A with the ticket id, none containing the subject or any message; the timeline has none either |
| `test_hq_school_page_shows_open_tickets_and_the_latest_with_links` | two School A tickets (one closed), one School B ticket | Support opens each school's page | the open count, the school's own latest tickets and links; never the other school's |
| `test_closed_tickets_assignment_and_input_are_checked` | a ticket | bad input; assign to Sales or a school account; clear the assignee; a bad status; an unknown ticket; HQ closes, replies, notes; the school writes and closes; HQ reopens | validation errors and nothing written; 404; closed with a timeline line; reply 409, note saved; 409 and 409; reopened with closing cleared and a timeline line |

`SaasPanelTest` counts 41 `/saas` routes (five more), each with `platform.can`.

## Endpoints

School panel (`auth`, `school.context`; `SupportTicketRequest` checks the right and the service checks it again):

| Method | Path | Name |
| --- | --- | --- |
| GET | `/support-tickets` | `support-tickets.index` |
| POST | `/support-tickets` | `support-tickets.store` |
| GET | `/support-tickets/{ticket}` | `support-tickets.show` |
| POST | `/support-tickets/{ticket}/messages` | `support-tickets.messages.store` |
| POST | `/support-tickets/{ticket}/close` | `support-tickets.close` |

HQ (`platform.can:support.manage`; writes use `SupportDeskRequest` and `PlatformSupportDesk` checks the ability and validates again):

| Method | Path | Name |
| --- | --- | --- |
| GET | `/saas/support` | `saas.support.index` |
| GET | `/saas/support/{ticket}` | `saas.support.show` |
| POST | `/saas/support/{ticket}/messages` | `saas.support.messages.store` |
| PUT | `/saas/support/{ticket}/status` | `saas.support.status` |
| PUT | `/saas/support/{ticket}/assignee` | `saas.support.assignee` |

No API or OpenAPI change. HQ pages are English only; the school pages have en, af, zu and ar keys.

## Not in this step

- The school health page (SA.10) is on an unmerged branch; showing ticket counts there is left for when it merges.
- A third-party help desk, email-in tickets, attachments and canned replies.
- Notifying HQ staff (by email or in the app) of new tickets or school messages; the queue's "New" mark is the signal for now.
- Per-ticket read state for HQ staff, automatic closing of resolved tickets, and response-time targets.
- Tickets from the mobile apps or the API.
