# Restricted wellbeing panel verification — 25 October 2026

The real panel provides category/team and case-assignment metadata separately
from incident triage, private case history, corrections/escalations, closure,
identified-incident linking and private appointment booking/change/cancellation.
A configuration grant never grants clinical body access. Dated case and team
assignments remain independent from support-plan and learner-note grants.

All private writes pin the expected account and school and read request bodies
only. Authorization precedes private draft reflection, including historical-year
refusals. Conflicts retain a bounded unsent draft in the response, require explicit
comparison, and issue a new operation ID for an intentional retry; no clinical
body enters session flash. Appointments use the school's calendar/timezone and
scoped dated professional choices; eligible selected professionals survive search
and pagination. Older case/report history remains readable.

Actual verification:

- Wellbeing domain, API, panel, public reporting, locale and controller guards:
  47 tests / 37,138 assertions passed; full Larastan zero errors and Pint passed.
- Actual Chromium: three roles at 1024/1280 pixels, 2 tests passed in 32 seconds.
  Admin metadata excludes confidential case/report bodies; assigned counselling
  saves an escalation, books an appointment and sends internal triage; an
  unassigned professional receives 404. AXE serious/critical, duplicate IDs and
  horizontal overflow checks pass.
- Public reporting read-only behavior keeps emergency guidance and existing
  threads available, hides write forms, and gives stale writes a private 423
  response with their unsent draft. Public page cohort: 5 tests / 67 assertions.

English labels are explicitly retained as fallback in Afrikaans/isiZulu pending
fluent and clinical-language review. This increment does not claim private media,
notifications, app screens, physical-device testing, staging or live clinical
response qualification. The module remains disabled by default.

After integrating the school-calendar membership fix, the panel/public/calendar
cohort passes 17 tests / 159 assertions. Panel TypeScript type-check passes.
The Android smoke workflow now separates family, teacher and facilitator rows;
teacher selects teacher flows, while facilitator flows use the named native
flavour and matching Dart define. Local Android execution is unrun; CI must
verify this newly selected emulator flow. Existing shared PHP style findings
were formatted without behavior changes.
