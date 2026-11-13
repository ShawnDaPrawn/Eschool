# Awards, extracurriculars and discipline UI rework

Scope: the approved revision-3 Awards, Extracurriculars and Discipline PDFs and their `tasks.json` contracts. The explicit owner request authorizes implementing the UI; this does not claim private Build Bible acceptance or a production release. The source baseline is the existing Laravel/Material panel plus the root agent's integration of PR488 academic competitions. No Flutter app or API route has been replaced.

## Design-to-runtime coverage

`awards-activities-discipline-trace.json` maps **181 design IDs** individually to actual runtime destinations and existing/revised Blade views. Result, exception, source-only and imported-PR states are mapped to the screen that renders them, rather than counted as independent static pages. The trace records the inherited backend and the revised UI separately. `awards-activities-discipline-labels.json` supplies new literal gettext labels for the centralized dictionary update; fluent translation review is separate.

A source-control-name audit across the original recognition/activity/discipline templates and the revised templates found **no removed literal form field names**. This is supporting evidence, not a substitute for workflow tests or the design trace.

### Awards and houses

- Award catalogue, module configuration, learner record, nomination queue/detail and assembly are separate destinations. Category, reusable definition, yearly nomination window, dated category-administrator appointment, persistence policy, house and point-category editors each have their own task view.
- Categories expose whole-school, grade/subject and activity-group scopes. Existing scope is shown as immutable; archives retain existing records. Definition cards show criteria, eligibility, frequency and availability. The Golden Boot example follows the real category → definition → window → eligible learner → nomination → review → presentation path, with direct assignment and external achievements also preserved.
- Nomination detail exposes only source-permitted approval/rejection, withdrawal and presentation actions, with encrypted immutable decision versions. Assembly omits private evidence and retained private versions while allowing authorized staff to record an approved presentation.
- Learner award profile, nomination, direct presentation, external award, selected correction and retained presentation/correction history are distinct tasks. Staff history uses exactly the same learner/category scope as the active profile; private correction reasons are not added to family API output.
- House counters retain their scoped polling. Point issue, selected reasoned reversal, history and dated membership change are separate learner tasks. End-old and assign-new membership remain separate writes; old points keep their original house.
- Invalid/stale writes keep escaped text only in the direct private response, restore the intended editor, and retain current saved state for comparison. Global school-year selection controls historical read-only behavior.

### Extracurriculars

- Group overview, learner/staff rosters, allocation editors, sessions/session editor, material list/editor and group configuration are separate destinations. Source dates, hierarchy, gender/grade/age eligibility, captains, staff roles, cascade rules, returning-member suggestions, active/cancelled states and reasons remain available.
- An allocation whose dates exceed the selected learner’s enrolment returns an inline private date error with its draft intact. A corrected resubmission succeeds; foreign learners and other missing records retain their original refusal behavior.
- Attendance entry moves to the central Attendance destination. An empty attendance destination provides a group/session chooser; future/cancelled sessions are read-only. Temporary facilitator cover is shown with a manager-only link to Leave. Review warnings compare the actual group, date, times and cancellation state, so an ordinary attendance capture does not incorrectly invalidate a cover assignment. No permanent group appointment is created by cover.
- Season copying remains within the selected school year; historical years show read-only season history. School-year activation stays in the global year workspace.
- Document list/download/removal and upload are separate tasks. Existing size/type/authorization/revision guards and explicit removal confirmation remain. Group conversation list, creation and selected conversation are separate destinations; reply privacy, moderation, close/reopen, allocated-group authority and lease expiry remain intact.
- Sports fixture list/create, invitation, team sheet, score and schedule/cancellation are distinct tasks. Competition list/create, invitation, participants, invite and standings are distinct tasks. A manager can load a scoped team group before choosing its eligible learners; ordinary assigned staff cannot broaden their group. Invalid team sheets retain labels, positions, selections and private notes only in the direct response.
- Imported academic competitions are functional destinations for debate/quiz/speech creation, overview, details/configuration, own adjudicators, human draft/submitted results, entrant scores/places, agreement/dispute, optional reasoning advice, feedback history/share and entrant awards. AI advice remains optional and does not decide a result. Shared/private source boundaries are retained.

### Discipline

- Record queue, occurrence creation, selected record, module configuration, detention queue/create/detail and central detention attendance are separate destinations.
- Framework configuration is one server-validated form presented as counting rules → sanctions → escalation levels → infractions → review/publish. The review shows the actual entered values. Without JavaScript, the complete form remains usable. Existing occurrences retain their source framework revision.
- Records retain factual description, private follow-up, family-facing reasons, scoped grade review/overturn, principal hearing with sanction/no-action decisions, detention enrolment, immutable versions and family acknowledgement.
- Detention creation retains grade/date supervisor eligibility and capacity. Detail retains roster and cancellation history. Attendance is a separate view, unavailable before the scheduled date or on a cancelled session; source correction and overturn rules still apply.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| Recognition, discipline, activity, fixture, file and conversation panel regression | **36 tests, 479 assertions passed** | `/workspace/runtime/admin-ui/awards-qualified.log` |
| Activity enrolment-date recovery and all affected panel cases (overlaps the domain regression) | **7 tests, 69 assertions passed** | `/workspace/runtime/admin-ui/awards-allocation-recovery.log` |
| Returning-member suggestions, eligibility and explicit allocation | **3 tests, 22 assertions passed** | `/workspace/runtime/admin-ui/activity-suggestions-final.log` |
| Imported academic competition service/API/panel qualification | **13 tests, 268 assertions passed** | `/workspace/runtime/admin-ui/academic-competitions-final.log` and `academic-competitions-fast.log` |
| Golden Boot lifecycle and house-point issue/reversal at 390 and 1280 px | **4 browser tests passed** | `/workspace/runtime/admin-ui/recognition-workflows-final.log`; screenshots in `results-recognition-workflows-final` |
| Activity/register/material, private documents, sports fixtures, academic competitions, discipline and desktop private conversations | **11 browser tests passed at 390/1280 px** | `/workspace/runtime/admin-ui/activities-discipline-final.log`; screenshots in `results-activities-discipline-final` |
| Discipline mobile active-step visibility follow-up | **1 browser test passed at 390 px** | `/workspace/runtime/admin-ui/discipline-step-final.log` |
| Private conversation workflows at 390 and 820 px | **2 browser tests passed** | `/workspace/runtime/admin-ui/awards-browser-qualified.log` |
| View compilation | **46 Blade views compiled and parsed; no leftover control/include directives** | `/workspace/runtime/admin-ui/check-awards-blade.php` |
| Source quality | Scoped PHP formatter, JavaScript syntax and whitespace checks passed | Recognition/activity/discipline changed files |
| Coverage trace | **181 unique design IDs; zero missing mapped views; 265 label keys** | Adjacent trace and labels JSON files |

Returning-member suggestions are verified through overview → Learners roster → Allocate learner, including the actual membership write and disappearance of the suggestion after allocation. Browser workflows exercise actual form submission and source authority, including foreign-school refusal, direct private validation recovery, current source revisions, escaped text, binary download privacy, axe serious/critical rules, document overflow and duplicate IDs. The Golden Boot test creates a group-scoped category, the year nomination window and a reusable definition before nominating, reviewing, presenting in private assembly mode, correcting and reading retained history. The academic competition test includes entrants, adjudicators and draft/submitted human results. Optional AI behavior is qualified with the source fake-provider tests; no paid provider or production setting was activated.

The recognition link cascade passed contrast checks for all variants (active tab 4.54:1; other tab 4.76:1; filled/outlined/text 7.10:1; tonal 4.57:1). Final headings use Material tokens: page heading 24 px, unstyled section heading 22 px, record section 16 px and record title 14 px. Shared card-title typography and assembly projection sizes retain their own component roles. Stylesheet URLs are versioned. Mobile local navigation and the discipline wizard reveal their current item inside the relevant scroll container; both live 390 px bounds assertions passed. Screenshot helpers now scroll to the top before capture to avoid fixed-header stitching artifacts.

Qualification corrected missing Blade-rendered fields, recognition link contrast, historical conversation debug-body exposure and an unhelpful generic error when allocation dates exceeded a learner’s enrolment. The enrolment correction preserves API/domain refusal semantics and foreign-record isolation. Test fixtures now own their consented synthetic learner and dates. Document scanning uses a local double that accepts only exact synthetic PDFs; production scanning remains unchanged and fails closed. Earlier failed or interrupted runs are not counted as passes.

## Qualification limits

Each approved design state maps to its real implementation; the trace does not claim a separate browser rehearsal of every exceptional-state permutation. Browser viewport checks cover mobile and desktop layouts; physical device, native-app and fluent translation review are separate. This ledger does not claim external acceptance or production deployment.
