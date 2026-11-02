# Teachers share their own questions (question banks)

Action Plan 3.6. Until now a daily practice question belonged to one teaching group (`practice_items.teaching_group_id`). Groups are per year, so a teacher's questions died with the group at year end and could not be used in a parallel class. This step gives every question a subject-and-grade bank in its school, an author and a sharing choice, and lets it be used in any number of groups.

## User stories

- As a teacher, I open the bank for a subject and grade I teach and see my own questions, the questions used in my groups and the questions colleagues have shared with everyone teaching that subject and grade.
- As a teacher, I use a question in another of my classes, or stop using it there, without rewriting it.
- As the author, I choose who may find and reuse my question: only me, the teachers of the groups using it, or every teacher of the subject and grade.
- As the author or a subject lead, I archive a question so no learner gets it, and restore it later. A colleague who uses my question cannot change its wording or its sharing.
- As a teacher after the year change, my questions are still in my bank, and the new-year wizard can keep them in use in each group's copy.

## Rules

- **The bank.** A question belongs to its school, subject and grade (`subject_id`, `grade_level_id`). It stays in that bank whatever happens to its groups. `teaching_group_id` stays as the group it was first written for.
- **Where it is used.** `practice_item_groups` lists the groups a question is used in. Daily practice, tutor checkpoints, the practice catalogue in the API, practice evidence for mastery and the classroom locks now look at these uses instead of `teaching_group_id`. A learner gets a question only through a group they are in, as before.
- **Who may find and reuse it** (`visibility`):
  - `private` (only me): only the author may put it into a group.
  - `groups` (the teachers of the groups using it): any teacher allocated today to a current group that uses it may also use it in their other groups of the same subject and grade.
  - `school` (every teacher of this subject and grade): any teacher allocated today to a current group of that subject and grade may use it.
  - Whatever the choice, the teachers of a group always see the questions their learners get there, and the author always sees their own.
  - Subject leads see every question in the grade's banks, private ones included, because they answer for what learners are given.
- **Who may change it.** Only the author or a subject lead may change the sharing, archive or restore it, or approve new wording (through the existing teacher-app approval). For this step a subject lead is a person who already oversees every teacher's learning content in that grade (`ContentPolicy::leads`): academic managers, the grade head and the principal. Everyone else may only use it. Sharing and archiving never change the approved revision.
- **Using a question in a group** needs: a current allocation to that group today (as for approving), daily practice switched on and writable, the question visible to the teacher, the same subject and grade as the group, an active question, and the teacher's right to reuse it. Using it twice changes nothing. Stopping only removes the use; the question stays in the bank.
- **Does it reach the learners?** The page shows, for each current-year use, whether the group's learners get it in daily practice, or why not yet: its source material is not published to that group, or its topic is not in the group's published curriculum plan, or it is archived. After the year change both usually wait for the new year's material and plans; the author then approves the question again with this year's material in the teacher app, as for any wording change.
- **Year change.** New carry-forward choice `practice_banks` (`TransferPracticeBanks`), offered while daily practice is on, after `teaching_groups` (which it ticks too). Each active question used in an active group of the old year is used in that group's copy in the new year (same subject, grade and name). A use the new year already has is left alone, and the wizard skips the choice when the new year already has uses. Archived questions stay behind. The old year keeps its uses as history. The actor needs academic-structure management.
- **Never across schools.** Every query runs inside the current school (`BelongsToSchool`). Another school's question, group or bank is not found, and nothing changes in either school.
- **Audit.** `learning.practice_used`, `learning.practice_unused`, `learning.practice_shared` (old and new sharing and archive state) and `learning.practice_banks_carried`.

## Existing questions

The migration keeps every existing question working exactly as before:

- subject and grade come from the group it was written for;
- the author is whoever approved its first version (left empty only if a question somehow has no version; then only subject leads may change it);
- sharing is `groups`, so the teachers of that group keep seeing and using it, and nobody else does until the author chooses;
- it gets one use, in that same group, dated when the question was created.

Rolling the migration back is refused while any question is in use.

## Data

- `practice_items`: new `subject_id` and `grade_level_id` (required, foreign keys), `author_id` (foreign key to users), `visibility` (`private`, `groups`, `school`; default `groups`), index `practice_items_bank` (school, subject, grade, active).
- `practice_item_groups` (school-owned): `practice_item_id`, `teaching_group_id`, `added_by`, timestamps; one row per question and group.

## Panel

`/question-banks` (Academics, "Question banks"; daily practice module; teachers and academic managers). Choose a subject and grade, then for each question: wording, topic, author, sharing, the current-year groups using it (with whether learners get it), earlier years' uses, "Use in this group" for the teacher's own groups, "Stop using" for their own groups, and, for the author or a subject lead, the sharing choice and archive or restore. Writes go through `QuestionBankRequest` and `QuestionBanks`, which checks the actor, the question and the group again.

## Acceptance tests (`Learning/QuestionBanksTest`)

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_a_new_question_belongs_to_its_subject_and_grade_author_and_group` | Tara approves a question for her group | she opens her bank | subject, grade, author and `groups` are set; one use in her group; learners still get it |
| `test_a_question_shared_with_the_subject_is_reused_by_a_colleague_in_another_class` | Tara's question, not yet shared | she shares it with the subject and Theo uses it in 7B twice, then stops | hidden from Theo until shared; used once in each class; in 7B's catalogue; Theo cannot change it; it reaches 7B; Tara's class keeps it |
| `test_a_question_used_only_where_its_material_is_not_published_waits_for_material` | the source material is not published to 7B | Theo uses the question there | the page says it waits for material |
| `test_private_and_my_groups_questions_stay_out_of_colleagues_reach` | a private question | Theo looks for it and tries to use it; then co-teaches Tara's group; then Tara shares with the groups using it | not found (404); seen but refused (403); then used. Nothing written on refusals |
| `test_only_the_author_or_a_subject_lead_changes_a_question` | Theo uses Tara's shared question | he changes its sharing or approves new wording | refused (403), nothing changes; as grade head he archives it, the revision stays and learners no longer get it |
| `test_wrong_role_and_wrong_group_are_refused_and_nothing_is_written` | a shared question | Theo uses or stops it in Tara's group, a learner or a parent changes or uses it, a parent opens the page, Theo posts a change | refused; counts and sharing unchanged |
| `test_another_schools_question_and_group_are_not_found_and_neither_school_changes` | School A's question and group | School B's teacher uses or changes them; School A opens an unknown bank | not found; School B has no banks; nothing changes in either school |
| `test_the_page_lists_the_bank_and_a_colleague_reuses_a_shared_question` | Tara's question | she opens the page and shares it; Theo opens it and uses it; Theo sends a bad sharing value | listed with its group and sharing form; shared; Theo sees "Use in this group" but no sharing form; used; validation error |
| `test_a_bank_outlives_its_groups` | Tara no longer teaches any group | she opens her banks | her subject and grade bank and her question are still there |
| `test_the_year_change_keeps_questions_in_use_in_each_groups_copy_once` | an active and an archived question in Tara's group | the wizard's options; groups and banks carried, banks twice; daily practice off | offered with count 1, teaching groups ticked too; the copy uses the active question once; last year keeps both uses; nothing to carry with the module off |
| `test_a_teacher_cannot_carry_question_banks_into_the_new_year` | the new year's group copy exists | a teacher runs the carry | refused (403); nothing written |

The migration's backfill was also checked by hand on the test database (old-style rows: subject and grade from the group, author from the first version's approver, `groups`, one use dated at creation; rollback refused while uses exist).

## Not in this step

- Browsing banks, sharing and reusing questions in the teacher app. The v2 API and OpenAPI are unchanged: the app keeps approving questions per group, and its group catalogue now lists the questions used in that group.
- Re-linking a carried question to this year's material from the panel. Today the author approves the question again with the new material in the teacher app (one current version per question, so its source is the same for every group using it).
- A separate "subject head" appointment. Subject leads are the existing content leads (academic managers, grade head, principal) until the school structure has subject heads.
- Copying a colleague's question to edit your own version of it.
- Sharing between schools or with the platform library (Action Plan 3.3).
