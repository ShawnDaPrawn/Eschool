# School administration revision 3 implementation

The requested implementation uses the existing Laravel panel and its shared Material 3 web components, whose tokens follow the Flutter theme. It implements the school-admin revision-3 PDF hierarchy and retains the source services, authorization and API contracts. It does not replace the backend or turn the PDF examples into seeded production records.

## Product contract

The main navigation is Home, People, Attendance, Leave, Academics, Extracurriculars, Awards, Discipline, Community, Vendors, Finance, Settings. People opens Learners, Parents, Teachers, Facilitators, Admin staff and Alumni. Community opens Gallery, Newsletter, Events and Voting. A permission-filtered workspace provides each module's tasks, records and configuration. The same registry feeds global search and breadcrumbs.

Learner attendance, including activity, detention and emergency attendance, is reached from Attendance. Learner, staff and facilitator absence workflows are reached from Leave. Staff attendance remains in People. Each operational workspace separates configuration through a real URL state or a dedicated page, preserving existing permissions and record revisions.

The header chooses the school year being viewed. Preparing and activating the school's current year are explicit whole-school operations under Settings. Promotion changes learner placement and does not activate a year. Existing historical-year refusal remains enforced on the server; year-independent personal preferences and retained financial workflows have explicit narrow exceptions.

## Shared interface standard

- One page heading, contextual actions, permission-filtered local navigation and a consistent record/context layout.
- Existing Material 3 typography, colour roles and components. New pages do not add Bootstrap. Converted legacy files are removed from the shrinking Bootstrap baseline.
- At narrow widths the main/context columns stack, controls use the available width, tables scroll inside a named keyboard-accessible region, and the navigation becomes a drawer. Essential information is not removed to make a screen fit. Touch controls have at least 44px targets.
- Fields use visible labels, inline validation, error summaries and focus recovery. Native dialogs preserve keyboard navigation. Dynamic repeated rows serialize to the existing backend contract.
- The shared JSON form suppresses simultaneous submissions, includes the clicked button's action, preserves values on refusal and never automatically retries a write whose outcome is unknown.
- Browser drafts require explicit opt-in and are scoped to account, school, year, URL, action and form. Hidden/password/file fields and fields marked `data-no-draft` are excluded. Private workflows disable persisted drafts. A dirty boolean also protects native POST forms when changing school/year or leaving; it does not persist their contents.
- HTTP recovery pages distinguish expired sign-in, missing access, stale data, throttling and an unconfirmed save. An unconfirmed money/approval operation directs the person to inspect the saved record before resubmitting.
- After expired sign-in, enhanced forms can explicitly refresh their token only when the account, school, year, page and form still match. The refresh keeps live entries and attachments, leaves record revisions unchanged and never resubmits automatically. Background reads preserve document recovery destinations and one-time feedback without extending private old-input lifetime.
- Material tables own their loading, selection and refresh state; legacy Bootstrap initialization excludes them. A failed reload clears old rows and selections, and a confirmation cannot act on selections from a replaced result set.
- One GET document reuses its permission-filtered navigation across the sidebar, workspace and breadcrumbs. The result stays on that request and is keyed to the actor, school, year, locale and page. Writes and explicit authority checks remain fresh. Gallery and family-form menu availability stops after proving access instead of building every group's editor configuration.

## New panel adapters and domain additions

Domain-specific ledgers in this directory describe the exact implementation and evidence. Notable source capabilities previously lacking a full school panel now include the facilitator directory, alumni/volunteer invitation and booking, bursary decisions, giving, sponsored places, staff re-enrolment review and personal notification preferences. Facilitator absence and per-session temporary cover are additive school-owned domain records. School subscription invoices use a deliberately separate, issued-only school read contract, not HQ authority.

The personal notification panel at `/my-school` calls the existing NotificationInbox, StaffPrivateNotices, NotificationRules, QuietHours, TextNumbers and UrgentMessaging services. Every write binds the expected account and school; an old form is refused after either changes. There is no user-ID write target. Notice bodies are escaped and recipients/source visibility are checked before loading content. Related-source links use only currently permitted navigation entries, never a stored arbitrary URL. Private staff notice read acknowledgements retain source authority. Own notification preferences are independent of the viewed year; quiet hours explicitly apply across the person's schools. Text verification uses only the existing recorded mobile number, masking it and retaining cooldown/lock behavior.

## Dependency source

The designs explicitly reference PRs 488 (academic competitions), 489 (school provider sign-in) and 490 (shared content library). Their exact reviewed source heads were imported into this branch; `dependency-import.json` records the base, heads and paths. Their school-panel surfaces are adapted to the same hierarchy. Provider/model switches remain off unless already configured by an authorized operator. Importing source is not activating providers or merging those pull requests into main.

The private NavigateEd Build Bible was not accessible in this task. This record claims implementation against the supplied revision-3 designs and repository contracts, not compliance with an unseen private phase gate.

## Coverage and evidence

The design catalogue contains 1,312 destination/state contracts, including creation, editing, results, refusals and shared states. These are not 1,312 independent new features. Domain traces retain the design IDs and distinguish revised UI, inherited functioning source behavior, new panel adapters and shared recovery states. Source presence alone is not runtime verification.

`verification.md` records the isolated runtime and executed checks. Individual domain ledgers record their workflow, permission and cross-school checks. Local source verification, browser verification, CI, staging acceptance and production rollout are separate. The local runtime uses only synthetic repository fixtures. No production deployment or release is implied.

All added UI label keys are present in the four panel language maps. New strings without an existing translation use an English fallback; fluent Afrikaans, isiZulu and Arabic review remains a separate task.
