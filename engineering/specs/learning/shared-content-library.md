# Shared content library (teacher to teacher)

Action Plan 5.4. Builds on the [platform content library](content-library.md)
(3.3), its [content production pipeline](content-pipeline.md) (3.4), teachers'
[question banks](question-banks.md) (3.6) and the
[shared records pattern](../platform/shared-records.md) (5.1). Owner roles:
Backend, Admin UX (school panel and HQ), Learning.

Teachers write good practice questions every day, and most schools will never
have enough of their own. This step lets a teacher offer one of their own
approved questions to the platform library, where Navigate Ed reviews it like
any library item. Once published, teachers at other schools browse it, copy it
into their own question banks and rate it, and it reaches daily practice
through the library's existing paths.

The library's hard rule still holds: **school data never flows into it by a
live link.** Sharing is a deliberate, scanned and reviewed copy of one
question's teaching content, and nothing else.

## User stories

- As a school leader, I switch sharing on for my school (it is off by
  default) and choose whether a leader approves each question before it
  leaves the school (on by default).
- As a teacher, I share one of my own approved questions: I give it a short
  title, choose its library topic, choose how other schools see who shared it,
  confirm I wrote it or may share it and accept the licence.
- As a leader or a content lead of the question's grade, I approve or decline
  (with a reason) a question waiting to leave the school.
- As the teacher who shared it, or a leader, I withdraw it at any time.
- As a Navigate Ed reviewer, shared questions arrive in my review queue as
  "Shared by a school". I approve or send back as for any library item, see
  teachers' ratings and comments, and can take a published item down.
- As a teacher at a school whose library switch is on, I browse published
  shared questions by curriculum, subject, grade and topic, copy one into my
  bank for one of my groups, and rate it once my school has used it.

## Rules

- **Who.**
  - Teachers (the Teacher role at the school) browse, read, copy, rate, share
    their own questions and withdraw their own contributions.
  - School leaders (`SchoolStages::mayManage`: a Super Admin or office staff
    who manage school settings) change the school's sharing settings.
  - Leaders and content leads of the question's grade (`ContentPolicy::leads`:
    academic managers, the grade head and the principal) preview exactly what
    a contribution shares, then approve, decline or withdraw it. Nobody
    approves their own. A content lead opens the page without the Teacher role
    or the settings permission, and sees only the contributions of the grades
    they lead.
  - Everyone else (learners, parents, other staff, platform staff inside a
    school, impersonation and support sessions) is refused (403). Every check
    runs inside the person's own school with daily practice on
    (`PracticeAccess::actor`), in `SharedLibraryAccess`.
- **What is shared.** Only the question's latest approved version's teaching
  content: the question, its options, accepted answers, worked steps,
  explanation and expected time (`PracticeAnswers::definition`), plus the
  title the teacher gives. Never a learner's answer, mark, name or memory, a
  class list, a group, a topic or the school's lesson material. The teacher's
  own question is never changed (it is only read, under a read lock).
  Questions with a picture (labelling questions) cannot be shared yet: a
  picture cannot be checked for identifying data.
- **Identifying data.** The title and every text of the question are scanned
  when the teacher shares it and again when it leaves the school
  (`SharedLibraryScan`): an e-mail address, a South African identity number or
  a phone number (the patterns of `IdentifyingData`, as evaluation imports use),
  and the first name or surname (three letters or more, a whole word) of any
  current learner on the school's roster. Anything found is refused with a
  validation error naming what was found; nothing is written. A rating's
  comment is scanned the same way against the rater's school.
- **Licence and rights.** The teacher confirms they wrote the question or may
  share it, and accepts the licence in `config/shared_library.php` (CC BY 4.0
  until the owner decides). The licence is recorded on the contribution and the
  library source.
- **Preview before approval.** A decider opens the contribution and sees the
  question version the teacher submitted (`practice_item_version_id`), never a
  later revision of the teacher's question: prompt, options, accepted answers,
  steps, explanation and expected time. Only a leader or a content lead of its
  grade may open it (403 otherwise); another school's is not found (404).
- **Leader approval.** `library_school_settings.sharing_enabled` (off by
  default) must be on to share. With `leader_approval` (on by default) a
  contribution waits as `awaiting_approval` until a leader or content lead
  approves it; otherwise it leaves at once. Declining needs a reason (10 to 500
  characters), which the teacher sees.
- **Into the library.** When it leaves the school the question becomes a
  library item of kind practice question under the chosen topic (one of the
  library topics of the question's framework, subject code and grade, from its
  group's published curriculum, as `LibraryPractice::scopes`), with its own
  source (kind `contributed`, naming the school, the teacher, the credit they
  chose, the licence and the date) and version 1 with origin `contributed`,
  submitted for review. The teacher is its author and editor, so the existing
  review rules apply unchanged: only a reviewer assigned to the subject approves
  it, and nothing is published, shown to another school or used by practice
  until Content publishes the approved version.
- **The one path in.** Library models still refuse every write while a school
  is the current school (`PlatformLibraryRow`). `ContributionGate` is the
  single exception: `SharedLibrary` opens it only while it creates the source,
  item and version of one contribution, or marks a contributed item withdrawn.
  Inside the gate only those three models may be saved; reviews, reviewers,
  topics, frameworks and every delete are still refused.
- **One live contribution.** A question has at most one contribution waiting
  or shared at a time, and a question version is shared at most once
  (`library_contribution_version`); sharing again is refused (409).
- **Attribution.** A published contributed item shows, as the teacher chose:
  "Shared by &lt;teacher&gt;, &lt;school&gt;" (`named`), "Shared by
  &lt;school&gt;" (`school`) or "Shared by a NavigateEd teacher" (`anonymous`).
  Names are read when shown, so a renamed school or person shows the current
  name. HQ always sees the teacher and the school, for licensing.
- **Withdrawing.** The teacher who shared it, a leader or a content lead of its
  grade withdraws it. A contribution still waiting just ends. One that reached
  the library marks the library item withdrawn: it is unpublished, leaves the
  review queue and cannot be changed, reviewed or published again (409). Its
  versions are never changed, so daily practice sets and mastery evidence that
  used a version keep pointing at it; a set already issued stops offering it, as
  when the library switch goes off. Copies schools made stay theirs.
- **Browsing.** Only at a school whose library switch is on (HQ sets it). Only
  published items with a contributed source that are not withdrawn are
  offered: never a draft, a version waiting for review, an unpublished item or
  Navigate Ed's own content (whose licences may not allow copying). Filters:
  framework, subject code, grade, topic; 25 a page, newest first.
- **Copying.** The teacher approves the shared question for one of their
  groups with a topic and lesson material of that group, through
  `PracticeBank::approve` and all its checks, exactly as for any question. The
  copy is the school's own question (author: the teacher; sharing `groups`),
  remembers the library version it came from
  (`practice_items.library_item_version_id`) and never changes when the library
  item does. The form carries an operation id, so sending it again returns the
  first copy.
- **Rating.** A teacher rates an item (1 to 5, with an optional comment of up
  to 500 characters) once their school has used it: it holds a copy, or its
  learners met it in daily practice. The sharing school's teachers may rate
  their own school's item too. Once per item per teacher
  (`library_rating_once`); saving again changes it.
- **What ratings show.** `LibraryRatings` is the only code that reads ratings
  across schools, as numbers and unnamed comments: never who rated, from which
  school or when. Ratings from the school that shared the item never count
  towards it. Teachers see an average (one decimal) and a count only once 3
  ratings count (`shared_library.rating_threshold`), and the counted comments
  only then. Reviewers at HQ see the counted average and count, how many came
  from the sharing school, and every comment, without names.
- **Unpublishing.** A reviewer assigned to the item's subject can take any
  published library item down with a reason (`library_unpublished`, no
  school). It is no longer offered; its versions stay. Content may publish a
  later version after review, never one older than a version published before.
- **Feeds practice and the tutor's path.** A published contributed item is an
  ordinary published library version. Daily practice's library fallback
  (`LibraryPractice::candidates`), quick quizzes and the guided learning path
  (`LearningPathSources::candidates`, which hands its teach step to the tutor)
  offer it with no special case, and mastery evidence records the library
  version as for any library question. `TutorContext::capture` itself still
  reads only the school's own material (see "Not in this step").
- **Audit** (in the school): `library.sharing_settings_changed`,
  `library.contribution_offered`, `library.contribution_approved`,
  `library.contribution_declined`, `library.contribution_shared`,
  `library.contribution_withdrawn`, `library.rating_saved` (stars and whether
  there is a comment, never its text) and `library.item_copied`. At HQ:
  `library_unpublished`. Ids, states and choices only, never question content.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `library_contributions` | required school-owned | the question and version offered, library topic, title, credit, licence, who shared it and when they confirmed their rights, state (`awaiting_approval`, `shared`, `declined`, `withdrawn`), the leader's decision and reason, the library item it became, who withdrew it |
| `library_ratings` | required school-owned | library item, teacher, stars, comment; unique per item and teacher |

Columns added (migration `2026_11_12_540000_create_shared_content_library`):
`library_school_settings.sharing_enabled` (default off), `leader_approval`
(default on), `sharing_changed_by`, `sharing_changed_at`;
`library_sources.contributor_school_id`, `contributor_user_id`, `credit`,
`contributed_at`; `library_items.withdrawn_at`, `unpublished_at`,
`unpublished_by`, `unpublish_reason`; `practice_items.library_item_version_id`.
The migration's `down` refuses while contributions, ratings, contributed
sources, copies or withdrawn or unpublished items exist.
`library_contributions.practice_item_id` and `practice_item_version_id` are
indexed but have no foreign key into the practice tables, so the older practice
migrations can still be rolled back and re-applied: approved practice versions
are never deleted (the model refuses it) and `SharedLibrary` reads both only in
the school's own scope, pairing the version with its question (404 if
missing).

## Panel (school)

`/shared-library` (Academics, "Shared library"; daily practice module):
the sharing settings (leaders), the school's contributions with approve,
decline and withdraw (leaders and content leads), the teacher's own
contributions and where each stands, and the browse list with filters.
`/shared-library/items/{item}` shows the question, its attribution and licence,
ratings and the teacher's own rating, and the copy form (choose a group, then
its topic and material). `/shared-library/share/{item}` is the share form,
linked from each of the teacher's own active questions in the question banks.

| Method | Path | Name |
| --- | --- | --- |
| GET | `/shared-library` | `shared-library.index` |
| GET | `/shared-library/items/{item}` | `shared-library.items.show` |
| POST | `/shared-library/items/{item}/rating` | `shared-library.items.rate` |
| POST | `/shared-library/items/{item}/copy` | `shared-library.items.copy` |
| GET | `/shared-library/share/{item}` | `shared-library.share` |
| GET | `/shared-library/contributions/{contribution}` | `shared-library.contributions.show` |
| POST | `/shared-library/contributions` | `shared-library.contributions.store` |
| POST | `/shared-library/contributions/{contribution}/approve` | `shared-library.contributions.approve` |
| POST | `/shared-library/contributions/{contribution}/decline` | `shared-library.contributions.decline` |
| POST | `/shared-library/contributions/{contribution}/withdraw` | `shared-library.contributions.withdraw` |
| PUT | `/shared-library/settings` | `shared-library.settings` |

## API v2 (teachers' apps)

Under `/api/v2/learning/shared-library`, for staff (`staff` role group; the
service then requires the Teacher role), daily practice module, private
no-store responses. Described in `openapi.yaml` (tag `SharedLibrary`).

| Method | Path | Operation |
| --- | --- | --- |
| GET | `/items` | `listSharedLibraryItems` |
| GET | `/items/{item}` | `getSharedLibraryItem` |
| PUT | `/items/{item}/rating` | `rateSharedLibraryItem` |
| POST | `/items/{item}/copy` | `copySharedLibraryItem` |
| GET | `/contributions` | `listSharedLibraryContributions` |
| POST | `/contributions` | `contributeToSharedLibrary` |
| POST | `/contributions/{contribution}/withdraw` | `withdrawSharedLibraryContribution` |
| GET | `/practice-items/{item}/share-options` | `getSharedLibraryShareOptions` |

Leaders' approvals and the sharing settings are in the panel only.

## HQ

- The review queue gains the filter "Shared by schools" and labels each shared
  version with the teacher, school, date and credit chosen; withdrawn items
  leave the queue. The item page shows the same, the withdrawn or unpublished
  state, and the ratings.
- `GET /saas/library/ratings` (`saas.library.ratings`, `library.review`): rated
  shared items in the reviewer's subjects, with counts, averages and comments,
  never names.
- `POST /saas/library/items/{item}/unpublish` (`saas.library.items.unpublish`,
  `library.review`, assigned to the subject): take a published item down.
- Contributed sources are left out of HQ's source lists and pickers.

## Acceptance tests (`Learning/SharedContentLibraryTest`)

Every v2 response in it is checked against `openapi.yaml`.

| Given | When | Then |
| --- | --- | --- |
| Sharing on, leader approval on | Tara shares; tries to approve her own; the Super Admin approves; School B browses; the reviewer filters the queue; it is reviewed and published; School B browses with filters | waiting, nothing in the library; 403; version 1 in review, origin `contributed`, content only the definition, source names School A, Tara, `named`, CC BY 4.0; nothing found by B, not even by id (404); "Shared by a school" with Tara's name in the queue; then found, "Shared by Tara Demo, Jacaranda Demo School", no rating yet; Tara's question unchanged throughout (`test_a_shared_question_waits_for_a_leader_and_a_reviewer_before_another_school_sees_it`) |
| Sharing off | Tara shares; Tara changes settings; Theo shares Tara's question; B's teacher shares it; without licence or rights; with an English topic; without leader approval; twice; Theo and B withdraw; Tara withdraws | 403; 403; 403; 404; validation errors, nothing written; shared at once; 409; 403 and 404, still shared; withdrawn and the library item withdrawn (`test_sharing_is_off_until_…`) |
| Questions with an e-mail, an identity number, a phone number, a learner's first name; a title with a learner's surname; B comments naming a B learner or a phone number | share; rate | each refused (validation), nothing written (`test_identifying_data_is_refused_in_a_shared_question_and_a_rating_comment`) |
| A learner, a parent, non-teaching office staff; Support, Finance and Content at HQ; platform staff inside the school; learner and parent tokens on the API | browse, read, rate, copy, share, withdraw, settings; the ratings page and unpublish; rate | 403 everywhere, nothing written (`test_wrong_roles_are_refused_and_nothing_is_written`) |
| A waiting and a published contribution of A | B lists, approves, withdraws, opens share options; rates before using it; B copies and rates over the API; A reads | nothing of A's, 404s; 403; B's rating saved, no average shown, no contributor ids; A sees none of B's rating rows and no B name (`test_school_b_never_sees_school_a_contributions_or_who_rated_and_cannot_act_for_a`) |
| A published item | Tara and Theo (A) rate 5 and 5; two B teachers rate; a third rates; one changes; the reviewer opens ratings and unpublishes | A's not counted; nothing shown at two; 3.0 from 3 with the counted comments only; 4.0 from 3, one row per teacher; the page shows counts and comments without names; reason required; unpublished, version unchanged, B 404, audited with no school (`test_an_average_shows_only_from_three_counted_ratings_and_the_sharing_schools_ratings_never_count`) |
| A published item and B's group | Theo (not allocated) copies; Tara copies over the API twice; A withdraws; Tara copies again | 403; one copy in B, author Tara, linked to the library version, replay returns it; B's copy unchanged; 404 (`test_a_copy_is_the_schools_own_question_approved_like_any_other`) |
| A published item, A's library on | issue daily practice and answer; ask the learning path; withdraw; read the set; HQ publishes or redrafts | offered as a library version, evidence names it; offered under the same-named topic; unpublished, version unchanged, set 404, evidence still points at it, not offered on the path; 409 and 409 (`test_published_contributions_reach_daily_practice_and_learning_paths_and_withdrawing_keeps_the_evidence`) |
| The gate | update a library item from a school; write a review or delete inside the gate | 403, nothing changed; the new tables are school-owned, library tables still have no `school_id` (`test_the_contribution_gate_lets_only_sources_items_and_versions_through_and_the_tables_are_school_owned`) |
| A grade 7 head and a grade 8 head, office staff without the Teacher role or settings permission; Tara changes her question after sharing | each opens the page; the grade 7 head previews and approves; the grade 8 head previews or approves; Tara previews; School B's teacher previews | both reach the page, only the grade 7 head sees it; the preview and the library copy show the submitted version, not the change; 403; 403; 404 (`test_a_content_lead_without_the_teacher_role_or_settings_permission_previews_and_decides_only_their_grade`) |
| The panel and API | a leader saves settings; a teacher opens banks and the share form and shares; the leader approves twice; B browses, opens, copies and rates in the panel; the API browses, lists and withdraws | each page shows what it should; the second approval is refused with a message; B sees "Shared by Jacaranda Demo School"; withdrawn over the API, then 404 for B (`test_the_panel_pages_and_api_let_a_school_share_and_another_browse`) |

`SaasPanelTest` counts the 2 new `/saas` routes (177 in all).
`OpenApiContractTest` covers each new operation without a token, and a learner
and a parent refused.

## Owner decisions

- **Licence** granted by a sharing teacher: CC BY 4.0 proposed
  (`config/shared_library.php`).
- **Leader approval**: a school setting, on by default; sharing itself is off
  by default until a leader switches it on.
- **Attribution default**: the form preselects "My school only"; the teacher
  may choose their name and school, or no one in particular.
- **Rating threshold**: 3 counted ratings before an average, count or comment
  is shown to teachers (`shared_library.rating_threshold`).
- **Who may contribute**: only the author of an active, approved practice
  question who holds the Teacher role at the school; approval by a leader or a
  content lead of its grade.
- **"Used" for rating**: the school holds a copy or its learners met the item
  in daily practice (the sharing school may rate but is never counted).

## Not in this step

- **App screens** and the regenerated Dart client
  (`packages/eschool_api/tool/generate.sh`).
- **Lessons and other school material.** A school's lessons are files and
  documents that cannot be reliably scanned for learners' data; only practice
  questions can be shared. Labelling questions (a picture) are left out for the
  same reason.
- **The tutor reading the library.** `TutorContext::capture` still reads only
  the school's own published material (left out in 3.3: its manifest is
  hashed and re-authorised against school content versions every turn).
  Shared items reach the tutor's learning path, daily practice and quizzes.
- **Notifications** to the teacher when a leader or Navigate Ed decides, and a
  reviewer's reason shown to the teacher when Navigate Ed sends it back.
- **Flagging a rating comment** as abusive, and editing a comment at HQ.
- **Updating a shared question** from a new version of the teacher's own: the
  teacher withdraws and shares the new version.
