# HQ breach and incident register

Action Plan SA.12. Owner role: Backend and Admin UX (HQ).

Navigate Ed is the **operator** for each school's personal information; each school is the **responsible party** (POPIA). When there are reasonable grounds to believe personal information was accessed or acquired by someone not authorised, the operator tells the responsible party immediately (section 21), and the responsible party notifies the Information Regulator and the people affected as soon as reasonably possible (section 22). The register is where HQ keeps the record of each incident: what happened, which schools it affects, when each school was told, what the Regulator decision was, what was done, and how it closed. The steps to follow are in the incident runbook (`engineering/runbooks/incident.md`); the register is where they are written down.

## User stories

- As Navigate Ed support or operations, I record an incident when it is detected, with the schools it affects as soon as they are known, so nothing depends on someone's inbox.
- As the person handling it, I see what still stops the incident closing: affected schools not yet told, the Regulator decision, the Regulator date when notification is required, and the closing review.
- As an Owner or Information Officer at Navigate Ed, I can show the Regulator or a school, from one page, when each school was told and how.
- As a school, the register is never visible to me, and it never names my learners, parents or staff: forms ask staff to describe what happened, not to whom, and the audit log records ids and states only.

## Rules

- **Who.** The ability `incidents.manage` (Owner, Operations, Support) reaches every page and form. Sales, Finance and Content do not see the menu link and are refused (403). School accounts never reach `/saas`.
- **Record.** Title, kind (breach, security event, outage, data damaged or lost, other), severity (low to critical), whether personal information was or may have been reached (yes, not known yet, no), when it was detected (South African time, not in the future), when it happened if known (not after detection), what is known, and the affected schools (none if not known yet). A breach cannot say "no" to personal information.
- **Reference.** `INC-0001`, from the row id.
- **Status.** Open, contained (the time it was first contained is kept) and closed. A closed incident changes only by setting it back to open or contained (reopening clears the closing time and who closed it); adding schools or recording a notification on a closed incident is refused (409).
- **Closing.** Needs a closing review (impact, cause, fix, follow-up). When personal information was or may have been reached, every affected school must have been told and the Regulator decision recorded; a "required" decision needs the date the Regulator was told. Until then the page lists what is missing and the list shows it under "Needs attention".
- **Telling a school.** Per affected school: the date (not in the future, not before detection), how (email, phone call, meeting, letter) and whom (a role and name). A correction replaces the record and adds a timeline line. The school's Information Officer and principal from HQ's school record (SA.4) are shown beside each school.
- **Regulator.** Required or not required, with the reason, the date the Regulator was told and its reference. The school decides as the responsible party; HQ records the decision and helps.
- **Concurrent edits.** The details form carries the incident's revision; adding a school or recording a notification raises it, so a form opened before is refused (409, reload).
- **Timeline.** Every change of status, severity, personal-information answer, Regulator decision, school added and school told adds a line; staff add notes and actions (no names of people). The timeline shows the newest 100 entries a page at a time, with links to older and newer pages, so the whole history stays reachable.
- **Audit.** `platform_incident_opened`, `platform_incident_updated` (fields changed, status, severity, Regulator decision), `platform_incident_school_added` and `platform_incident_school_notified` (against the school), `platform_incident_entry_added`. None holds the free text. They show in the HQ audit log (SA.3).

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `platform_incidents` | global | the incident, its Regulator decision, closure and revision |
| `platform_incident_schools` | required school-owned (foreign key to the school) | one row per affected school: when, how and whom it was told, and by whom |
| `platform_incident_entries` | global | the timeline |

Rows are history: the migration's `down` refuses to drop tables that hold rows.

## Acceptance tests (`Platform/HqIncidentsTest`)

| Given | When | Then |
| --- | --- | --- |
| Support; School A has an Information Officer on its school record | record a breach affecting School A | the list shows "One school not told" and "Regulator decision not recorded"; the page shows the Information Officer; detection stored in the app's timezone |
| That incident | close it | refused until School A is told, the Regulator decision is recorded and, being required, its date given; then closed, with the timeline and audit rows (school notification audited against School A; no free text in the audit) |
| Operations, an outage with no personal information | a stale form; close without a review; change a closed incident; add a school or record a notification once closed; reopen | 409; refused; 409; 409; reopened with closing and containment cleared |
| Support | a future detection; an occurrence after detection; a breach with no personal information; an unknown school; a notification in the future or before detection; a Regulator date without "required" | each refused, nothing written |
| Sales, Finance, Content; a school Super Admin and a teacher | each of the seven endpoints with a valid payload | no menu link; 403 everywhere; nothing written |
| An incident with more than 100 timeline entries | open it; open page 2 | the newest 100 first, then the opening entry on page 2 |
| An Owner | record a notification for a school not on the incident, or for an unknown incident | 404; nothing written |

## Endpoints (HQ panel)

| Method | Path | Name |
| --- | --- | --- |
| GET | `/saas/incidents` | `saas.incidents.index` |
| POST | `/saas/incidents` | `saas.incidents.store` |
| GET | `/saas/incidents/{incident}` | `saas.incidents.show` |
| PUT | `/saas/incidents/{incident}` | `saas.incidents.update` |
| POST | `/saas/incidents/{incident}/schools` | `saas.incidents.schools.store` |
| PUT | `/saas/incidents/{incident}/schools/{school}` | `saas.incidents.schools.notified` |
| POST | `/saas/incidents/{incident}/entries` | `saas.incidents.entries.store` |

Every route carries `platform.can:incidents.manage`; every write uses `IncidentRequest` and the service validates the same rules again.

## Not in this step

- Sending the notice to a school from the register. Schools are told directly (phone first for a breach); announcements to schools are SA.13.
- Notifying the people affected: that is the school's decision as responsible party, with Navigate Ed's help.
- A school-side view of incidents that affect it.
