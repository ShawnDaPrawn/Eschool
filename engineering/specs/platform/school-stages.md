# School stages

Action Plan 4.1, "Stage per grade and stage-aware features". This step builds the server and panel side: a stage for every grade at every school, a settings page to change it, stage limits on learner features, and no sign-in for early years learners.

## Why

Younger children should not be learning on a phone. NavigateEd groups grades into three age stages, and what a child can do depends on their stage. Schools differ, so each school can move the boundaries.

## The stages and their defaults

The owner's decision (Action Plan, Decisions: Age stages) sets the defaults:

| Stage | Key | Grades by default |
| --- | --- | --- |
| Early years | `early_years` | Grade R, 1 and 2 |
| Junior | `junior` | Grade 3 to Grade 7 |
| High school | `high_school` | Grade 8 to Grade 12 |

The decision also names Grade 000 and 00 as early years. NavigateEd's grade list (`grade_levels`) starts at Grade R, so those grades have no stage yet (see "Not in this step").

## User stories

- As a principal or school administrator, I can see which stage each grade is in, and move a grade to another stage, so that NavigateEd matches how my school works.
- As a parent of a Grade 1 child, I keep using the parents' app for my child, and my child does not get a sign-in of their own.
- As a Grade 1 learner, if I try to sign in, I am told that early years learners do not sign in themselves.
- As the owner, I can limit a learner feature (for example the tutor) to some stages in `config/modules.php`, and the server refuses it to learners in other stages.

## Rules

1. Every school has a stage for each grade from Grade R to Grade 12. The migration fills `school_stages` with the defaults for every existing school, and HQ's new-school set-up (`PlatformSchools::create`) fills it for each new school. A grade with no row still reads its default.
2. A learner's stage is the stage of their homeroom's grade this school year (`student_sessions` for the current school year, then the learner's recorded class if there is no session). A homeroom without a grade gives no stage, and nothing is limited by stage for that learner.
3. **Who may change the stages:** Super Admin, or staff who hold `setting-create` and are not a parent, learner or teacher: the same people who manage School modules. The FormRequest checks this, and `SchoolStages::update()` checks it again.
4. Only grades whose stage actually changes are written. Each write records who made it (`changed_by`) and goes to the audit log with the old and new stage. A change also records `context.changed` (reason `stages`) so the apps refresh.
5. Unknown grades or unknown stages are refused, and nothing is written.
6. **Never across schools.** `school_stages` is school-owned. The page reads and writes only the current school's rows. A learner's stage always comes from their own school's mapping.
7. **Stage-limited features.** A module in `config/modules.php` may list `stages`. `ModuleGate::allows()` and `writable()` refuse that module to a signed-in learner whose stage is not in the list. The `module:` route middleware uses the same check, so the refusal is a 404 like any switched-off module. Each learner's module list in `/me/context` leaves the module out. Guardians, staff and background jobs are never limited by stage.
8. In this step the tutor and daily practice list `stages: ['junior', 'high_school']`, following the decisions that the tutor starts in Grade 4 and practice in Grade 3. No other module is limited yet. The strengths mirror (Action Plan 4.8, [strengths mirror](../learning/strengths-mirror.md)) is limited to the same stages, because early years learners do not sign in.
9. **No sign-in for early years.** A learner whose stage is early years is refused at the learner sign-in (`POST /api/student/login`, from the app or the web portal) with 403 and the message "Learners in the early years do not sign in themselves. A parent or guardian can follow their learning in the parents' app." No token is issued and no portal session is left. The check sits in `PortalSession::signIn()` next to the guardian approval check, so it runs before any token or session is made. Guardians' own sign-in is not affected.

## Data

`school_stages` (required school-owned):

| Column | Meaning |
| --- | --- |
| `school_id` | The school; foreign key, part of the unique index |
| `grade_level_id` | The grade (`grade_levels`); unique per school |
| `stage` | `early_years`, `junior` or `high_school` |
| `changed_by` | The school leader who last chose this stage; null while it is the default |

The migration's `down()` refuses to drop the table once any school has chosen its own stage.

## Where it lives

- `App\Services\Platform\Stages\SchoolStages`: defaults, the school's mapping, a learner's stage, the sign-in check and the settings write.
- `App\Services\Platform\Modules\ModuleGate`: `allows()`, `writable()` and `context()` apply the stage limit; `allowsStageOf()` answers for a given person.
- Panel: Settings, School, **School stages** (`/school-stages`, `SchoolStageController`, `SchoolStagesRequest`, `platform/stages.blade.php`).

## Acceptance tests

`tests/Feature/Platform/SchoolStagesTest.php`:

| Test | What it proves |
| --- | --- |
| `test_every_school_starts_with_the_owners_default_stages` | The migration and new-school seeding give every school the 13 defaults; seeding twice adds nothing; a school with no rows reads the defaults |
| `test_a_school_leader_moves_a_grade_and_the_change_is_audited_in_their_school_only` | Happy path: the page shows every grade; a change writes only the changed grade, records who made it, is audited with before and after, records `context.changed`; School B's stages are unchanged |
| `test_unknown_grades_and_stages_are_refused_and_nothing_is_written` | Unknown stage, unknown grade, empty list and a smuggled `school_id` are refused; no rows or audit entries change |
| `test_teachers_parents_and_learners_cannot_view_or_change_the_stages` | Wrong role: a teacher, a parent and a learner get 403 on view and save; neither school's stages nor the audit log change |
| `test_the_service_refuses_a_caller_without_the_right_even_without_the_form` | The service checks the right itself |
| `test_an_early_years_learner_cannot_sign_in_but_their_guardian_can` | A Grade 1 learner is refused at sign-in from the app and the portal, gets no token and no session; a Grade 8 learner signs in; the guardian signs in and still sees the child; moving Grade 1 to junior lets the learner sign in |
| `test_each_school_decides_its_own_learners_stage` | Wrong school: School B making Grade 5 early years refuses only School B's Grade 5 learner; School A's Grade 5 learner still signs in and School A's mapping is unchanged |
| `test_a_stage_limited_module_is_refused_to_a_learner_in_another_stage_and_never_to_guardians` | A junior learner has the tutor; after the school moves the grade to early years, the same learner's existing session loses it in `/me/context` and gets 404 from the tutor API; the guardian and teacher are not limited; modules without stages and learners without a graded homeroom are not limited |

## Not in this step

- **The learner's stage in `/me/context`.** Showing each child's stage to the apps changes the API shape (`openapi.yaml` and the generated Dart client), so it waits for an API step. Only the existing module list reflects the stage here.
- **Child mode in the parent app** (Action Plan 4.2), and the junior sign-in rules that go with it: own sign-in from Grade 6 with guardian approval, and Grade 7's optional own sign-in from Term 3.
- **Grade 000 and 00.** They are not in `grade_levels`, which is shared reference data that needs its own migration. Until then a homeroom without a grade has no stage, so it is not limited.
- **Existing sign-ins after a boundary moves.** If a school moves a grade into early years, learners already signed in are not signed out; stage-limited features are refused to them at once, and their next sign-in is refused. Revoking their tokens is a later step.
- **Learner sign-in set-up for early years.** The panel can still email or print a learner's sign-in set-up; the sign-in itself is refused. Hiding that action for early years learners is a later step.
- **Stage limits on other modules,** such as chat or rankings for juniors. The plan names these as product ideas, not decisions, so no module other than the tutor and daily practice is limited yet. Schools cannot change a module's stages; that is platform configuration.
- **Classroom tablet sessions** for early years (foundation classroom card entry) are not learner sign-ins and are not changed.
