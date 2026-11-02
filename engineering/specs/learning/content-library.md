# Platform content library

Action Plan 3.3 (platform-owned library), with the first slice of SA.16
(content administration). Owner role: Backend and Admin UX (HQ), Learning.

Schools write their own practice questions today (`practice_items`, approved
by a teacher). Many schools will not have enough of them. Navigate Ed builds
its own library of practice questions, lessons, diagnostics and past-paper
questions, organised by curriculum framework (such as CAPS), subject and
topic, reviewed by subject reviewers before anything is published. A school
whose library switch is on gets published library questions in daily practice
when it has too few approved questions of its own.

The library belongs to no school. School data never flows into it: no school
request, job or HQ action inside a school can write a library row. Data flows
the other way only: a school's practice set and mastery evidence may point at
a published library version, and say so.

## User stories

- As Navigate Ed Content staff, I add a framework (CAPS), its topics per subject
  and grade, the prerequisites between topics, and where content comes from and
  under what licence, so every item has a topic and a source.
- As Content staff, I write an item as a draft, change it while it is a draft,
  and send it for review.
- As a reviewer assigned to a subject, I see the items waiting for review,
  approve what is right, and send back anything else with a reason.
- As Content staff, I publish an approved version. To change a published item
  I start a new draft; the published version learners see never changes.
- As HQ, I switch the library on for one school at a time, with a reason. It is
  off by default.
- As a learner at a school with the library on and few approved questions of
  my own, my daily practice still has questions: my school's first, then the
  library's for my subject's framework topics.
- As a school, my mastery evidence says whether each practice answer came from
  my own material or the library, and which library version.

## Rules

- **Who.** Two new HQ abilities in `config/platform_roles.php`:
  `library.manage` (Content, Owner): every `/saas/library` page and form except
  the review queue; `library.review` (the new Content reviewer role, Owner):
  the review queue and the decision. Other roles get 403. School accounts never
  reach `/saas`. The services check the ability again and refuse to run while
  a school is the current school (403).
- **Framework.** A code (`CAPS`, `IEB`: capitals, digits, `_` or `-`, unique) and
  a name. The code is the value the exam coach reads from a school's curriculum
  (`curriculum_plans.standard_snapshot.framework`), which is how a school's
  subject finds its library topics.
- **Topic.** Framework, subject code, grade and name (unique within the three).
  The subject code must be a published code of that framework in the subject
  standards catalogue (`subject_standards`) that covers the grade. Each topic
  gets a fixed UUID key.
- **Prerequisites.** A topic's prerequisites are replaced as a set. They must be
  topics of the same framework and subject (any grade). A topic cannot be its
  own prerequisite, and a change that would make a cycle is refused (validation
  error, nothing saved). Paths are bounded at 100 topics, as the school graph's.
- **Source.** Title, kind (written for Navigate Ed, DBE, IEB, openly licensed,
  licensed from a publisher), licence, attribution and an optional https link.
  Every item names one.
- **Item kinds and content.**
  - practice question: the daily practice question format (number or choice,
    question, worked explanation, options, accepted answers, checked by
    `PracticeAnswers`) and an expected time of 60 to 300 seconds;
  - past-paper question: the same, plus year, paper code and question number;
    its source must be DBE, IEB or licensed;
  - lesson: Markdown text;
  - diagnostic: instructions and a list of published practice questions of the
    same framework and subject.
- **Versions.** An item starts with version 1 as a draft. Content changes only
  while a version is a draft. The steps are: draft, then waiting for review,
  then approved or rejected; approved, then published. Saving an item whose
  latest version is approved, published or rejected starts the next version as
  a new draft; while one waits for review, saving is refused (409). A published
  version never changes and no version is ever deleted (the model refuses with
  409). Each item carries a revision, so a form opened before another change is
  refused (409, reload).
- **Review.** Only a reviewer assigned to the item's framework and subject may
  decide, and never on a version they wrote or sent for review (an Owner too).
  A rejection needs a reason (10 to 1000 characters). Reviews are kept as
  written.
- **Reviewers and their subjects.** Content staff assign platform staff whose
  role holds `library.review` to a framework and subject code, and remove them.
- **Publishing.** Only an approved version can be published (409 otherwise),
  and never one older than the version already published. The item then points
  at it; older published versions stay readable for sets already issued.
- **School switch.** `library_school_settings`, one row per school, off by
  default. HQ switches it on or off from the library page with a reason; it
  changes only that school.
- **Daily practice read path.** `DailyPractice::candidates` takes the school's
  own approved questions first, as before. When the school has fewer than ten
  (`LibraryPractice::MINIMUM_OWN`) and its switch is on, published library
  practice questions follow, for the library topics whose framework and subject
  code are those of the group's published curriculum and whose grade is the
  group's. They are ordered like the school's own (due, then weakest topic),
  then by the library's prerequisite graph (earlier topics first). The set is
  still at most ten questions and ten minutes. Every read and review checks a
  library entry again: the switch, the group's frameworks and the published
  version; switching the library off withdraws issued library questions (404).
- **API shape.** A library entry in a daily set has exactly the fields of a
  school entry: `item_id` is the library item, `topic_id` the school's mirror
  node, `source_version_id` the library version. No API response changed.
- **Topic overlay.** `TopicGraph::sync` (and `TopicGraph::overlay`) mirrors the
  library topics of the subject's frameworks, codes and grades into the
  school's `mastery_topics` (with `library_topic_id`, without a curriculum
  topic), only while the school's switch is on. Mirror nodes stay out of
  `TopicGraph::nodes`, so the school's graph editor, mastery map, exam coach and
  tutor see only the school's own topics.
- **Evidence.** `mastery_evidence.content_source` is `school` (the default, for
  every existing writer) or `library`; a library answer also records
  `library_item_version_id`. FSRS memory for a library question is kept per
  learner and library item (`practice_memories.library_item_id`).
- **Audit.** `library_framework_created`, `library_source_created`,
  `library_topic_created`, `library_prerequisites_changed`,
  `library_item_created`, `library_draft_saved`, `library_draft_started`,
  `library_submitted`, `library_reviewed`, `library_published`,
  `library_reviewer_assigned`, `library_reviewer_removed` (no school), and
  `library_school_switched` (against the school, with the reason). Ids, states
  and the switch reason; never item content.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `library_frameworks` | global | framework code and name |
| `library_sources` | global | source, kind, licence, attribution |
| `library_topics` | global | framework, subject code, grade, key, name |
| `library_topic_prerequisites` | global | topic, prerequisite |
| `library_items` | global | topic, source, kind, title, published version, revision |
| `library_item_versions` | global | number, state, content (JSON), expected time, who wrote, sent and published it |
| `library_reviews` | global | version, reviewer, decision, reason |
| `library_reviewers` | global | reviewer, framework, subject code |
| `library_school_settings` | required school-owned | the school's switch |

Columns added to school tables (all nullable, foreign keys into the library):
`mastery_topics.library_topic_id` (and `curriculum_topic_id` is now nullable),
`mastery_evidence.content_source` and `library_item_version_id`,
`practice_set_entries.library_item_version_id` (and
`practice_item_version_id` is now nullable),
`practice_memories.library_item_id` (and `practice_item_id` is now nullable).
Both migrations' `down` refuse while rows use the library.

Every library model uses `PlatformLibraryRow`: creating, changing or deleting a
library row while a school is the current school is refused (403).

## Acceptance tests

`Platform/HqContentLibraryTest`:

| Given | When | Then |
| --- | --- | --- |
| Content staff | add CAPS, a source, a topic and a practice question; send it for review; publish before approval; a Maths reviewer approves; publish | pages show each; a subject code outside the catalogue and a past-paper question with an original source are refused; publishing before approval is 409; then published; every step audited with no school (`test_content_staff_build_a_framework_and_publish_only_after_a_subject_reviewer_approves`) |
| Whole numbers ← Fractions ← Decimals | make Decimals a prerequisite of Whole numbers; a topic its own prerequisite; an English topic as a Maths prerequisite | each refused, edges unchanged; replacing and clearing work (`test_a_cycle_in_prerequisites_is_refused`) |
| A published version | change its content or state; delete it; edit the item | 409, 409, 409; version 2 starts as a draft and version 1 is unchanged and still live; an approved version's content is refused too; a stale revision is 409 (`test_a_published_version_cannot_be_edited`) |
| A version waiting for review | a reviewer for English only; an Owner not assigned; an assigned Owner on their own submission; the Maths reviewer rejects without, then with a reason | 403, 403, 403, nothing written; reason required; rejected; editing starts version 2; a Support member cannot be assigned (`test_only_a_reviewer_for_that_subject_can_approve`) |
| Operations, Support, Finance, Sales; Content on the queue; a reviewer on the manage pages | every library route with a valid payload | 403 everywhere, nothing written (`test_wrong_roles_are_refused_and_nothing_is_written`) |
| A school Super Admin, a School A teacher, a School B teacher; platform staff inside a school | every library page and two writes; create a framework | 403; 403; nothing written (`test_school_accounts_cannot_reach_the_library`) |
| The schema | list the library tables' columns; create, change or delete library rows inside School A and School B | no `school_id`, all listed as global; every write refused, counts unchanged (`test_library_tables_hold_no_school_id_and_no_school_row_is_written_into_them`) |
| Content staff | switch School B on; switch School A without a reason; an unknown school | only School B's row; reason required; 404; audited against School B (`test_switching_the_library_for_one_school_leaves_the_other_alone`) |

`Learning/LibraryPracticeTest` (School A teaches CAPS `MATH-SP` Grade 7, with no
approved questions of its own; the library has Fractions ← Decimals, one
published question each and one draft):

| Given | When | Then |
| --- | --- | --- |
| The switch off | issue today's set | 409 as before, nothing mirrored, the switch reads off (`test_with_the_switch_off_the_library_is_never_offered`) |
| School B on, School A off; then School A on | issue | 409 for A; then two library questions, Fractions first, with the library ids, the mirror topics and 240 seconds; the draft never offered; mirror nodes not in the school graph; switching B off leaves A's set readable; nothing mirrored into B (`test_with_the_switch_on_a_school_with_no_items_of_its_own_gets_library_candidates_and_school_b_does_not_affect_school_a`) |
| One approved school question | issue | the school's first, then the library's (`test_school_material_comes_first_then_the_library`) |
| A set with both | answer each | evidence `school` with no version, and `library` with the version and mirror topic; library memory kept; library tables unchanged (`test_evidence_records_its_source_and_the_library_version`) |
| An issued library set | switch the library off, then read and review | 404 both, no evidence (`test_switching_the_library_off_withdraws_issued_library_questions`) |

`SaasPanelTest` counts the 16 new `/saas` routes (55 in all) and checks each
names its ability. `SchoolOwnedTablesTest` checks the new tables are sorted.

## Endpoints (HQ panel)

| Method | Path | Name | Ability |
| --- | --- | --- | --- |
| GET | `/saas/library` | `saas.library.index` | `library.manage` |
| POST | `/saas/library/frameworks` | `saas.library.frameworks.store` | `library.manage` |
| GET | `/saas/library/frameworks/{framework}` | `saas.library.frameworks.show` | `library.manage` |
| POST | `/saas/library/frameworks/{framework}/topics` | `saas.library.topics.store` | `library.manage` |
| PUT | `/saas/library/topics/{topic}/prerequisites` | `saas.library.topics.prerequisites` | `library.manage` |
| POST | `/saas/library/items` | `saas.library.items.store` | `library.manage` |
| POST | `/saas/library/sources` | `saas.library.sources.store` | `library.manage` |
| GET | `/saas/library/items/{item}` | `saas.library.items.show` | `library.manage` |
| PUT | `/saas/library/items/{item}/draft` | `saas.library.items.draft` | `library.manage` |
| POST | `/saas/library/items/{item}/submit` | `saas.library.items.submit` | `library.manage` |
| POST | `/saas/library/items/{item}/publish` | `saas.library.items.publish` | `library.manage` |
| POST | `/saas/library/reviewers` | `saas.library.reviewers.store` | `library.manage` |
| DELETE | `/saas/library/reviewers/{assignment}` | `saas.library.reviewers.destroy` | `library.manage` |
| PUT | `/saas/library/schools/{school}` | `saas.library.schools.switch` | `library.manage` |
| GET | `/saas/library/reviews` | `saas.library.reviews` | `library.review` |
| POST | `/saas/library/versions/{version}/review` | `saas.library.versions.review` | `library.review` |

Every write uses `LibraryRequest` (its `authorize()` checks the ability) and a
service in `app/Services/Learning/Library` that checks it and validates again.

## Not in this step

- **Content itself** (3.5): no topics, questions or lessons are seeded.
- **AI drafting into the library** (3.4): `TeachingDrafts` is not pointed at the
  library, and there is no teacher panel.
- **`TutorContext::capture`** reading the library. The tutor's context manifest
  is hashed and re-authorised on every turn against school content versions;
  adding library sources there needs its own change and tests.
- **Apps and API**: no `/api/v2` or `openapi.yaml` change. The apps cannot yet
  tell a library question from a school one, show its source or licence, or
  start a lesson or diagnostic. Library entries already fit the existing daily
  set shape.
- **Lessons, diagnostics and past-paper questions for learners.** They can be
  written, reviewed and published, but only practice questions reach daily
  practice. The diagnostic start is 3.7; the past-paper library and licence
  attestation are 3.19.
- **Mastery map**: mirror topics are not shown on the school's mastery map, and
  library topics are not linked to the school's own curriculum topics.
- **SA.16, later parts**: quality signals from learner data (weak items),
  retiring a published item, and a school-side switch (only HQ switches it).
- **Teacher-to-teacher sharing** (5.4).
