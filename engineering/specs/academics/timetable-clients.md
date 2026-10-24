# Timetable workbench and shared clients

Use the canonical timetable services for a Material3 panel workbench and the
shared academics week screen. Managers configure rooms/bells, place lessons and
import a sheet through a conflict preview before committing its fingerprint.
The week editor lists actual dates, periods and rooms; editing/cancelling uses
original revisions. Both widths remain usable and all text/labels are escaped.

Teachers, learners and parents use a shared adaptive week view. Staff with fresh
manage rights receive the editor; families never fetch configuration, staff
matrices or learner rosters. A next-period home card shows room and teacher
without a tap. Existing phone timetable routes remain available. School/child
changes clear private screens; stale requests cannot replace a new context.

Use explicit school IDs for every request. Initial dates and year resolve in the
school timezone on the server, so a phone's local date cannot select the wrong
week. Server clash previews control save; clients do not infer safety from period
IDs. Failed/stale saves preserve inputs and require a fresh preview, without
silently retrying a conflicting write. Test cubit races, permissions, empty/error
states, phone/tablet/desktop layouts, app browser flows and panel Playwright at
both widths. Do not equate synthetic fixtures with actual 20-minute classroom
week-building or physical next-room acceptance.


Panel import stages inspect bounded UTF-8 CSV/TSV, match columns, explicitly map
exported values and preview the atomic batch. No uploaded label is trusted HTML;
value maps serialize into a bounded JSON field to avoid PHP input-count limits.
Rooms and bell schedules use the same audited services as the API. Used schedules
are copied to retain period provenance. Permission checks precede every screen
and write. A conflicting commit renders a 409 with the original placement.

The API supplies optional school-year choices for managers and resolves a missing
week date using the school timezone. Read-only users aggregate authorized lessons
across overlapping calendars; managers can choose their editing calendar. The
home card refreshes once per minute and on resume and resets its future when the
school or child changes. Inactive allocations are suppressed by server reads.
