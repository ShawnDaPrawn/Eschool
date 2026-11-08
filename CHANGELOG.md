# Changelog

## 2026-11-08

- The subject tutor now leads the teach step of a learner's guided learning path again. The default tutor prompt (v2, now 2.1.0) had not been given the learning path instruction that the original prompt has, so it waited for the learner instead of teaching. It now leads in small steps and still never gives the path's practice or check answers. The tutor still needs a real-model evaluation before it is switched on.

## 2026-11-07

- Bind daily family summaries to the exact children whose facts were collected, rechecking those relationships before saving so a revoked relationship cannot be hidden by a remaining sibling.
- Update the five affected Symfony components to the 7.4.23 security release, retaining the existing dependency constraints and security-audit checks.
- Rebuild compiled routes when deploying this dependency update so the routing security fix is applied to cached routes too.

## 2026-11-09

- Daily family summary (Action Plan 5.11): a guardian can opt in to one short summary a day, at a time they choose outside their quiet hours, covering all their children at all their schools: today's and tomorrow's timetable changes, homework due, forms waiting for them, unread school messages and how many private updates are waiting (never what they say). Each school's part is read and written inside that school. The wording comes from the AI gateway when a school has it switched on, and from a plain template otherwise. New API: `GET`/`PUT /api/v2/me/family-summary/preferences`, `GET /api/v2/me/family-summaries/{summary}`. **Deploy:** run migrations; the scheduler runs `family:daily-summary` every 15 minutes and its retention daily.

- Keep starting checks to choice and number questions so extended practice question types cannot leave a diagnostic waiting for an answer it cannot mark.
- Exclude answers awaiting teacher marking from library quality rates and privacy cohort thresholds.
- Refuse the practice question-type rollback while recorded per-part results remain, including automatically marked answers, so rollback cannot erase their marks.
- Update the five affected Symfony components to the 7.4.23 security release, retaining the existing dependency constraints and security-audit checks.
- Rebuild compiled routes when deploying this dependency update so the routing security fix is applied to cached routes too.

## 2026-11-04

- Daily practice has four new question types: worked steps with a mark per step, short written answers, labelling a diagram, and spoken answers. Teachers write them on the new "Write a practice question" page or in the API, and HQ writes them in the content library (Action Plan 3.11).
- Downloaded practice sets no longer carry the answers or worked explanations for apps that ask for the new practice contract (`X-Practice-Contract: 2`): answers are marked on the server when they sync, and the result and explanation appear only after the answer is saved. This applies to school and library questions and to the classroom tablet, which already asks for it. Apps that do not ask yet keep today's sets (choice and number questions, answers included) until `practice.legacy_set_contract` is switched off.
- Short written answers that match no accepted answer, and spoken answers, wait for the learner's own teachers on the new practice marking page; their mark counts towards the learner's practice once. AI help with marking stays switched off until an AI provider is qualified.
- Recorded answers are encrypted in the school's private storage, heard only by the learner's teachers, and deleted after 90 days (30 days after marking). **Deploy:** run migrations; the new daily `learning:prune-practice-audio` job runs at 03:55.
- **Apps:** the new practice contract (`question` instead of `definition`, and a `solution` with each saved answer) is opt-in, so installed apps keep working unchanged. Regenerate the Dart client and update the apps to send `X-Practice-Contract: 2`, then switch off `PRACTICE_LEGACY_SET_CONTRACT` to close the old answer loophole for everyone.

- Prevent SMS or WhatsApp activation during a usage month whose invoice was already issued early; show when it can be enabled and retain the sealed invoice unchanged.

- Run the browser CI server without JIT, matching the application runtime, after a native PHP crash stopped the server during report tests. The browser assertions and failure checks stay in place.
- Refuse OneRoster exports containing formula-like spreadsheet values instead of changing names or identifiers; show the requester how to correct the source data and remove partial temporary archives.
- Update the five affected Symfony components to the 7.4.23 security release, retaining the existing dependency constraints and security-audit checks.
- Rebuild compiled routes when deploying this dependency update so the routing security fix is applied to cached routes too.
- Keep the check-in test answers on their intended school date when CI starts near midnight.
- Return the saved school-business receipt on the first response so exact retries retain the same JSON representation on MySQL.
- Keep invoice installment JSON paths in UTF-8 on MySQL 8, so sealing invoices and applying approved bursaries do not fail on binary JSON conversion. The repair migration retains the existing financial guards.

## 2026-11-06

- Independent schools can publish a versioned re-enrolment contract for next year. Guardians accept the exact wording online; the school sees who has not yet re-enrolled and can send reminders, and the promotion page shows which learners are confirmed (Action Plan 5.10).
- Families can apply for a bursary with household details and supporting documents that only they and the school's bursary committee can see. The committee's award becomes a fee discount that Finance approves; private details are erased on a fixed schedule (Action Plan 5.10).
- Finance can run alumni giving campaigns: pledges and EFT donations are recorded with numbered receipts on the school ledger, donor names stay with Finance, and a school may share campaign totals (Action Plan 5.10).
- **Deploy:** run migrations and the installation seeder (new permissions); the new daily `bursaries:prune` job runs from the scheduler.

## 2026-11-08

- Schools on NavigateEd can now arrange fixtures with each other. An activity manager invites another school (found by name) or types the name of a team that is not on NavigateEd, sets the date, venue and field, and the invited school accepts for one of its own groups or declines. Until a school accepts, the other sees only its name.
- Each school keeps its own team sheet and private notes. The other school sees the team name and, only if the school chooses to share it, each player's label and position, never learners' names or anything else.
- Both schools report the score; the result counts once the two reports agree, and each school sees the other's report while they differ. Against a team not on NavigateEd, the host's report is the result. Guardians of the learners on the team sheet get a private notice that a result is ready and can see their child's team's results.
- Leagues and competitions between schools, with a running table from confirmed results for every school that joined. New panel page: Fixtures between schools, next to Activity groups. New API: /api/v2/activities/fixtures and /competitions.
- Developers: records shared between schools follow a new pattern (engineering/specs/platform/shared-records.md): a shared row that names no school, each school's own participant row, and one service that reads only the columns a school chose to share.

## 2026-11-06

- The homework reuse test now reads the clock once, so it no longer fails when a minute ticks over between the copy request and the due-date check.
- The wellbeing check-in context test now spreads its answers from a fixed time of day, so it no longer fails when run in the last minutes before midnight.
- Schools can export their classes, learners and teachers in the OneRoster 1.2 CSV format from the panel (Open standards, OneRoster export). It needs the new sensitive ability "Export the school roster in OneRoster format" and a reason, is built in the background into private encrypted storage, and is downloaded through a short-lived link that works only for the person who asked. Learners are identified by their LURITS number first. Birth dates and sex are left out unless asked for. Every step is audited.
- Recognition awards can be downloaded as signed Open Badges 3.0 credentials by the learner's guardians (and by high school learners themselves) through API v2. Anyone can check a credential at /open-badges/verify; withdrawing an award revokes its credential. **Deploy:** set OPEN_BADGES_SIGNING_KEY (32 random bytes, base64, from Secret Manager) before credentials can be issued.

## 2026-11-04

- Schools can build an alumni and volunteer pool for career talks and job shadows (API only for now; the module "Alumni and volunteer pool" is off by default). The office invites former learners from its own adult leaver records, and outside volunteers; each person consents when they first sign in, and can withdraw at any time with immediate effect. The school records each person's safety checks, who checked and when the clearance ends, and can book only cleared people, only into staffed group sessions of at least two learners. Pool members get their own sign-in that never reaches learner records, marks or contact details; they see a session's attendance list on the day of the session only.

- Leadership dashboard: a school can choose to take part in anonymous benchmarking and then see its own attendance, results movement (learner-topics that rose a mastery level this term) and guardian reach next to the median and quartiles of comparable schools (same sector, same mix of school stages, similar number of learners). Only schools that take part are compared and only they see the comparison; a group of fewer than five schools shows nothing; no school is ever named. Figures are worked out weekly (Mondays) into a platform snapshot, and a group's figures are kept unchanged until at least three schools have joined or left, so one school's value cannot be worked out from week-to-week changes. HQ sees the same published figures under School health, Benchmarks. **Deploy:** run migrations; the weekly `benchmarking:snapshot` schedule entry is added.

- Practice tests (Action Plan 3.17): teachers set timed cycle tests for a group from their question bank (and the Navigate Ed library where it is on) on the new Practice tests page, sharing the questions between topics evenly or like chosen past papers; learners start quick quizzes and cycle tests through the v2 API. The server holds the deadline and the answers: nothing about the right answers reaches the device before hand-in, late saves are refused and a late hand-in counts only answers saved in time. Results count as practice evidence on the mastery map (labelled practice test), point to the topics to look at next and can hand a review to the tutor. Typed and photographed answers, mock exams and the app screens follow.

- Exam coach memos can now be marked against (Action Plan 3.16). A new marking engine checks a typed answer, step by step, against a memo's mark points: values with their tolerance, units (converting between SI units such as km/h and m/s), equivalent algebra, formulas, key terms, significant figures and consistent-accuracy follow-through. Mathematics and Physical Sciences have their own conventions (for example, Physical Sciences needs the unit and the direction); other subjects use only the memo's rules. Every mark gets a reason and a confidence; method and explanation marks that no rule decides always go to a teacher, as automatic judgement stays off.
- Teachers can try it from a memo with "Try marking an answer against this memo". Nothing is saved and learners do not see it.
- Memo mark points gain three rules: an expression or formula, terms, and significant figures.
- Learner marking stays off for every subject until it passes the accuracy check against teacher marking (`config/marking.php`).

- Practice runs are counted kindly: weekends, holidays, school closures and one missed school day a week no longer break a learner's run, and practising on a weekend still counts. The practice screen's day count follows the new rule.
- Learners can set their own weekly practice goal (1 to 7 days) and get one short "well done" note the week they reach it. Guardians can see the goal; only the learner sets it.
- When a topic that needed more practice becomes secure on the family-visible mastery map, the learner and their guardians get one short "something to celebrate" note, at most once per topic each term. It never shows a score, and staff-only marks are never used.
- Schools can switch on gentle practice reminders for guardians and learners (both off by default) under Academics, Practice reminders: at most one a week, school-day afternoons only, never during quiet hours, and never naming a child or a count.
- Awards managers can switch on practice run badges (off by default), presented with the other persistence badges once a school year for runs of 5, 10, 20 and 40 days.
- The weekly parent update can now say when a child reached their weekly practice goal and how long their practice run is; it never says a goal was missed.
- API: `GET /api/v2/learning/practice/learners/{learner}/motivation` and `POST /api/v2/learning/practice/learners/{learner}/practice-goal` (the Dart client needs regenerating).

- Tutor teaching quality (Action Plan 3.10): a second tutor prompt (v2) that explains at the learner's stage and grade, shows worked examples of similar problems and common mistakes, lets a learner "explain it back" in their own words, and answers more warmly in the same structured form. The first prompt stays selectable.
- The tutor now remembers which topics a learner practised in earlier sessions (topics and counts only, never the chat, and never anything flagged for safeguarding). The learner and their guardians can see and clear it; it only uses the last 90 days of the current school year.
- The tutor can read the section of a document that answers the question, chosen with embeddings through the AI gateway; when that is off or unavailable it reads the opening, as before. Explanations in Afrikaans or isiZulu are built but stay off until each language's safeguarding phrases are reviewed. **Deploy:** `AI_TUTOR_PROMPT` (default `tutor-v2`), `AI_TUTOR_EMBEDDING_MODEL`, `AI_TUTOR_HOME_LANGUAGES`; new migration.

- Learners get a guided learning path for each subject (Action Plan 3.9): the server finds the topic to work on next, shows the school's lesson (or a library lesson), gives a few practice questions, then a short check it marks itself, and plans a review for a later day. A check that does not go well yet leads back to the lesson with a kind message; after three tries the topic rests until tomorrow. Answers are never sent to the device before the learner answers. Teachers see which step each learner in their group is on (no scores or rankings), and guardians see a plain summary for their own child. When the tutor and the AI are switched on, the tutor opens on the topic and leads the lesson. API: new `/api/v2/learning/paths` endpoints; the Dart client needs regenerating.

- Reading library (Action Plan 4.4), server, HQ and admin panel. Navigate Ed's
  content staff build a catalogue of classic public-domain books in HQ
  (Reading library), record a copyright decision for each one (who, when and
  why; only books cleared as public domain in South Africa ever reach
  learners), put them on shelves by grade and write comprehension questions.
  The server fetches each book once from a Project Gutenberg mirror, removes
  the Project Gutenberg licence and name, keeps a private copy and serves it a
  page at a time. With the new Reading library module switched on, school
  leaders can hide shelves or books from their learners and switch on two
  reading badges (finishing a book, reading on several days in a week), given
  through Awards. Learners' places, reading time and answers are their own and
  never compared. The learner API is ready; the app screens come later.
  **Deploy:** run the migrations and set `READING_MIRROR_URL` (nothing is
  fetched until it is set). The South African copyright rule and the use of the
  Project Gutenberg name still need legal review before launch.

- Learner passport (Action Plan 4.10). With the new Learner passport module on, a learner, and a guardian who approves for them, can see one read-only record across the NavigateEd schools the learner attended, headed by their LURITS number: awards and badges, activity groups, supervised peer tutoring minutes, and the results that came with a signed school transfer. A reading section waits for the school library. School staff with profile permission see the same page for their own learners in the panel (People, Learner passports), limited to what their school holds and what arrived in a signed transfer to it; they never see another school's records, and learners are found by name, never by LURITS number. Health, counselling, wellbeing, discipline, support plans, safeguarding, house points and private notes are never part of a passport. From their 18th birthday (by the school's calendar) a learner can download their passport as JSON or PDF; each download is audited. API: `GET /api/v2/passport/me`, `/me/export` and `/learners/{learner}`; regenerate the Dart client. The app screens come later.

- School groups: one owner, many schools (Action Plan 5.7, SA.18).
  - HQ creates a group, invites schools and names the group's administrators.
  - A school joins only when its own Super Admin agrees on the new School groups page. The page shows exactly what the group will see. The school can leave at any time, and the group stops seeing it at once.
  - Group administrators sign in to a group page with each school's learners, weekly active families, guardian reach, learners active in the app and recorded attendance, plus the group's totals. Numbers under 10 are hidden, as is any total that would reveal one. The role gives no access inside any school, and no learner record is ever shown.
  - HQ Finance can bill a school through its group. One invoice a month then rolls up the schools' own monthly lines, and a school's month is never billed both on its own and through the group.
  - **Deploy:** run the migrations.

- SMS and WhatsApp (Action Plan 2.7 follow-ups): a mobile number is now texted only after its owner verifies it in the app with a six-digit code sent by SMS (codes expire after ten minutes, five wrong codes lock it for an hour, one code a minute and three an hour). Numbers recorded before this change are not texted until verified, and a changed number must be verified again; a skipped text is recorded with the reason. Guardians and staff choose SMS, WhatsApp, both or neither for each notice in the app (`/api/v2/me/text-messaging`); the school sees only how many people chose. Schools can now also switch SMS and WhatsApp on for staff (urgent notices and safeguarding alerts), on the number on the staff member's own account. Navigate Ed's monthly invoice to a school now has a line per channel passing on what Twilio charged for the previous month's texts, in rand at a configured exchange rate. **Deploy:** run the migration `2026_11_07_100000`; set `PLATFORM_BILLING_USD_ZAR_RATE` (rands per US dollar, for example `18.50`) before creating invoices for months with texts, or the drafts stay blocked.

- New "My strengths" mirror for learners (Action Plan 4.8, server and API; the app screens follow): the topics a learner is growing in fastest, what they remember well from daily practice, the topics they chose to explore with the tutor (never anything said in a conversation), their activities and the skills from them, and a few career ideas with their sources. No scores, ranks or comparisons. Only the learner sees it. They can share a read-only copy with an approving guardian or a teacher who teaches them, for up to 30 days, end it at any time, and see when it was last viewed; every share and view is audited. Nothing else in NavigateEd can read it, so it is never used for streaming or placement. Schools switch it on with the new "Interests and strengths mirror" module (off by default, not for early years). Reading will appear once the reading library is built.

- Learners can take a short starting check for a subject when their school has the Navigate Ed library switched on (Action Plan 3.7). It asks a few questions per topic, one at a time, marked on the server: the app never receives the answers or explanations, and the learner is not told right or wrong. Each answer fills the mastery map as its own kind of evidence ("diagnostic"), never as a mark. Learners and guardians see a plain starting point; teachers see who has finished and the starting map per topic on the new Starting checks page. API: new `/api/v2/learning/diagnostics` endpoints in `openapi.yaml` (the Dart client needs regenerating). **Deploy:** run migrations (two new school tables).

- HQ content library (Action Plan 3.4): Content staff can ask AI for practice questions or a lesson for a library topic, from a chosen source. The drafts arrive as drafts marked "AI draft" and are published only after a reviewer for the subject, who did not ask for them, approves. While AI is switched off across the platform (as now, with no provider qualified), HQ is told so and nothing is written.
- HQ AI calls go through the AI gateway's own HQ path, with moderation and HQ's own daily spending caps, and are metered apart from every school's AI spend.
- Each library version now says where it came from (written by staff from a source, or an AI draft with who asked and who reviewed it), on the item page and in the audit log.
- A new "Needs attention" page lists published library questions that learners find far harder or far easier than expected, from answers across schools, shown only as a percentage and a learner-count band, never a school or a learner, and only for questions answered by at least 20 learners in at least 3 schools. HQ can open a new draft from it.
- The library review queue can show only AI drafts or only flagged items.

## 2026-11-03

- Admin panel and API: Emergency management (Action Plan 5.8), a new module,
  off until the school switches it on. Roles the school names (the new "Send
  emergency alerts and lockdowns" ability; principal and deputy principal
  templates have it) send a lockdown, evacuation, shelter-in-place or general
  emergency alert to all staff and every family, or to staff only, in two
  steps: prepare it, then type its confirmation word to send it. Learners are
  never sent alerts. Alerts go by push, email and in-app, and by SMS and
  WhatsApp wherever the school switched them on, and are never held for quiet
  hours. The all-clear goes to the same people, and who prepared, sent and
  cleared each alert is recorded. A drill says "DRILL" and "This is a drill.
  There is no emergency." in every message, and fills in the drill log
  (planned and actual times, type and notes). Gate staff sign visitors in with
  a guardian card at the gate reader or by hand; the visitors on site show in
  the roll call, and their details are erased after the school's retention
  period (30 days unless the school changes it). During an alert, class
  teachers take their class's roll call, and a child is released only to one
  of their guardians (checked by guardian card or identity document) or to the
  named collector of an early collection request approved for today; anyone
  else is refused and every release is logged. Guardians see only their own
  children's status. App screens come later.
  engineering/specs/community/emergency-management.md. **Deploy:** run the
  migrations; the daily `emergency:purge-visitors` task is scheduled.
- When an admin panel browser CI part fails, CI now prints the PHP OPcache/JIT settings and the kernel's note on any PHP process it killed, so a panel server that stops without a PHP error can be diagnosed.
- Keep diary test fixtures and requests on the same clock so the Monday overdue-homework check is independent of the run date.
- Refresh the strict HQ route inventory while retaining every route authorization assertion.
- Apply question-bank scope foreign keys after backfill and required-column conversion, preserving the final constraints on MySQL 8 and MariaDB.
- Restore the "No child unseen" page at /risk-interventions/unseen; a merge had moved its routes, so the early warning panel returned an error.
- Daily practice answers on platform library questions no longer fail when recording mastery evidence.
- New pushes now cancel a branch's earlier CI run in full, so stale runs no longer hold up the queue.
- Message workbench links are underlined so they read as links; the content library link migration rolls back on MySQL; more tests guard the school calendar in /me/context, check-in counts beyond 500 answers, and topics without evidence in the mastery impact report (from #394).

- Phase5: actual daily cash custody reports, retained physical count revisions,
  explained differences and private exports without automatic ledger adjustments.


- Phase5: original vendor settlement batches, separate Finance approval, exact
  school sales/VAT recognition, verified manual payouts and private vendor reports.


- Phase5: dedicated vendor operators, explicit counter assignments, private panel
  and native scanner access, single-use collection and interrupted handover recovery.


What changed in NavigateEd, newest first. Each phase release gets a section when it reaches production; work merged since the last release sits under **Unreleased**. Entries are grouped by who notices them (Admin panel, Apps, API, Platform, Security, Operations, Removed, Developers), and say in plain words what changed for the people who use or run the platform. Deployers' actions are marked **Deploy:**.

Earlier history is kept where it was written and is not repeated here:

- Upstream eSchool versions up to 3.5.0 (the codebase we bought): [`documentation/changelog/index.md`](documentation/changelog/index.md), published at https://shawndaprawn.github.io/Eschool/changelog/.
- This fork's work before NavigateEd Phase 0 (security hardening, tests and CI, dependency updates, translations, branding), with links to every commit and pull request: [`wiki/Release-History.md`](wiki/Release-History.md).

## Unreleased

### Fixed

- API: guardians with leaver-only access can remove a child's photo tag and
  withdraw existing consent without regaining any other write access. Revoked
  relationships and expired access still stop changes and receipt replay.

### Added

- Admin panel: Early years tools for young classes (first part). With the new
  Early years module switched on, a class teacher fills in the daily diary for
  the whole class in one go (meals, rest, mood, toileting and a short note per
  child), and records observations, a note or a photograph tagged to a
  development area, which build each child's portfolio. Schools start with
  South Africa's six early learning and development areas and can change them.
  A photograph is kept only if someone can give consent for the child, and
  staff see it only after the family approves it in the private photo gallery.
  From an observation, the teacher can refer an early concern to the grade
  head as an intervention, in plain words and never as a diagnosis. Grade heads
  and the principal can read the diary and portfolios. Family views and the
  developmental report come later. **Deploy:** run the migrations.
- Admin panel: Wellbeing check-ins and an intervention library (Action Plan
  4.11). Principals keep a library of everyday school actions in early warning
  (the steps, a suggested length and how to measure progress) and can add
  NavigateEd's six suggested actions in one step. Staff who plan an
  intervention can start it from a library action and record dated progress
  against its steps: on track, needs review or completed. A learner's
  counsellor or safeguarding professional can schedule a short private
  check-in for one learner or a group on their caseload and record the
  learner's answer, from Not good to Great with an optional note. Only that learner's
  authorised pastoral staff see the answers; their early warning list shows
  recent check-ins as context, never as a risk. The learner app answer comes
  later. **Deploy:** run the migrations.
- Platform: Navigate Ed Operations can now run a school's onboarding imports
  (homerooms, learners with guardians, or staff) on its behalf. From the
  school's HQ page, "Imports on the school's behalf", Operations check the file
  first: every row's result is shown and nothing is written. They then send it
  to a leader at the school, who sees the rows under People, "Imports by
  Navigate Ed", and approves or declines it with a reason. Only after approval
  can Operations apply it, through the school's own import, and only if the file
  and the school's records are unchanged since the check. The file is stored
  encrypted and deleted once the import is applied, declined, cancelled or
  stopped, or after 7 days. Every step is in the HQ audit log and on the
  school's page. New HQ ability `imports.run` (Owner and Operations).
  **Deploy:** run the migrations; the scheduler runs
  `platform:expire-school-imports` hourly.

- Admin panel: a **Mastery impact** report for each term. Principals and
  academic managers see the whole school; grade heads see their own grades. For
  each topic it shows how many learners rose a level since the start of the
  term, held their level, went lower, were measured for the first time, or have
  no evidence yet, with a total per grade. It shows counts only, never learner
  names or rankings. Learners with no evidence count as "no evidence yet", never
  as zero. It is in the menu under Grades and subjects, next to the leadership
  dashboard (which links to it), and needs the mastery and leadership dashboard
  modules. The start and latest mastery are saved every night
  (`mastery:growth-snapshots`), and start and end are always compared within
  one mastery model version.
  **Deploy:** run the migrations, then run
  `php artisan mastery:growth-snapshots <school> --backfill` once for each
  school using mastery, to fill in the current term.
- Admin panel: Pathways now has career profiles and a "What would it take"
  view (Action Plan 4.9). Pathway managers publish a career profile from a dated
  public source: the school subjects it needs (with any sourced minimum), the
  curriculum topics that matter, and university, TVET college and learnership
  routes, each with its own source. Profiles past their review date are flagged.
  Counsellors with pathway management, and teachers for their own learners,
  choose a learner and a career to see the subjects it asks for next to the
  learner's current subjects, and the learner's mastery on the linked topics
  with good places to start. Evidence the reader may not see, or that does not
  exist yet, shows as unknown, never as zero. The view never rates a learner.
  **Deploy:** run the migrations.

- Admin panel: teachers share their own practice questions. A practice
  question now belongs to its subject and grade in the school instead of one
  class, so it stays when the year changes. On the new **Question banks** page
  (Academics, with daily practice on) teachers open the bank for a subject and
  grade, use a question in any of their groups or stop using it, and choose
  who else may find and reuse the questions they wrote: only them, the teachers
  of the groups using it, or every teacher of that subject and grade. Only the
  author or a subject lead (academic managers, the grade head, the principal)
  may change or archive a question; colleagues may only use it. The page shows
  which classes really get each question and which wait for this year's
  material or curriculum plan. The new-year wizard has a new choice that keeps
  questions in use in each teaching group's copy. Existing questions keep
  working in their class and are shared with that class's teachers.
  `engineering/specs/learning/question-banks.md`. **Deploy:** run the migrations.
- Admin panel: Each grade now belongs to an age stage: early years (Grade R to
  2), junior (Grade 3 to 7) or high school (Grade 8 to 12). School leaders see
  and change the stage of each grade under Settings, School stages, and every
  change is kept in the audit log. Early years learners can no longer sign in
  to the learner app or portal themselves; their parents and guardians keep
  their own sign-in. The tutor and daily practice are offered to junior and
  high school learners only. **Deploy:** run the migrations.
- Learning: The Monday learning update now starts with growth (Action Plan
  4.6). Parents see "This week [child] grew in…", listing only topics where the
  child's mastery rose last week (never falls, rankings or comparisons with
  other children), and a help-at-home line naming the topics the school's
  published curriculum plans start next week. Learners get their own short
  in-app note, "This week you grew in…", in their inbox only (no push or
  email). Growth uses only what the family may already see on the mastery map,
  compares like with like within one mastery model version, and follows the
  existing guardian approval rules. The new lines appear once a school approves
  a weekly update template that uses `{grew_in}` and `{help_at_home}`; the
  English starting text has them. Schools can also switch on two persistence
  badges in the awards settings: one for practising on several days in a week,
  and one for coming back to a hard topic and improving. Both start off. Badges
  are presented as permanent awards each Monday, with the usual family award
  notice, and are never awarded twice for the same week.
  **Deploy:** run the migrations.
- Admin panel: Teachers can now break an exam coach memo into mark points. On
  the new Exam coach memos page, a teacher picks a teaching group and a past
  paper, and for each question records what earns each mark, the kind of mark
  (method, answer, accuracy, consistent accuracy or explanation), an optional
  rule (an exact value with a tolerance, or units required) and how many marks
  it is worth. The points of each question must add up to the question's
  total. The paper's author or a grade lead then marks the memo as reviewed;
  changing it afterwards needs a fresh review, and only a reviewed memo will
  be used for marking. Other teachers of the group can read the mark points.
  **Deploy:** run the migrations.
- Admin panel: Every child teaches something (Action Plan 4.7). When a teacher
  looks for peer tutors, learners who have not tutored yet this term (or have
  tutored least) are offered first, and each match says why, for example "Has
  not tutored yet this term". The existing rules still apply: proven topic
  mastery, guardian consent, a current place in the teaching group and a
  supervising teacher. A learner who captains an activity group this school
  year can now also be matched to teach that activity as a non-academic skill,
  under the same supervision; these sessions count as service like any other
  but are not shown in the apps yet. Staff who manage the academic structure,
  the principal and grade heads get a new page, "Who has tutored this term, per grade", showing how
  many learners in each grade have tutored at least once this term: counts
  only, never names or a ranking. **Deploy:** run the migrations.
- Admin panel: schools can choose a second mastery model. On the Mastery map, a
  curriculum manager can switch from model v1 (the last 20 answers, marks added
  up) to model v2. Model v2 weighs recent and harder questions more, lets old
  evidence fade at the pace of the learner's practice memory, counts practice at
  half the weight of marks, and grows evidence coverage slowly, so one right
  answer is no longer top level. Every score records the model that produced
  it, and the panel shows which model the levels come from. Model v1 is the
  default and keeps working as before, so nothing changes until a school opts
  in, and a school can switch back at any time. The change is in the audit log.
  The apps receive the model's name but don't show it yet. **Deploy:** run the
  migrations.
- Admin panel: "No child unseen" (Action Plan 4.5). Early warning now finds
  the quiet learners: anyone with no recorded positive contact from an adult in
  the last 15 school days (the principal can change the number). Progress
  notes, award nominations and presentations, awarded house points and
  completed peer tutoring count; counselling notes, wellbeing, health,
  safeguarding and discipline records never do. If a source is switched off or
  not used, it is not taken as "no contact": with nothing to go on the answer is
  "unknown" and nobody is flagged. Grade heads see the list for their grades
  and the principal for the whole school, under Early warning → No child unseen,
  with the days since the last contact and the kinds of contact this year,
  never what a note says. They can name a staff member to reach out; that
  person gets a notification, the choice is audited, and the request closes by
  itself once a positive contact is recorded. Families never see it.
  **Deploy:** run the migrations.

- Platform: school offboarding (Action Plan SA.11). For a school that is
  leaving, Operations or an Owner starts an offboarding at
  `/saas/schools/{school}/offboarding` with a reason, a deadline and the
  contract's retention terms (a contract can now record how many days after it
  ends the school's data is deleted; otherwise the date and terms are
  entered). Navigate Ed prepares a full, signed and encrypted export of the
  school's own data, and only the school's Super Admin can download it, from
  "Data export (leaving)" in their own panel, through a link that lasts 15
  minutes. They confirm receipt there, or the deadline counts as receipt. HQ
  then archives the school and deletion is scheduled for the contract's date.
  Deletion needs two approvals by different people, the second an Owner, each
  re-checking that the export was delivered and no legal hold is on; in this
  release it runs a dry run that counts what would be deleted and deletes
  nothing. HQ sees states and counts only, and every step is in the audit log.
  **Deploy:** run the migrations.
- Platform: a Navigate Ed content library in HQ at `/saas/library`. Content
  staff add curriculum frameworks (such as CAPS), topics per subject and grade
  with their prerequisites (a loop of prerequisites is refused), the source and
  licence of the content, and practice questions, past-paper questions, lessons
  and diagnostics as drafts. A new Content reviewer role approves or sends back
  items, with a reason, but only for the subjects they are assigned to and
  never their own work; only an approved version can be published, and a
  published version never changes (editing starts a new draft). Every step is
  in the HQ audit log. Nothing a school writes ever goes into the library.
- Learning: HQ can switch the library on for a school (off by default). When it
  is on, a learning group with fewer than ten approved practice questions of
  its own gets published library questions for its curriculum framework,
  subject and grade after its own, in daily practice. Mastery evidence now says
  whether each answer came from the school's own questions or the library, and
  which library version. **Deploy:** run the migrations.
- Platform: an HQ home page at `/saas/home`, where signing in to HQ now lands.
  It shows, across every school, the schools by stage, the schools marked at
  risk, what blocks each school still being set up, and, for the roles that
  handle them, open incidents, open LURITS claims, AI safeguarding flags and
  caps, and platform health (jobs waiting, failed jobs and failed email and
  push messages in the last day). Each part appears only to the roles that can
  open its area, and links there. Only counts and school names are shown,
  never a learner, parent, staff member or contact. The school list stays at
  `/saas`, now also the "Schools" link in the HQ bar. Platform health is a new
  ability for Owner, Operations and Support.
- Platform: packages and prices are edited at `/saas/packages` instead of being
  fixed in the code. Owner and Finance create draft packages from the product
  modules, set trial days, activate and retire them, and add prices per learner
  per month (excluding VAT) from a date: a base price, add-on prices for product
  modules and discounts. Past prices are kept and never changed, overlapping
  dates are refused, and the page shows the price on any date. Changing an
  active package's modules does not change what schools already on it can use
  until HQ re-applies the package to them, with a reason. Other HQ roles can
  only look. The four existing packages are copied over unchanged, so no school
  notices anything. **Deploy:** run the migrations.
- Admin panel: a school can add a teacher or staff member who already uses
  NavigateEd at another school (or a principal moving schools) without a second
  login. They are emailed an invitation, sign in with their usual password and
  accept or decline. Accepting never changes their password, and no school can
  change the name, email address or other details of an account it shares with
  another school, or deactivate or delete it: editing, deactivating or deleting
  such a person only changes that school's own records and roles.
  Adding them reads the same as adding a new person, so the school never learns
  that the address was in use or where. Waiting invitations are under Staff,
  Invitations to join, where they can be withdrawn. The staff import now invites
  such people (and parents or former staff of the school) instead of giving
  them the role straight away, and refuses learners. Platform: HQ can
  set up a school whose first administrator already has an account the same way.
  Learner and platform staff accounts are never invited.
  **Deploy:** run the migrations (`school_join_invitations`).
- Platform: Owners now set the AI spending caps in HQ. In the AI control
  centre at `/saas/ai`, each AI feature's daily caps per school, per person and
  per learner can be changed with a reason, and apply from the next request.
  The values in `config/ai.php` are the defaults until HQ saves one. Every
  change is kept with who made it, when, the old and new amounts and why, and
  is in the HQ audit log. A school's own lower limits in its AI settings still
  apply on top (the lower of the two is used), and HQ never changes them.
  Amounts are in the gateway's metering currency (US dollars today), with no
  exchange rate applied. **Deploy:** run the migrations.
- AI: the Subject tutor is a personal service to the learner, not a school
  service. Tutor use no longer counts against a school's daily AI cap, and a
  school at its cap can still use the tutor. The tutor keeps its own daily
  caps per person and per learner, set in HQ. HQ shows tutor spend apart,
  labelled as personal.

- Platform: an AI control centre at `/saas/ai` for Owner, Operations and
  Support. It shows whether each AI feature and provider is qualified, its
  model, price and daily caps per school, person and learner; each school's AI
  spend this month and today against its daily cap; and how many safeguarding
  flags wait for each school's reviewers. Only settings, totals and counts are
  shown, never a prompt, an answer or a learner. Owners can switch an AI
  feature on or off for a school with a reason (kept in the audit log); a
  feature that is not qualified cannot be switched on. Amounts are in the
  gateway's metering currency (US dollars today), with no exchange rate
  applied. The dated cost report at `/platform/ai-costs` is unchanged and
  linked for staff who hold its grant.
  `engineering/specs/platform/hq-ai.md`.
- SMS and WhatsApp for urgent notices: in **Settings > SMS and WhatsApp** a
  school can send urgent school notices and absence alerts to guardians by SMS,
  WhatsApp or both, choosing which notices use each. Both are off until the
  school switches them on, and only office staff who can change settings can do
  it. Messages go to the mobile number on the guardian's parent record at that
  school. An SMS or WhatsApp message never contains a child's name or details:
  it says there is an urgent message and asks the guardian to open the
  NavigateEd app; only a notice sent to the whole school includes its title and
  message. Each message is charged to the school at the price NavigateEd pays.
  In HQ, **SMS and WhatsApp** shows each school's messages and cost for the
  month (Owner, Operations, Finance). Nothing is sent until NavigateEd's Twilio
  account, senders and WhatsApp templates are set up.
  **Deploy:** run the migrations; add `navigateed-twilio-account-sid` and
  `navigateed-twilio-auth-token` in Secret Manager, re-run
  `infra/gcp/provision.sh`, and set the Twilio senders and templates in the env
  file ([SMS and WhatsApp](engineering/specs/platform/sms-whatsapp.md)).

- Platform: Navigate Ed bills its schools from `/saas/billing`. Each month
  Finance (or an Owner) creates draft invoices for every active school in one
  run: learners are counted that day (leavers and other schools' learners are
  not), and the price per learner is the school's contract price, or else its
  package's list price with any discount and the add-on modules it has switched
  on. VAT (15 %, set in config) is worked out on each line and the invoice adds
  the lines. Drafts are reviewed and then issued with a reference such as
  NE-2026-0001 and a due date 30 days later; an issued invoice never changes,
  and a mistake is corrected with a credit note. EFT payments are recorded with
  the bank reference and the invoices they settle; part-payments are fine, an
  over-payment becomes credit on the school's account, and a mistaken payment is
  reversed with a reason rather than deleted. Each school has a printable
  statement and each invoice a printable page. Overdue invoices are listed, a
  reminder goes to the school's Super Admins and its bursar contact, and after a
  14-day grace period the school is listed as due for suspension, which an Owner
  or Operations still decides with the existing suspend form. The page also
  shows the month's invoiced, collected and overdue totals and monthly recurring
  revenue. Operations can look; Sales, Content and Support cannot. Issued
  invoices, their lines and payments are also protected in the database itself,
  and pressing "Send a reminder" twice sends one reminder.
  **Deploy:** run the migrations.

- Caseload renewals for a new school year: in Early warning and interventions,
  **Renew caseloads for a new year** lists last year's open risk caseload grants
  for learners placed in the new year. A principal (or the learner's grade head)
  keeps the same counsellor, chooses another one, or lets the grant end. Ended
  grants are never carried, and only caseload administrators see the page.
- Staff drafts for a new school year: on Academic allocations, **Draft from last
  year** fills in each teaching group's teachers and each homeroom's teacher from
  last year wherever the group or homeroom carried over. Change what is different
  and apply it in one step; teachers see nothing until then, and a draft is refused
  if allocations changed after it was built.
- HQ shows each school's health at `/saas/schools/{id}/health`: weekly active
  families, how many learners have a guardian the school can reach, sign-ins by
  role, module use, email bounces and complaints, push failures, AI spend against
  the month's cap and failed jobs, counts only and never a person. `/saas/health`
  compares every school from nightly figures, schools at risk first; the at-risk
  rules (activity down 30%, reach under 60%, bounces at 5% and others) are in
  `config/school_health.php`. Owner, Operations, Support and Finance can see it.
  **Deploy:** run the `school_health_snapshots` migration; the scheduler runs
  `platform:school-health` nightly (run it once by hand to fill the comparison).
- Platform: feature flags at `/saas/flags` for Owner and Operations. A new
  feature can be switched on for chosen pilot schools first (beta), then for
  everyone, with single schools switched on or off by name, and a kill switch
  that turns it off everywhere at once. The apps receive the result in their
  existing feature switches. A flag can switch a feature that is already in the
  apps off for a school, but never on where the app does not offer it.
  **Deploy:** run the migrations.

- Communication: the sender of a school message, and its reviewers, see a
  delivery report: how many people it reached and read it, who had no
  notification channel, and for email and push how many were delivered, sent,
  bounced or failed, with each recipient by name. Addresses are never shown.
  **Deploy:** run the migrations.
- Notifications: school notice emails carry the school's crest, name and
  colour and a plain-text version, and school messages arrive in full in the
  email. Private notices (safeguarding, wellbeing, discipline, progress and
  similar) still say only that there is something to see in the app.
- Notifications: optional school notice emails have a one-click unsubscribe
  (in the email client and as a footer link) that switches that notice's email
  off. Required notices, such as absence alerts and urgent notices, never
  carry it.
- Communication: smart groups. Staff can save a group by rules (grades,
  homerooms, teaching and activity groups, any or all of them, learners or
  their guardians) that is worked out again whenever a message is previewed
  and sent. Saved audiences can now be private or shared. **Deploy:** run the
  migrations.

- Academics: a learner or guardian can subscribe their calendar app to the
  learner's exams, and optionally the next four weeks of lessons, through a
  secret link they can revoke at any time. **Deploy:** run the migrations.

- Admin panel: SA-SAMS learner, attendance and marks exports now identify each
  learner by their LURITS number. Only learners who have no LURITS number yet
  still need a learner reference in the layout's map. The export page and each
  download's manifest say how many learners were exported without one, with a
  link to the LURITS numbers page. Importing the SA-SAMS learner list again
  records the LURITS numbers of learners already at the school. A number that
  another learner already holds is not recorded: it waits for Navigate Ed
  support, and the school is not told who holds it.
- Notifications: richer pushes. Consent requests show Yes and No, messages
  show Reply and their sender, urgent notices and safeguarding alerts show
  Acknowledge; every button only opens the app to confirm. Private notices
  (wellbeing, discipline, consent, progress and similar) show only "School
  notification" on a lock screen. When a timetable changes, the affected
  people's phones can quietly refresh it. **Deploy:** nothing to run; leave
  `PUSH_TIMETABLE_REFRESH` and `PUSH_ANDROID_PRIVATE_VISIBILITY` off until
  the apps that handle them are released.


- **LURITS when adding a learner.** The Add learner form takes the learner's
  LURITS number. If another NavigateEd school already has the learner, the
  office is asked to start a transfer request instead of creating a duplicate;
  it is never told which school. If the learner is already, or was before, at
  the school, the office is pointed to their existing record.
- API: notifications now come in three layers: a title, a short excerpt for the
  inbox row and, where there is more to say, a full text, plus the app screen
  each notice opens. `GET /api/v2/me/notifications` lists a person's notices in
  the current school and `GET /api/v2/me/notifications/{id}` returns one with
  its full text after checking again that they may still see it. Exam changes
  now say what the sitting was before, and absence alerts say who to contact.
  Private notices (wellbeing, discipline, progress, transfers and the like)
  still only say that something is waiting. Installed apps see no change; the
  new app screens follow. **Deploy:** run the migrations.

- Platform: each school's mail now goes out on two Amazon SES streams, one for
  account and safety mail (sign-in links, password resets, invitations, welcome
  mails, safeguarding alerts, urgent notices and absence alerts) and one for
  everything else, each through the school's own SES configuration set, so one
  school's newsletter bounces can no longer hold up another school's or its own
  password resets. HQ's new "Mail streams" page (/saas/mail-streams) shows each
  school's streams, whether SES lets them send, any tenant pause SES reports,
  and 30 days of bounce and complaint rates flagged at SES's review and pause
  levels; Operations can set up or check a school's streams there. Off until
  `MAIL_SES_STREAMS` is switched on. **Deploy:** run the migrations. Before
  switching on, see engineering/specs/platform/ses-streams.md (VDM cost check,
  SES key permissions, leave `AWS_SES_CONFIGURATION_SET` empty, then run
  `php artisan mail:ensure-streams`).

- Admin panel: schools can send their notices from their own domain. Under
  Settings, "Email from your domain", the school's Super Admin enters the domain,
  sees the exact DNS records to add (three DKIM records, the bounce MX and SPF
  records and a recommended DMARC record), and presses "Check now" or waits for
  the automatic check. Once Amazon SES has verified it, the school's notices come
  from "<school name> <notices@that domain>"; until then, and always for password
  and invitation emails, from the NavigateEd address. Disconnecting goes back to
  the NavigateEd address at once. Each domain belongs to one school, and every
  connect, verification change and disconnect is in the audit log. HQ's onboarding
  checklist shows whether the school's domain is verified. Off until
  `MAIL_SENDING_DOMAINS=true`. **Deploy:** run the migrations; to switch it on, give
  the SES keys the identity permissions listed in
  `engineering/specs/platform/sending-domains.md` and set `MAIL_SENDING_DOMAINS=true`.

- Academics: the learner and family week view shows named breaks and the
  learner's activity sessions next to lessons. Activity sessions can now have
  a start and end time. **Deploy:** run the migrations.
- Notifications: quiet hours. Each person can set hours when push
  notifications wait and arrive afterwards; safeguarding alerts, urgent
  notices, absence alerts and new sign-in alerts always come through. Pushes
  now tell the apps their channel and urgency. **Deploy:** run the migrations.
- Platform: HQ (`/saas`) now needs two-factor sign-in. Every Navigate Ed staff
  member sets up an authenticator app (Google Authenticator, Microsoft
  Authenticator, 1Password and others) and gets ten single-use recovery codes.
  After that, each sign-in asks for a code from the app before any HQ page
  opens. An Owner can reset someone's two-factor sign-in from the staff page
  with a reason; the operator console command `platform:reset-two-factor
  {email}` is the break-glass for an Owner's own. School accounts are not
  affected. **Deploy:** run the migrations. Every platform staff member sets
  up their app at their next HQ sign-in.

- Platform and Admin panel: audited support access. Navigate Ed Support,
  Operations and Owner staff ask a school for access at `/saas/support`, with a
  reason and a length of up to four hours. The school's Super Admins are told
  at once and approve or decline under Settings, "Navigate Ed support access";
  when the school cannot be reached, another Owner can open a request that has
  waited at least 15 minutes, with a recorded reason, and the school is told
  immediately. A school's decline stands: nobody can ask again or override for
  a day. While it is open, the staff member sees a read-only view of the
  school's set-up and accounts (learners and parents only by exact email),
  never wellbeing, counselling, health, safeguarding, discipline,
  early-warning or support-plan records. Every page they open, and every
  account a list or search showed, is listed for the school and in the HQ
  audit log. It ends by itself, or earlier when the school or the staff member
  ends it. **Deploy:** run the migrations.

- Platform: announcements to schools at `/saas/announcements` for Owner and
  Operations. Write release notes, a maintenance window or a policy change, aim
  it at every school, at schools on a package or at a lifecycle stage, or at
  chosen schools, see how many schools and Super Admins it reaches, then
  publish. Each school's Super Admins get a notice and a banner in their panel
  while it is current; dismissing the banner counts as read, and HQ sees how
  many schools and Super Admins have read it. A published announcement never
  changes: withdraw it and publish a new one. Super Admins find every
  announcement for their school under Settings, Navigate Ed announcements.
  **Deploy:** run the migrations.
- Admin panel, Apps and API: the LURITS number is now the number a learner
  is known by. Person profiles, the student report page, report cards, school
  transfer packages, exit archives and the leaving certificate show it first,
  then the school's admission number. A learner without a LURITS number shows
  the NavigateEd number instead, labelled "NavigateEd number (temporary)". The
  v2 API adds `primary_identifier` to learner profiles, a parent's children,
  guardian approvals and the wellbeing learner picker; only the learner,
  guardians who may approve and staff with profile permission receive it.
  No migration.
- Admin panel and Platform: a support desk. A school's Super Admin, or office
  staff who look after the settings, open a support ticket to Navigate Ed
  under Settings > Navigate Ed support (a question, a problem, a request or
  billing; normal or urgent), read Navigate Ed's replies, add messages and
  close it, and are told in the panel when Navigate Ed replies. In HQ, Owner,
  Operations and Support work the queue at `/saas/support` (urgent first, then
  oldest, with tickets changed since their last look marked new), reply, keep
  internal notes the school never sees, assign tickets and set their status.
  HQ's page for each school shows its open tickets, and its timeline notes
  when a ticket opens and closes. Messages are encrypted and never written to
  the audit log. **Deploy:** run the migrations.
- Admin panel and apps: learners who leave at the end of the year are no longer
  locked out on the day the new year starts. Until 31 March they, and their
  guardians, can still sign in and read their final reports, marks and
  documents, but change nothing; then their access ends. A learner who leaves
  during the year reads for 90 days. Leavers are not in any class list, register or
  group of the new year, are counted apart on the dashboard and are marked on the
  student list. The new Leavers page (Calendar and years) lets the office bring a
  leaver back as a returning learner or end their access sooner. **Deploy:** run
  the additive `school_memberships.leaver_until` migration.

- Platform: a breach and incident register at `/saas/incidents` for Owner,
  Operations and Support. Each incident records the schools it affects and
  when, how and whom at each school Navigate Ed told, whether the Information
  Regulator must be told and when it was, a timeline and a closing review. An
  incident that may involve personal information cannot close until every
  school has been told and the Regulator decision is recorded. **Deploy:** run
  the migrations.
- Notifications now reach every device a person is signed in on, not only the last
  one: a parent's phone and tablet, or a teacher's two devices, all receive them.
  Signing in to an app on another device no longer signs the first one out (up to
  five devices per app), and signing out ends only that device. **Deploy:** run the
  additive `user_devices` migration, which copies each existing push registration.
- Platform: an HQ audit log at `/saas/audit` shows who at Navigate Ed did what
  to which school, when and why (reasons, before and after values), filtered by
  school, action, staff member and date, and exported as CSV. A school's own
  history is never shown there, and each export is itself recorded.
- Admin panel: exam mode. Exam periods per grade (Exams and results → Exam
  periods) turn a learner's timetable days into exam days, leaving normal
  lessons out unless the school keeps them. Each exam sitting can have a venue,
  and learners and guardians are told when an upcoming sitting's date, time or
  venue changes, or it is removed. **Deploy:** run the migrations.
- Platform: platform staff roles (Owner, Operations, Support, Finance, Content,
  Sales read-only). Every HQ page and action checks the role. Owners invite
  staff by email from `/saas/staff`; the invited person chooses their own
  password. Access is time-limited and every change is audited with its reason.
  Existing grants became Owner. **Deploy:** run the migrations.

- Developers: school permissions are now described as modules with access levels
  (view, contribute, manage) and named special abilities, in
  `config/module_access.php`, as the foundation for a simpler role editor. No
  permission changes for anyone.

- Finance can record verified cash/EFT fee receipts, review allocation to explicit
  holder shares, and retain overpayments as credit. Parents have private fee
  statements showing their charges, applied payments and held credit separately.
  Legacy payment history remains separate; no payment gateway is activated.
  **Deploy:** rehearse the additive payment migration and reconciliation on isolated
  staging, explicitly assign `fee-payments-manage` to real Finance roles, and
  confirm the school allocation rule before allocating.

- The learner's LURITS number can be recorded as their primary identifier, once per
  learner across all schools, encrypted, and never written twice: a number another
  learner already holds opens a conflict for review instead. The NavigateEd number
  remains the temporary identifier for learners without one (ADR 0007). Screens,
  API fields, imports and SA-SAMS keying follow. **Deploy:** run the additive
  `learner_identifiers` LURITS migration.

- Internal school ledger foundation with balanced immutable integer-cents postings,
  permanent receipt identities, protected wallet balances and nightly reconciliation.
  Existing receipt history stays intact; this does not activate wallets or payment providers.
  **Deploy:** rehearse the additive migration (including trigger privileges) and
  `ledger:reconcile --json` on isolated staging; route critical reconciliation logs
  to operations before real money uses the ledger.

- The new-year wizard can carry teaching groups and activity groups (teams, clubs,
  societies) into the next year empty, with their tree, rules and seasons moved to
  the new year, so learners and staff are allocated afresh and nobody is left in
  last year's groups.

- Learner Home and parents' child academic screens remain accessible with unpaid
  fees, including saved links to the former fee-lock screen. Guardian fee privacy
  and approval checks continue to apply.

- Short days: the timetable workbench can replace a bell schedule on one date
  with short-day bells. Lessons keep their order and take the short times;
  lessons past the last short period do not run that day. Clashing short days
  are refused, and affected learners, guardians and teachers are notified. The
  v2 week adds an optional `day_pattern`. **Deploy:** run the additive
  `bell_schedule_overrides` migration.

- **Platform admin area (`/saas`), first version.** Platform staff can see every school with its package and counts of learners, parents and teachers, set up a school with its first administrator (a one-time password, shown once), change its package and modules, and suspend, archive or restore it with a reason. It reads no person's record and is closed to school administrators. Opening it needs both a platform staff identity and an expiring grant set from the operator console. Support access into a school, imports and usage/health views are not part of this version. **Deploy:** run the migration, then `php artisan platform:staff-identity <user_id>` and `php artisan platform:admin-access <user_id> --hours=8` for each operator.

- Staff invitations: a new school's first Super Admin is emailed a one-use
  link to choose their own password instead of being handed a one-time
  password, and the platform can send it again until it is accepted. School
  staff can send a colleague a sign-in invitation from their profile.
  Accepting verifies the email address and signs out every other session.
  **Deploy:** run the additive `staff_invitations` migration; mail must be
  configured for the emails to arrive (the link is also shown once).

- Manage dated teaching groups, learner memberships and teacher/homeroom/leadership assignments with explicit class-grade mappings, audited corrections and current allocation checks for existing app projections.

- HQ school records: platform staff keep each customer school's stage (lead to
  live, at risk, churned), profile, contacts, contracts with the POPIA operator
  agreement, and a timeline of notes, on a new page in `/saas`. The school list
  shows each school's stage. Schools never see it, and it shows none of a
  school's people. **Deploy:** run the additive `school_accounts` migration.

- Prepare versioned curriculum topics and term assessment plans with exact task/question maxima, separate term and annual weights, allocated-teacher access, audited publication and explicit new-year draft copying.

- Guardian management: the school office can give a learner any number of
  guardians, each with a relationship and the rights to approve and to see
  fees, from "Manage guardians" on the learner's profile. A learner always keeps
  at least one approving guardian, and removing a guardian ends their access at
  once. **Deploy:** run the additive `guardian_links` migration.

- Add versioned CAPS/IEB subject reference imports and school subject mapping, with source provenance, explicit publication, tenant/permission checks and a Material 3 catalogue page.

- Role editor by module: school administrators can create and edit custom
  roles by choosing a level (no access, view, contribute, manage) for each
  module that is switched on, plus named abilities. Sensitive abilities need a
  confirmation and are audited. The old checkbox editor is still available.

- School staff can edit app contacts and branding under Settings → School information, publish PDF policies and schedules, and show current-year term dates in both apps’ My school screen. School selection is explicit on each request. Unlisting removes a publication from the screen while its public link remains valid.

- Role templates: the role editor can start a new role from nine templates
  for common school jobs (principal, deputy, office, bursar, head of
  department, grade head, activities coordinator, reception, IT). Sensitive
  abilities are never part of a template.

- Add school-specific person profile categories, protected defaults, encrypted custom values and authorised API v2 forms/configuration. Partial edits validate supplied fields without blocking on unrelated incomplete fields. School admins can arrange fields in Settings, and authorised people use the same form metadata in the panel and both apps. Saves update changed fields only. Permitted CSV exports filter sensitive values before decryption; private drafts stay out of browser/session storage. Private file fields accept encrypted PDF/PNG/JPEG attachments with authenticated previews and preserve unrelated edits. Legacy migration remains in progress. **Deploy:** preserve APP_KEY; rehearse the additive profile migration and back up profile values, attachment metadata and private ciphertext before rolling it back.

- Who can see what: a read-only page shows one person's or one role's level in
  each module, their abilities (sensitive ones marked), any stray permissions,
  and the pages in their menu, exactly as that person's own sidebar for a
  person.

- People can choose a global handle in the shared app settings. Learners under 18 need a verified guardian to approve the proposal; adults choose directly. Handles avoid personal identifiers, changes are limited per term, and old names remain reserved for 90 days. Approved handles work alongside existing sign-in names. Existing app/API responses remain compatible.

- Product catalogue: the 44 module switches are grouped into ten product
  modules with a readiness level and a price placeholder, shown to platform
  staff at `/saas/packages`, and a new `pilot` package includes them all.

- School admins can control included features under Settings → School modules. Disabled features disappear from navigation and app context, and their routes refuse access. Required features stay on; fees with retained records become read-only. Existing schools retain their seed features, while new explicitly packaged schools start fees off. Native apps refresh on a queued school-specific context change.

- Family reach: a page for the school office shows how many current learners
  have a guardian the school can reach in the parent app or by verified email,
  reach by homeroom, and which learners to follow up.

- Lifelong student numbers work alongside existing learner logins. School staff can append dated homeroom moves through API v2, while installed clients keep their existing session fields. Parent context includes each authorised child's school, approval, number and homeroom. **Deploy:** rehearse `people:backfill-identities --queue` on isolated staging before production acceptance; historical dates imported from the old session table are marked as legacy-derived. Permanent cleanup preserves numbers and other schools' accounts, and refuses learners with dated enrolment history.

- Guardian access approvals and one-use emailed invitations, including email verification, withdrawal, separate fee access and versioned consent. Existing family relationships are migrated without inventing approval. The shared approval screen uses generated API models.

### Developers

- **Web app preview** (Actions > Web app preview): builds the learner and parent app and the teacher app for the browser and puts them on Firebase Hosting preview channels, so both can be opened and installed on a phone as web apps before a store or production release. The job summary gives each address and says whether the chosen server accepts it. PR runs save downloadable artifacts without Firebase credentials. Publishing requires an explicit `deploy_preview` dispatch from `main`, defaults to staging and refuses the configured production API. The live portals are unchanged. **Deploy:** on a server running this code, add both preview addresses to `CORS_ALLOWED_ORIGINS` with the portal addresses before signing in there.

- Narrow Phase 1 closeout to supported clients after the product owner confirmed there are no legacy clients. Legacy profile storage retirement and compatibility writes are no longer release prerequisites; existing data remains subject to privacy controls.

- Integrate dependency PRs #48, #49, #50, #53, #55, #56, #57, #59 and #60, including their compatibility migrations. Pin Flutter CI and deployment builds to 3.47.7 / Dart 3.13.5 for Google Fonts 9; preserve the Material type scale and refresh all affected lockfiles. File Picker 13 supports both apps' attachments and single-file uploads. TeX 5 starts its renderer, keeps question and answer views independent, and loads its bundled browser bridge; release phones allow its device-local asset server. Image 4 supports all legacy image helpers as well as tenant uploads. Add regression checks for image encoding, school role revocation, checkout setup/cancellation, typography and rendered exam maths.

### Security

- Private group conversation drafts keep their conversation type, audience and private reply target after validation errors. Destinations are checked again before retaining text; unavailable targets discard the draft rather than selecting a broader audience. Reply drafts are not copied into the new-conversation form.

- Pin the shared web, worker and scheduler image and PHP CI jobs to PHP 8.3.36 for CVE-2026-109618 and CVE-2026-109619 (PHP2Shell). CI now verifies the built runtime and Apache configuration. **Deploy:** rebuild and replace all three roles; existing containers remain vulnerable until updated. See `engineering/runbooks/deploy.md`.

Phase 0 (Groundwork): built on the phase branch, not yet released to production. Nothing changes for teachers, learners and parents on their phones; the admin panel's navigation and school-year tools are new, and the platform underneath is ready for Phase 1.

### Admin panel

- Navigation is arranged by job (Home, People, Academics, Communication, Money, Activities, Wellbeing, Settings) in plain words. Every page keeps its address.
- Home shows a setup checklist worked out from the school's data, and today's numbers.
- Global search in the header ("/" or Ctrl/Cmd+K) finds learners, parents, staff, homerooms, subjects and settings pages.
- The year switcher in the header shows past years read-only: a banner on every page, write buttons disabled, and the server refuses changes.
- "Start the new school year" creates next year, copies what you choose (terms, subjects and their teachers, the timetable) and makes it current in one step.
- The header, navigation and new page components are built with the Material 3 library instead of Bootstrap.
- The teacher list has Deactivate and Activate; a deactivated teacher is signed out everywhere and cannot sign in.
- The Email Configuration page's "Amazon SES" choice now works.
- Fixed: the staff list did not load when a staff member had no date of birth.

### Apps

- iOS builds of both apps (and the teacher app's facilitator and vendor builds) take their version and build number from `pubspec.yaml`, as Android does. Every iOS build used to report build number 3, so App Store Connect would refuse the second upload, and force update compared against the wrong build.
- Both apps read the server address and environment from build settings (`--dart-define` `API_BASE_URL`, `ENV`, `FLAVOR`, or `config/staging.json` and `config/production.json`) instead of `lib/utils/constants.dart`.
- Shared code, sign-in and notifications moved into the packages `eschool_core`, `eschool_session` and `eschool_notifications`; nothing changes on phones.
- Tablet and desktop layouts: a navigation rail and a centred content column. Routes take typed arguments.
- Portal addresses and `navigateed://` links open the matching screen after sign-in.
- Web builds of both apps, for `app.navigateed.co.za` (learners and parents) and `teach.navigateed.co.za` (teachers). Uploads, payments and push notifications are not available in the browser yet.
- The web portals sign in with the panel's session cookie (no token in the browser) and show pictures and PDFs from the panel; a browser that reports an invalid language opens in English instead of a blank page.
- Teacher app: the take-attendance button and each lesson on Schedule are read out by screen readers.
- The fees list shows the learner's homeroom ("Grade 5 A") instead of "Grade".

### API

- `/api/v2` in the same `{error, message, data, code}` envelope, starting with `GET /api/v2/me/context`: the user, roles, current school, switched-on modules, notification rules, feature flags, sign-in methods and children.
- Named rate limiters (`api`, `auth`, `money`) and an `Idempotency-Key` header for v2 writes.
- `admin-panel/openapi.yaml` (OpenAPI 3.1) is the contract: linted in CI, every v2 response is validated against it, and `packages/eschool_api` is generated from it.
- API responses now carry the security, rate-limit and CORS headers (`ResponseService` returns its response instead of exiting). The v1 API is unchanged: the 162 response snapshots still match.

### Platform

- Mail and push notifications are sent by queued jobs with retries, and every delivery attempt is recorded.
- Transactional email goes through Amazon SES in Cape Town; bounces, complaints and deliveries come back from SNS and are recorded.
- A domain-event outbox, dispatched every minute.
- Audit log for users, learners, marks, fee payments and settings.
- Money is stored and computed in integer cents beside the old float columns, which keep working for v1 (back-filled daily by `money:backfill-cents`).
- Settings can carry a school id, ready for Phase 1.
- Structured JSON logs (`LOG_CHANNEL=json`) to stderr with a request id, user id, school id and route on every line, an `X-Request-Id` response header, and exceptions formatted for Cloud Error Reporting. Log context carries ids only, never request bodies; e-mail addresses, ID numbers and phone numbers in messages are masked.
- `GET /up/worker`: a health check that fails when a queued job has waited more than ten minutes. A job that uses up its tries is logged once as "Queued job failed" (by name, without its data), and log lines written by a job carry its name and id.
- Container roles: the one Docker image runs as the web service (default), the queue worker (`CONTAINER_ROLE=worker`: `queue:work --tries=3 --timeout=120`, restarted hourly, stopped gracefully on SIGTERM), the scheduler (`CONTAINER_ROLE=scheduler`: one `schedule:run`, for a Cloud Run Job started every minute) or a one-off `artisan` command. Only the web role migrates (one instance at a time, behind a MySQL lock) and runs the installer seeders, `InstallationSeeder` and `EmailSeeder`, on every start.
- The database queue's `retry_after` is 180 seconds, longer than the worker's 120-second job timeout, so a slow job is not picked up twice.
- PHP no longer puts function arguments into exception traces (`zend.exception_ignore_args`). The image adds the `pcntl` extension, which `queue:work` needs to enforce job timeouts and stop cleanly.
- Routes are registered once.
- Fixed: fresh container installs failed on the `EmailSeeder` name.

### Security

- One password rule everywhere: at least 10 characters, letters and numbers, and not found in known breaches.
- Deactivated teachers are signed out and cannot sign in.
- App tokens are named per app; signing in signs out the same app's other devices, and a password change revokes every other session.
- CORS is limited to the panel and the web portals.
- Web portals use Sanctum's cookie mode: a portal sign-in starts a new session instead of issuing a token, a refused sign-in keeps no session, signing out ends it, and a password change elsewhere ends it. The panel no longer encrypts its cookies twice (everyone signs in to the panel again once after the deploy).
- Uploaded files under `/storage` send CORS headers to the panel and portal origins only (`GET` and `HEAD`, `Vary: Origin`).
- New learners' passwords are set by a guardian through an e-mailed link or a printed one-time code; passwords that were a date of birth are flagged for a change.
- Uploads are stored per school, client file names are sanitised, and files whose content does not match their type are refused.
- A report-only content security policy on the panel, with violations reported to `/csp-report`.
- Secrets are read from Secret Manager (environment variables) instead of `settings` rows; `php artisan secrets:audit-settings --purge` clears the old rows.
- Failing requests are logged without their values.
- A custom role named "Admin" no longer sees every learner.
- Secret scanning with gitleaks over the whole history on every pull request, push to `main` and weekly: no credentials found. The Firebase Android API keys in `google-services.json` are public client identifiers and are allowlisted for that file and key format only; the product owner should confirm their restrictions in the Cloud Console (`engineering/security/phase-0.md`).
- Dependabot for Composer, pub (both apps and every package), npm (docs and the panel's Playwright project), Docker and GitHub Actions. The dependency audit also lists outdated Dart packages (`dart pub outdated`, informational). Threat model and the Phase 0 security record in `engineering/security/`.

### Operations

- Environments on Google Cloud: `infra/gcp/provision.sh staging|production` creates or updates Cloud SQL (backups; point-in-time recovery in production), the uploads bucket, Secret Manager secrets (names only), least-privilege service accounts, the Cloud Run services `navigateed-admin` and `navigateed-worker`, the `navigateed-scheduler` job with an every-minute Cloud Scheduler trigger, Workload Identity Federation for GitHub Actions, uptime checks and alerts. It gives the panel, the worker and the scheduler job the SES settings and keys and the secrets that left the `settings` rows, each mounted once it has a value. Not yet run against a real project.
- Deploy workflows: staging on every merge to `main` once the repository variable `STAGING_DEPLOY_ENABLED` is `true`; production from a `v*` tag after a reviewer approves, with a database backup first and smoke tests after. Both deploy the documentation and the two web portals to Firebase Hosting.
- `SESSION_DOMAIN=.navigateed.co.za` for staging and production, applied by `provision.sh` once `APP_URL` is on that domain; until then the deploy workflow builds the portals with `WEB_AUTH_MODE=bearer` (the `WEB_AUTH_MODE` environment variable overrides).
- Runbooks for environments, deploy, release, rollback, restore (with the restore drill, not yet run) and incidents in `engineering/runbooks/`.
- Local stack with Docker Compose at the repository root: MySQL 8, the panel, the worker and the scheduler, built from the same image as Cloud Run.
- k6 smoke test (`engineering/perf/k6-smoke.js`) with our p95 budgets (Build Bible 5.2) (300 ms for lists, 150 ms for `/api/v2/me/context`), run by hand from **Actions > k6 smoke**.

### Removed

- The in-panel System Update and the `storage-link`, `migrate` and `seeder_install` URLs (the container does these at start).
- The Razorpay, Paystack and Flutterwave gateways, from the panel and from the learner and parent app. Stripe stays, the v1 API is unchanged, and old transactions still show their gateway.
- The unused `EnsureUserIsTeacher` middleware.

### Developers

- `engineering/`: phase plans and questions, feature specs, decision records (ADR 0001 to 0005), runbooks, the security record, the self-test record and the living appendices; and this changelog.
- Demo account credentials are omitted from this public changelog.
- Playwright checks of every panel page at 1280 and 1024 px with axe, the main flows and the removed URLs, in CI.
- Test suites for tenancy (every API route), the role matrix (every route against every role) and the OpenAPI contract (every v2 response).
- CI builds both apps for the web and runs their smoke flows on an Android emulator and in Chrome.
- The `EmailTempalate` model is renamed `EmailTemplate` (the table name is unchanged).

**Deploy:** deploy the worker (`navigateed-worker`) and the scheduler job with this release: mail and push are queued now. Run `infra/gcp/provision.sh` for the environment, which sets `LOG_CHANNEL=json`, `CACHE_DRIVER=database`, `QUEUE_CONNECTION=database`, `MAIL_MAILER=ses` and the SES settings, and creates the secrets; add the SES keys and the secrets that left the `settings` rows to Secret Manager, re-run the script, then run `php artisan secrets:audit-settings --purge` (`engineering/runbooks/release.md`, step 7). See `engineering/runbooks/environments.md`.

### Phase 5 fee corrections

- Finance can review and post an immutable credit note against a recorded invoice
  share, reducing debt or restoring paid money to the original receipt's held
  credit while retaining holder and learner restrictions.
- Finance and private parent statements show original charges, credit notes and
  net balances; private Finance explanations remain excluded from parent reads.

### Phase 5 fee items

- Finance can maintain additional learner/family fee items and review one-off
  occurrence invoices with exact payer shares and duplicate protection.
- Family charges bill once, retain real learner visibility and work with existing
  credit notes, held payments and learner pins. Tuition-first allocation now
  distinguishes annual tuition from additional items.

- Phase5: personal wallets, private statements and guardian controls independently
  of fees, with explicit Finance settings and family/teacher/facilitator app access.

- Phase 5: verified school-owned master wallet funding, private printable receipts
  and shared outside receipt identity across fee and wallet collections.

- Added independently confirmed operator cash handovers, custody reservations, physical shortage limits and nightly cash reconciliation; fee, wallet and vendor cash payouts use the actual operator’s custody.
