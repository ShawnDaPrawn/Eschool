# Versioned curriculum and assessment plans

Phase 2 step 3. Backend, App and Admin UX.

## Stories and acceptance

A school administrator or an allocated subject teacher prepares a subject/grade/
year curriculum with ordered topics and term assessment plans. Tasks record their
component, term and annual weights, exact maximum marks, due date, topic links
and optional numbered questions. Published definitions are immutable; revisions
and year copies are drafts with provenance. A teacher can access only allocated
subject/grade/year plans. Parents and learners cannot read draft assessments.

Each term plan may be published independently; an incomplete annual plan does not
block capturing or teaching a prepared term. Publication checks that included
component weights total 100%, each component's task weights total 100%, question
maxima equal their task maximum, and every linked topic/term belongs to the same
school, curriculum and grade range. No policy is guessed from a subject name.
Draft editing accepts incomplete future work. Stale revisions return 409 instead
of overwriting another editor. An unchanged retry creates no new version/audit.

## Data

School-owned curriculum_plans (subject, grade, year, version, source plan, title,
standard snapshot, annual component weights, rounding and missing-mark policy),
curriculum_topics (plan, term, key, order, weeks), assessment_plans (curriculum,
term, components, state, revision, publication actor/date), assessment_tasks
(plan, key, component/annual component, term/annual weights, max mark units,
due date, topic keys, source task), assessment_questions (task, number, maximum
units, topic keys). All disputed records soft-delete. Stable provenance supports
same-year revision without losing mark history; cross-year copies never copy marks.
Marks use exact hundredth units; client forms show decimal mark strings. Calculation
will use rational arithmetic and round only at the final configured boundary.

## Services and endpoints

AcademicAccess resolves a fresh active member, explicit school permissions and
subject/grade/year teaching allocation. Administrators manage school plans;
teacher reads/writes also require allocation. Authorization precedes idempotent
replay. CurriculumPlans owns scoped transactions, optimistic revisions, bounded
nested validation, publication and copy. API v2 academics curriculum-plans CRUD,
assessment-plan publication and curriculum copy are thin request/policy/service/
resource endpoints. Lists paginate and filter by year/subject/grade/state.

The new-year wizard offers an explicit copy-curriculum-as-drafts option. Dates
shift by the target year delta, invalid leap days are refused before any writes,
and term IDs map only within matching grade bands. Current year stays unchanged.

## Screens, notifications and module

Material 3 plan list/editor and a shared adaptive academic client provide topic,
task and question editing with useful draft errors and publication previews.
Keyboard workflows preserve pasted/unsaved data on errors. Always-on academics.
Plan changes are audited; no unsolicited family notifications are sent.

## Security and verification

Test other schools and owners, unallocated/revoked teacher access, inactive
members, child/parent refusal, publication immutability, stale writes, component
and question boundaries, atomic nested failures, copy/retry/date mapping, rollback
and unchanged legacy data. Update OpenAPI/generated SDK and language keys; test
cubit/widgets and phone/desktop browser flows with accessibility and overflow.
Existing marks are never silently reassigned by a curriculum revision. Staging,
source/policy acceptance and real pilot evidence remain separate.
