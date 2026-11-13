# Revision 3 implementation: navigation, attendance, leave and school year

This implementation follows the approved revision 3 designs in `school-admin-design-v3` and retains Laravel's existing Material 3 components and domain handlers. It does not claim a Build Bible phase sign-off. The user authorised implementation of the PDF designs, including their missing facilitator leave and calendar contracts.

## Information architecture and access

- The drawer has the requested order: Home, People, Attendance, Leave, Academics, Extracurriculars, Awards, Discipline, Community, Vendors, Finance, Settings.
- Only People and Community are dropdowns. People has Learners, Parents, Teachers, Facilitators, Admin staff, Alumni; Community has Gallery, Newsletter, Events, Voting. Their primary destinations open actual permitted directories/editors. Module/group overview links retain the full permission-filtered task registry, search and separately selectable configuration.
- `config/navigation.php` projects the previous registry into these owners without deleting its original endpoints or permission/actor/module conditions. Additions expose actual facilitator/alumni directories, re-enrolment, bursaries/giving/sponsorship, subscription invoices, personal inbox/preferences and the new leave/calendar records.
- Activity-session, detention and emergency roll-call views resolve under Attendance while their configuration and operational records retain contextual links to the owning module.
- The global header and mobile drawer preserve school and viewing-year switching. The account menu exposes personal inbox, notification preferences and quiet hours. Scope-specific endpoints keep their original server guards.

## Source-backed screen coverage

| Design workflow | Implemented views and source behavior |
| --- | --- |
| Learner registration attendance, dated queue, per-period register, correction/conflict/review | `academics/register-workbench.blade.php`; existing `Registers`, `RegisterOverview` and `RegisterQueries` remain authoritative. The selected register is focused instead of showing queue/configuration simultaneously. |
| Attendance configuration | `academics/register-settings.blade.php` and `academic-registers.configuration`; explicit academic structure management authority; original cutoff and teaching-day save handler. |
| Daily learner attendance capture | `attendance/index.blade.php` + `attendance-workspace.js`; every enrolled learner is loaded through the source endpoint in bounded batches, approved leave stays locked, date constraints and holiday choice preserved, original `d-m-Y` store payload retained. No Bootstrap table dependency. |
| Daily attendance records / annual report | `attendance/view.blade.php`, `report.blade.php`; real year/class/term/status/date filters with shared Material table search/pagination/error states. |
| Monthly learner roster | `attendance/monthly.blade.php` + `attendance-monthly.js`; actual monthly endpoint with text status codes as well as colours, keyboard-scrollable table, loading/empty/retry states. The previous misleading staff-month nav entry is now labelled Monthly learner attendance. |
| Attendance import | `attendance/add_bulk_data.blade.php`; separate download and upload views, unique control IDs, native CSV picker, real date-range export and row validation. Codes 0/1/4/5 are described accurately. The existing old `date` range payload remains accepted. |
| Activity, detention, emergency attendance | Focused pages implemented by the activities/discipline/community owners, linked by Attendance using the original source guards and record handlers. |
| LV001–008, LV015–016, LV301–308, LV321–322, LV325, LV327–335 | `leave/index`, `leave_request`, `student_leave_request`, `staff_leave`, `leave_details`, `leave_setting`; shared request/configuration and native record/decision dialogs. Original reason, files, per-day portions and rejection reason are visible as read-only facts. Personal pending submission differs clearly from office-created approved leave. Staff final decisions are locked, learner Pending is retained, delete describes actual removal rather than rejection or undo. |
| LV005–006, LV305, LV329–331 | Dedicated configuration list/create/edit with nonnegative **monthly** allocation and required non-working weekdays, one-master-per-year source rules, real soft-delete. Configuration-year selection never activates a school year. |
| LV015/LV321 monthly calculation | All twelve source report rows are retained without client pagination; allocated, used CL, LWP, total used, remaining CL and remaining total columns stay distinct. Dashes are not recast as invented available balances. |
| LV009–011, LV309–314, LV312, LV323–324, LV326 | `pastoral/cover.blade.php` now has separate request list, own assigned lessons, create and selected-record views. Existing `CoverQueries`/`ReliefCover` authority, revision checks, refreshed occurrence handling, private reasons, empty candidates and cancelled history are preserved. Teacher cover remains independent from leave approval. |
| LV012–014, LV315–320, LV317/LV319 | Implemented additive facilitator absence/session-cover contract and central authorised leave calendar, described below. These are working persisted flows, not proposed/fake states. |
| Global school-year create/carry/check/preview/activate/result | Existing five-step `academics/new-year` wizard preserves service behavior. Curriculum carry defaults remain unselected and publishing permission is required. New result page reports actual copied/skipped choices and prepared notices from the successful transition receipt. General settings now exposes a readonly current-year value and links to the global workflow; its POST rejects any attempted year change. Promotions remain preparation and do not activate a year. |

## Facilitator contract added for the requested designs

`FacilitatorLeave` uses new `facilitator_absences` and `facilitator_session_covers` school-owned records. Composite foreign keys bind cover rows to their own school's absence, activity session and group; the absence binds to its school's year. Reasons and attachment metadata are encrypted. Files are on a private disk and download only after record authority is checked. Audit entries omit private reason text.

- Owners require a fresh active **Facilitator** membership, `FacilitatorAccess::ready`, an active school and enabled groups. A Teacher record never substitutes for facilitator identity.
- Reviewers require both fresh `activity-groups-manage` and `leave-approve`. Only they see other facilitators' private requests or assign replacements. Impersonation does not grant this private workflow.
- Requests are bound to the current school year, inclusive dates and exactly one full/first-half/second-half portion per requested date. At least one actual dated facilitator appointment/session must be affected. First half is before 12:00 and second half is from 12:00; untimed sessions are conservatively affected by either half. The UI states this boundary explicitly.
- Overlapping pending/approved portions are refused. Submission remains pending. Approval/rejection is final and checks the expected revision; it never assigns cover automatically.
- Cover is one original absence + one activity session. The replacement must have an active Facilitator membership and dated school activity appointment, have no conflicting absence, approved legacy leave, activity session, temporary cover, or teacher timetable/relief reservation. Source session and cover revisions are checked again on save. Permanent group appointments are never changed.
- Request/decision/assignment operations reuse the source activity operation transaction/idempotency machinery. Replays recheck live authority and current-year context.
- A changed source session is labelled for review; original/replacement names and saved reservation remain. The substitute has an owner-only assigned-cover view that excludes the absent person's private reason and files. Activity session/attendance pages display actual cover assignments with manager-only links.
- The calendar combines authorised legacy staff/learner and facilitator requests for a selected week/year. Learner visibility follows the teacher's dated class/term scope. Its cells contain person, dates, portions and decision only; private reasons/files are absent. Opening a request rechecks the same authority.

## Mobile and accessibility

The pages use the shared Material grid, field, form, table, filter, dialog and local navigation patterns. Dense rosters have labelled keyboard-scrollable regions; monthly status does not depend on colour; controls are native and labelled; request details use focus-managed native dialogs; private requests disable browser drafts. The parent task owns shared responsive CSS/drawer/form recovery behavior and browser viewport verification.

## Verification

- Node syntax checks passed for leave and attendance scripts.
- PHP syntax and Pint passed for the new domain/controller/route files.
- Navigation's first completed run: 8 tests passed, 3 stale expected-label/assertion failures after in-flight navigation changes; the assertions have been updated. This is **not** counted as the final pass.
- Final combined feature verification is running in isolated `ui_rework_nav_test`: `FacilitatorLeaveTest|NavigationTest|CoverPanelTest|NewYearTest`. Final results must be recorded after completion; no missing log is counted as a pass.
- New facilitator tests cover persisted submission/approval/cover, repeat-operation idempotency, no permanent group mutation, owner/private-file denial, other-school denial, overlap/day-portion refusal, stale/final decisions, conflicting replacement sessions, historical-year writes and calendar privacy.
- Browser database migration was confirmed applied by the verification agent. Existing module feature/browser suites are owned by their respective agents.

No remaining known PDF workflow is intentionally left as a nonfunctional placeholder in this slice. The test/browser results and any discovered runtime defects remain the final completion gate.

## SE070: module setup readiness

`school-modules.readiness` separates package availability from actual setup. The bounded `ModuleReadiness` service checks only the configured module registry and school-scoped records. Awards checks active categories, per-category active definitions, selected-year nomination windows and actual management/delegated reviewer responsibility. Groups checks current-year groups and dated staffing; leave checks the actual year master and non-working days; discipline checks the retained year framework; fees checks active year plans; foundational setup reuses `SetupChecklist`. Authorised links use the existing domain routes. A module with no implemented check or without current configuration-management authority explicitly displays **Review configuration**, never Ready. Tests cover enabled-without-configuration, foreign-school records not satisfying readiness, and denied/non-manager views.

## Final year/calendar and coverage corrections

The machine-readable `navigation-attendance-leave-trace.json` maps all **142** owned revision3 IDs: 40 Attendance, 53 Leave and 49 global navigation/year/readiness screens and states. Each record names concrete implementation files and a source-backed destination; result, refusal and conflict IDs map to the actual retained form/record/response surfaces.

`session_years/index` now separates the searchable year directory, selected-year calendar/terms, date editing and cleanup review. Preparation and date edits never activate a year. A school with no current year can prepare its first year and open an explicit whole-school activation review; subsequent changes use the global wizard. The wizard can review a specific prepared target. The old settings/date-editor activation flags are rejected. Current-year category clearing is refused server-side. Cleanup lists actual original-relation counts, separates selected-category clearing from whole-year deletion, requires a native final confirmation and displays a result only after the server confirms. Financial retention refusals retain the year visibly.

`term/index` separates calendar list, term editor, add term, class-term configuration and calendar templates. Original `TermCalendar`/`TermTemplates` rules, field-level values, source redirects, created/skipped counts and school/year-bound preview receipts remain authoritative. Removing a term asks for confirmation and preserves source refusal when records depend on it.

The attendance report's CSV export retrieves every authorised row of its selected class/year/term from the existing report endpoint, with a bounded pagination loop and spreadsheet-formula escaping. It does not silently export only the current table page.

The facilitator cover boundary remains deliberately narrow: assignment creates a dated commitment and owner-only session context. It does **not** grant permanent group management or substitute attendance capture. Existing dated group staff retain their source capture authority. Cover display compares the saved group/date/time/cancellation with the live session; attendance-only revision increments do not produce false schedule warnings. Approved legacy first/second-half leave is checked by each actual day portion, so morning leave does not incorrectly block an afternoon session. Active group ancestry is checked before identifying affected sessions or replacement appointments.

Additional verified evidence: all 26 owned Attendance/Leave/year/term Blade templates pass compiled `TOKEN_PARSE`; the cover schedule freshness unit test passes (1 test, 7 assertions); 34 initial desktop/375px route checks reported no document overflow or JavaScript exceptions (permission redirects are not counted as screen coverage). Follow-up year/term and Teacher-profile checks and the latest domain feature batch remain in progress. The interrupted earlier disk-backed run is not counted as passed; the updated complete batch uses the isolated RAM-backed test database and durable `/runtime/nav-final.log`.

## Final integration qualification

The root route includes now inherit the existing authenticated school-context and read-only-year middleware group. The refreshed qualification uses that final wiring. The new global activation forms send the actual source year reviewed; preview rejects a changed current year, and activation checks it again inside a common school/settings transaction lock before any carry-forward write. An initial-year review similarly refuses to replace a year another administrator has activated. Existing callers that omit the additive source field retain their request contract.

The calendar template page preserves the selected template across preview and permits Apply only while that exact preview is displayed. Selecting another template requires a new preview. The existing template service still validates school/year ownership and keeps source created/skipped results.

SE-S02 Modules is explicitly included in the machine-readable142-ID trace, covering availability/package settings, actual readiness and authorised configuration links. Attendance and Leave report chooser links preserve their record tab; crossing between those report tabs refreshes the complete shared navigation context.

The completed old `/runtime/nav-final.log` was **35 passed,3 failed (38 tests,368 assertions)**, not a pass. Its failures were a wrong-school fixture rejected by outer middleware before reaching the intended assertion, duplicate nav history entries, and missing label dictionary entries. The fixture and duplicate entries were corrected; the parent merged the literal labels in every locale. The replacement `/runtime/nav-qualified.log` is the final qualification log and must be reported only after its process completes.

Fresh checks completed:26owned Blade templates compiled with zero syntax errors, nine changed year/leave PHP files pass Pint, the added attendance controller/test files pass Pint, and the new term-preview JavaScript passes Node syntax validation. Browser tests now include actual register loading, preserved day portions, native configuration dialog focus, separate task screens, and375/1280px accessibility/overflow checks; those checks are not counted as passed until run.

## Classroom management integration (AC-A10 and AC052–AC056)

The transferred classroom slice adds six concrete mappings, bringing this file's trace to148IDs. `/foundation-classrooms` now opens the retained lesson authorization history. Separate real GET destinations provide a bounded allocated-group chooser and selected lesson authorization, manager-only eligibility review/editor, tablet inventory/revocation, and tablet registration. Pairing and lesson grants retain their source private result pages, expiry/visibility erasure, account/school body checks and dedicated service authority. API consumers do not receive the new panel-only GET fields. Foreign/unallocated selected groups are refused before a form renders.

Tablet and lesson revocations identify the selected record and explain the effect in a native confirmation. Only those existing revocation forms are year-safe: their source middleware already permits revocation while viewing historical years. Eligibility, registration and new grants remain disabled/refused in that state. Secrets and private fields are not persisted in browser drafts or session flash. Server refusal leaves the canonical eligibility, device or grant unchanged.

The classroom panel test passed against real domain fixtures, covering separate form surfaces, manager/Teacher boundaries, unallocated Teacher, foreign school/group and wrong-role refusals. Existing actual-backend browser registration/delegation/practice tests were updated to the separate destinations; they remain pending until the shared School1 mutation lane is free. Both changed classroom templates compile successfully.

## Qualified core results

`/runtime/nav-final-qualified.log` completed with exit0: **25 tests,1,110 assertions**. This covers AttendanceWorkspaceTest(3), NavigationTest(12), SchoolYearWorkspaceTest(4) and CoverPanelTest(6), including exact dropdowns, every retained legacy destination, every locale key, custom roles, tenant boundaries, separate configuration, native date-range/legacy export compatibility, private cover failures and historical-year protection.

The completed earlier batch `/runtime/nav-qualified.log` passed NewYearTest(13), SchoolTermsTest(18), FacilitatorLeaveTest(8) and FacilitatorSessionCoverTest(1); its only failing Navigation label assertion is now covered by the complete passing NavigationTest rerun. That is **65 distinct relevant core tests passed** across these runs; the incomplete/interrupted batches are not reported as green suites.

The first completed browser batch passed both Teacher Attendance/Leave workflows(375px and1280px) and both School Terms workflows. Terms creates actual source/target years, adds grade calendars, saves and selects a template, previews/applies it, enables a class calendar and checks375/1024/1280px accessibility/overflow. Its two admin sweeps failed on a shared legacy/native-table collision and a missing synthetic staff-history permission; the shared collision and test-only fixture were corrected. The targeted admin375px rerun has passed with exact-destination, native-table, focus-return, axe and overflow assertions; its1280px counterpart remains running. The classroom panel/API contract and actual-backend browser qualification remain separate final gates.
