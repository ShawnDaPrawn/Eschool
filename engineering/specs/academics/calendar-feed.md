# Calendar feed

Action Plan 1.10. Owner role: Backend (academics). Builds on the [timetable](timetable.md) and [exam mode](exam-mode.md).

A learner or a guardian can subscribe their phone or desktop calendar to the learner's school calendar. Exams are always in it; lessons for the next four weeks only when asked for.

## User stories

- As a parent, I want Asha's exams in my phone calendar, so I see them next to everything else and they update when the school changes them.
- As a learner, I want my lessons in my calendar too, so I know where to be.
- As a parent, I want to stop a link I shared by mistake, at once.

## Rules

- **Who.** The learner, or anyone who may see that learner's timetable in the app (`TimetablePolicy::authorise` for that learner: the learner and their current guardians). Staff and other families cannot make a link for a learner (403 or 404, nothing written).
- **The link.** `POST /api/v2/academics/calendar-feeds` returns `…/calendar/{token}.ics` once. The token is 48 random letters and digits; only its SHA-256 is stored. At most five live links per person and learner (422). Listing shows only your own live links, without their URLs.
- **Fetching.** The calendar app fetches the link with no sign-in and no school header. Each fetch finds the link by its hash, runs inside the link's school, and checks again that the person who made it is an active member of that school, their account is enabled, the school is active, the academics module is on, and they may still see the learner's timetable. Otherwise it is 404 (a notice is logged with the link id and reason, never the token). A school header on the request changes nothing. Fetches are limited to 60 a minute.
- **What is in it.** Every exam sitting of the classes the learner is placed in for the school years around today, from 30 days ago (title "Exam: subject", venue as location, exam name as description). When lessons were asked for: the learner's lessons for the next four weeks, exactly as the app's week view shows them (short days, exam days and relief cover included). Times are written in UTC from the school's timezone. Calendar apps are asked to refresh every six hours.
- **No replay.** Making a link is not idempotent: the response carries the secret, so it is never stored for replay (no `idempotent` middleware). A retry makes another link, within the limit of five, and the extra one can be revoked.
- **Not exams.** Exam rows that only project released assessment results (linked through `academic_exam_links`) are not sittings and are left out.
- **Revoking.** `DELETE /api/v2/academics/calendar-feeds/{id}` by the person who made it; anyone else gets 404. A revoked link is 404 at once.
- **Audit.** Making and revoking a link are audited with the learner id and the lessons choice.

## Data

`calendar_feeds` (required school-owned): school, user who made it, learner, token hash (unique), include_lessons, last_used_at (updated at most hourly), revoked_at.

## Acceptance tests (`Academics/CalendarFeedTest`)

| Given | When | Then |
| --- | --- | --- |
| Farid, Asha's guardian; Asha has a Tuesday maths lesson and a maths exam on 20 Feb | he makes a link with lessons and his calendar fetches it | only the hash is stored; the feed has the exam at 07:00 UTC with its venue, four lessons, no fifth week; the list shows the link without its URL; Asha's own link without lessons has the exam only |
| Gina (Ben's guardian), Asha asking for Ben, Tara (teacher) | they make a link for Asha (or Ben) | refused, nothing written; Gina cannot revoke or list Farid's link; Farid revokes it and it is 404 |
| Farid's guardian link ends; Asha's membership ends; Asha's account is disabled | the links are fetched | 404 each time; Asha's own link keeps working until her own access ends |
| School B | lists, revokes, or makes a link for a School B learner through School A; a fetch says School B | nothing listed; 404; refused; the same calendar as without the header |
| Five live links | a sixth is made; a made-up or malformed token is fetched | 422; 404 |
| The same creation sent twice with one Idempotency-Key; a released-results exam row | made; fetched | two different links, no idempotency row holds the token; the projection is not in the feed |

## Not in this step

- Activities: activity sessions have no times yet (Action Plan 1.6).
- The apps' screen to make, copy and revoke links (the API is ready).
- Term dates and school events in the feed.
