# People and Academics implementation ledger

Scope: revision-3 school-admin design contracts; Laravel panel implementation on the existing Material 3 library. This is an implementation and verification record, not a phase acceptance or production-release claim. The private Build Bible was not available to this task.

`people-academics-trace.json` accounts for all 211 People and 239 Academics design destinations. Each entry retains the design ID, source, field names and routes, identifies the actual implementation, and distinguishes revised views, inherited source workflows, and work owned by another agent. Existing state variants are not counted as separate new applications. All explicit source files in those 450 contracts exist in this checkout. Four transferred Settings access-review contracts (SE071/359/360/361) are also traced here, making 454 entries.

## New functional panel coverage

- **Facilitators:** school-scoped, searchable/paginated directory of active Facilitator memberships and dated facilitator allocations in the selected year. Add facilitator opens a responsive Material form with the scoped Facilitator role preselected. The source role whitelist now permits this specific system role alongside school-owned custom staff roles; Super Admin, unrelated system roles and foreign-school role IDs remain refused. New and existing accounts follow the original staff/join invitation process, with acceptance by the existing account owner required; activity allocation actions open the existing source workbench. Permissions and the Groups module gate remain enforced server-side.
- **Alumni / volunteer pool:** native directory, adult former-learner search, invitation, member detail, clearance/refusal/revocation, restricted vetting history, booking/history/cancellation. Calls existing PoolMembers / PoolBookings services with the exact operation/revision contracts. Initial passwords are never flashed; business validation retains other draft values. Member consent remains exclusively in the member sign-in flow. Bookings display derived learner counts and supervision for the same dated sessions used by the write service; consent and clearance are rechecked on save. Booking history is paginated in the selected school year. Native validation/conflict recovery uses fixed workspace URLs so background script requests cannot become the return destination. The existing module dependency gate makes the pool unavailable when the pool or its Groups dependency is disabled; no panel exception bypasses it.
- New routes live in `routes/people_workspaces.php`, registered by the root agent. `PeopleWorkspacesTest` covers invitations, validation, business refusal, password non-disclosure, role/tenant boundaries, vetter authority and booking-module availability. Existing pool-domain tests retain the complete consent/vetting/booking lifecycle coverage.

## Revised source-backed layouts

| Area | Actual change / retained behavior |
| --- | --- |
| Learners, parents, teachers, staff | Consistent page headers and responsive shell over existing lists, edit dialogs, source fields and JS. Staff creation is a distinct directory state with full M3 labels, mobile contact/date controls and the existing photo picker. Learner/parent/guardian uploads use the existing M3 file component, with source names, conditional required-photo validation and preview hooks. Explicit select/Select2 accessible labels and preview alt text added. |
| Person profiles | Main profile/file workspace with category anchors and contextual profile/export/invitation/guardian actions. All privacy-filtered categories, partial updates, file upload/removal and guardian authorization remain source behavior. |
| Access review (Settings) | Separate person and role selectors, explicit enabled-state column, levels and sensitive abilities, direct other permissions and menu pages. Actual person previews use live navigation authority; role previews state their limits and mark additional prerequisites. Direct targets match active current-school staff/role choices. |
| Learner report | Material record navigation with real Attendance/Leave deep links, accessible calendar/photo labels, explicit month selection and recoverable AJAX loading. The central chooser preserves its requested section. Direct URLs now enforce the same teacher homeroom/year scope as the chooser; custom staff report authority is preserved. |
| Profile configuration | Field list, add/edit category and add/edit field are separate URL states. Locked field types, role visibility/editability, sensitivity, validation, options and removal rules are retained. |
| Teaching groups | Group operation view separated from class/grade mapping configuration. Allocation records remain dated; prior-year allocation draft is contextual to homeroom/leadership management. |
| Curriculum | Definition, term publication and copy-to-draft are separate authorized record states. Published definitions remain locked; complete source topic/assessment/component grids remain generated by the existing curriculum editor. JS safely handles states without the definition form. |
| Learning content | Upload, conversion status, draft review, replacement/version history, publication preview and withdrawal retain their original flows. Transcription controls are in a separate configuration state, and saves return there. |
| Reports | Reports/release, teacher comments and design/comment-bank configuration are separate URL states. Record-specific batch state takes precedence. Evidence preview, revision conflict review, grade-head/principal sign-off, PDF generation and family release retain their source forms and gates. Teachers default to their comments. |
| Exam periods | Period summaries have record-specific edit actions; creating a period is a separate state. Grade/date/lesson-continuation fields, scope, edits and removals retain source contracts. |
| Reading | Shelf/book visibility remains operational; reading-award rules move to Configuration. Settings saves return to Configuration. |
| Mastery | Learner evidence is separate from topic graph/model/family-sharing configuration. Teachers retain read-only prerequisite viewing. Revision and graph-cycle controls remain source behavior. |
| Shared library (PR490 source imported by root) | Browse, own contributions, school review and settings use distinct authorized states. Full submitted question details precede leader approval/decline. Item preview/rating uses main content and contextual copy-to-school controls; original attribution/licence, rights confirmation and tenant/owner checks remain intact. |
| Question authoring | Option, accepted-answer, worked-step, diagram-label and coordinate rows replace delimiter editing through progressive enhancement. Correct choices use selection menus. An image preview supports pointer placement or a keyboard centre point. Numeric positions remain editable; original limits, private upload and submission payload remain enforced. |
| Structured setup fields | Profile choices, admission document requirements and intervention steps use bounded repeatable rows; original source fields remain as the no-JavaScript fallback. Mastery conflicts show named records instead of raw JSON. |
| Practice reminders | Existing setting names, numeric bounds and revision/operation fields now use standard M3 field components. |
| Promotion | Existing Place/Promote/Repeat/Leave and validation workflow retained; explicit guidance distinguishes learner movement from global school-year activation. |
| Care / wellbeing | Existing clinical/private-versus-assignment views retained; shared workspace navigation applied without broadening access. |
| Early years | Classes, a read-only area reference, manager configuration and one development-area editor now have separate URL states. Only the existing authorized manager can open create/edit states. |
| Mid-year changes | Learner changes and grade/default retention configuration are separate states; the panel defaults to the globally selected school year. Preview/commit and private exit-package actions remain tied to the learner. |
| Academic timetable | Week, lesson placement and import are separate tasks. Configuration has rooms, bells and short-day destinations. Source date bounds, period rows, clashes, import mapping and fingerprint commits remain in place; an import conflict retains all submitted rows for review. |
| Peer tutoring | Scheduling and history are separate states. Invalid schedules return to the selected group/learner with non-sensitive form values. Completion, cancellation, reversal, supervisor/account checks and integer service minutes remain source behavior. |
| Progress / unseen learners | School progress-band settings and principal-only unseen-learner rules are isolated from learner and private note records. Configuration does not load a supplied private note target. |
| Practice tests / intervention library | Directories now link to create/edit states instead of appending every editor. Exact source limits, operation/revision checks, archive/withdraw controls and validation recovery remain. |

`people-academics-template-upgrades.json` enumerates 56 legacy page templates receiving shell/header treatment. It is not a claim that those templates were rebuilt or their complete lifecycle re-tested. The source-backed admission pipelines, transfers, assistance/consents, passports, teaching/marks workbenches, learning questions/tests/memos, timetable, early years and classroom device workflows remain reachable through the coordinated navigation and shared responsive shell; their exact source fields/actions remain in place.

## Ownership and boundaries

- Global layout, responsive CSS, shared components, year switcher, route registration and localization merge: root and navigation agent.
- Attendance and Leave screens: navigation agent.
- Identity card workbench and native re-enrolment: community/settings agent. This agent did not rewrite or duplicate those implementations.
- Final layout-audit follow-up: Assistance office/document publishing (PE-P08, PE013–020) is delegated to community/settings; classroom authorization/eligibility/devices (AC-A10, AC052–056) is delegated to navigation. Their qualification is recorded by those owners.
- Shared-library source implementation came from the dependency import recorded in `dependency-import.json`; this agent changed its UI organization and corresponding UI expectations.
- Existing immutable released reports, original API contracts, guardian approval and scoped clinical records are retained. No production data or external email was changed by the local tests.

## Verification

- Targeted Pint passed for seven new/changed PHP files; Blade view compilation passed.
- Initial People test runs were invalidated by concurrent navigation/import changes (undefined request in Navigation was fixed by its owner; imported provider-link migration arrived after a running process migrated). They are not counted as passing.
- After reconnect, the disk-backed combined PHP attempt was stopped during schema initialization; it is not counted as passing. Current full selected PHP cases run against the disposable RAM-backed test database and write `/workspace/runtime/admin-ui/people-final.log`. Browser runs use the isolated local fixture database only. Final outcomes are recorded below.
- Browser tests were updated to follow the actual configuration/create navigation, and a new People workflow covers mobile invitation, directory layouts and wrong-role/school access. The first content browser flow completed upload, review, publication and withdrawal but hit the 60-second test deadline during axe under concurrent runtime load; the flow timeout was increased for rerun.
- `people-academics-labels.json` contains new/untranslated UI labels for the root's coordinated locale merge. Fluent translation review is separate from label-key presence.

### Runtime findings resolved during verification

- Executable compiled-template checks found and fixed the mastery/curriculum inline-PHP parsing issue and a malformed roll-number header expression. The final all-view `TOKEN_PARSE` sweep reports zero syntax failures.
- A rejected pool invitation exposed the background-script previous-URL issue. The pool now has explicit native recovery routes and preserves non-sensitive draft input. Root also corrected document history globally.
- Correct-option and diagram-label selectors replaced older test locators for raw code fields. Native invitation tests now include an actual business refusal before correcting and saving.
- New facilitator tests cover the scoped system role, duplicate-account invitation acceptance and forbidden role IDs; the browser journey uses the real staff form and file control.

Completed browser evidence so far: learning-content upload/review/publication/withdrawal at 1024 and 1280, another teacher's private-content refusal, report comment conflict recovery, private-profile file upload/save/removal at 1024, guardian page at 1280, mastery graph/evidence workflow at 390, structured question creation at 390, and worked-step/diagram draft serialization. Failures and skipped tests from earlier runs remain visible in their logs and are being rerun after their specific corrections; they are not counted as a complete-suite pass.

### Completed qualification before final access checks

- `/workspace/runtime/admin-ui/people-final.log` completed: 45 selected PHP tests, 638 assertions; 44 passed and one mastery view-selection regression failed. The correction makes explicit learner-group selection take precedence over old configuration input; a targeted rerun is pending.
- `/workspace/artifacts/ui-rework-tests/people-approved.log` confirms complete curriculum draft/publication/copy and Alumni invalid-offering recovery followed by successful invitation. The directory Axe run found neutral-chip contrast, corrected by the shared-component owner. A private-profile browser retry encountered the runtime session-lock failure; neither failed run is called a pass.
- Learner attendance report deep-link/month controls passed at 320 and 1280; teaching-group allocation and teacher roster flows passed in `people-concrete.log`.
- `LearnerReportAccessTest` adds direct and AJAX class restrictions, selected-year placement/appointment checks, foreign learner/year rejection, unchanged custom staff authority and chooser-section validation. These tests are pending final runtime qualification.

### Completed core qualification and final layout audit

- `/workspace/runtime/admin-ui/people-qualified.log`: **55 PHP tests passed, 701 assertions**. This includes the corrected mastery regression, learner-report teacher/year/school restrictions, custom staff access, transferred access review, native pool/facilitator workflows and the selected Academics/Profile/Shared Library tests.
- `/workspace/artifacts/ui-rework-tests/people-qualified-browser.log`: **11 of 12 passed**. Pool validation recovery, directory layout/Axe at 320/390/1024/1280, wrong school/role, private profile upload/removal at 1280, chooser-to-attendance/month-to-Leave navigation and access review at 320/1280 passed. The facilitator creation locator was corrected; a second attempt completed the real save but collided with the source's delayed reload during test navigation. The test now waits for that reload; its final result remains pending.
- The inherited-view audit examined the task/record branches of all 59 distinct source templates. Record-specific guardian, admission, transfer and clinical actions and sequential import/export review remain in their source contexts. The audit found the eight layout gaps documented above and two delegated gaps; source presence is not being used as proof of redesign.
- Executable Blade `TOKEN_PARSE` checks passed the 12 newly revised views/partials. Targeted Pint passed 24 changed PHP files. New focused PHP and 320/1280 browser checks for these final layout changes are running separately; their pending results are not included in the 55-test pass.
