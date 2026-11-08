# Practice tests: test assembly and timed mode

Action Plan 3.17. Daily practice gives a learner ten minutes of self-study with the answers on the device, which is right for practice but not for a test. A practice test is assembled from the same approved questions, is timed by the server and keeps every answer and worked explanation on the server until the learner hands it in. Results become mastery evidence labelled `practice_test`, and the result hands the learner on to the next things to practise and to a tutor review.

Two kinds exist now. A **quick quiz** is a learner's own short check on one to three topics of a group. A **cycle test** is set by the group's teacher for the whole group, with a time limit and a window. A **mock exam** is the next kind (built from structured memos, Action Plan 3.15, and the subject marking engine, Action Plan 3.16); the kind is named in `TestKinds` and refused (422) until it is built. As decided for marking, learners sit mock exams on their own and teacher-set tests stay separate.

## User stories

- As a teacher, I set a cycle test for one of my groups: a title, the topics, how many questions, the time limit, when it opens and closes, and whether the questions are shared evenly between the topics or like chosen past papers.
- As a teacher, I see how each learner in the group is getting on (not started, sitting it, time up, handed in) and how many they got right, listed by name, and how the group did per topic. I can withdraw a test so nobody new starts it.
- As a learner, I start a quick quiz on a topic I choose, or a cycle test my teacher set, and see the time I have left. My answers are saved on the server as I go.
- As a learner, when I hand in I see how I did per topic and what to look at next. For a quick quiz I see the right answers and explanations at once; for a cycle test, once it closes, so classmates still sitting it are not given the memo.
- As a learner, what I got right and wrong counts towards my mastery map as practice, and I can take the topic I found hardest to the tutor.

## Rules

- **Who.** Only the learner themself (an approved Student in the group today) starts, reads, answers and hands in a sitting. A guardian (even an approving one), another learner, a teacher or another school gets 404 (403 for an unapproved learner, as daily practice). Only a teacher allocated to the group today (`PracticeAccess::staff`) sets, lists, reads the results of or withdraws its cycle tests; other roles get 403. Every query is school-scoped (`BelongsToSchool`); another school's group, test or sitting is not found and nothing changes in either school.
- **Module.** Practice tests are part of daily practice and use its module (`daily_practice`, off by default). Writes need it writable.
- **Where questions come from.** The school's own approved questions used in the group (question banks, Action Plan 3.6), with their current version, whose source material is published to the group (for the teacher's counts) or readable by the learner (for a sitting). Where the school's library switch is on, published library practice questions for the group's framework topics (Action Plan 3.3) follow, under the school's mirror topic nodes. Only kinds a marker handles today are used (`TestMarkers::ASSEMBLED`: `choice` and `number`).
- **Topic shares.** `even` gives each chosen topic the same weight. `past_papers` uses the exam coach topic shares of up to five chosen published past papers (each question's marks split across its topics, exactly as `ExamStudyPlans` weighs them); topics the papers do not cover get no questions. The question count is split by largest remainder (ties to the lower topic id), and what a topic cannot hold goes to the others in proportion. A cycle test whose topics cannot fill its question count is refused with the number that is ready (422, on `question_count`). The blueprint stores the shares it was set with.
- **Bounds.** Quick quiz: 1 to 3 topics, 3 to 10 questions, timed by the questions' expected seconds (at least two minutes); one open quick quiz per group at a time (409), at most 20 a school day (422). Cycle test: 1 to 20 topics, 5 to 40 questions, 5 to 180 minutes, open at most 31 days, closing in the future. Times in the panel are entered in the school's time zone and stored in UTC.
- **A sitting.** Starting chooses each topic's questions and their order, stable per learner and test (a hash of the blueprint, learner and question), so classmates usually get different questions and order. One sitting per learner per cycle test: starting again (same or new operation) resumes it. A cycle test starts only inside its window and while not withdrawn. The sitting keeps the immutable question versions it was assembled with, like a printed paper.
- **The deadline is the server's.** `deadline_at` is the start plus the time limit, never later than a cycle test's closing time. The device gets `server_time`, `deadline_at` and `seconds_left` to show a timer but is never trusted for time. Saves are accepted until the deadline plus a 30-second network grace (`PracticeTests::GRACE_SECONDS`) and refused after it (409). A hand-in after that is accepted but marks only the answers saved in time; answers sent with it are ignored and the sitting records `closed_by: time`. A sitting nobody hands in stays `time_up` and is not marked.
- **No answers before hand-in.** Before hand-in a question carries only its id, position, topic, kind, prompt, options and the learner's own saved answer. Accepted answers, worked explanations and right or wrong never leave the server. After hand-in each question shows whether it was right; the accepted answers and explanation follow at once for a quick quiz, and for a cycle test once it closes or is withdrawn.
- **Hand-in is idempotent.** Hand-in runs through the practice operation receipts (`PracticeOperations`): the same operation returns the same result, the same operation with different content is refused (409), and a second hand-in with a new operation is refused (409). Marks and evidence are written once.
- **Marking.** `TestMarkers` gives each question kind its `TestAnswerMarker`. Choice and number use the existing automatic marking (`PracticeAnswers`). Any other kind is kept, bounded, and waits for a marker (`marking: awaiting_marker`), counting neither in the score nor as evidence. This is the seam for typed working and photographed answers: when the question types (Action Plan 3.11) and the subject marking engine (Action Plan 3.16) land, register their marker here and add the kind to `ASSEMBLED`.
- **Mastery.** Each automatically marked answer becomes one piece of evidence in the `practice` audience (`MasteryRecompute::practiceTest`), `source_type` `practice_test`, with the school or library source and item key as daily practice records them. Unanswered questions are shown as not answered, not as wrong, and are not evidence. Mastery v2 weighs a practice test answer at 0.75 (marks 1.0, daily practice and tutor checkpoints 0.5): it is timed and its answers never reach the device, but it is still practice, not a teacher's mark. Nothing writes official marks.
- **The review hand-over.** The result lists the topics to look at next, the one with the smallest share right first (only topics not fully right). Mastery evidence already feeds the exam study plan weighting and daily practice's weakest-topic ordering. When the school has the tutor switched on and the first topic to review is one of the school's own, the result carries `review.tutor`: the group, the topic and up to three published source versions behind the questions there (missed ones first), exactly the inputs `POST /tutor/sessions` takes. Library topics get no tutor hand-over yet.
- **Learner-facing text.** Plain and kind; no ranking, class averages or comparisons. The teacher's results are in name order.
- **Audit.** `learning.practice_test_set` and `learning.practice_test_withdrawn`.

## Data

- `practice_test_blueprints` (school-owned): group, subject, grade, year, `kind`, `created_by`, `learner_id` (a quick quiz's owner), `title`, `topic_ids`, `weighting`, `paper_version_ids`, `topic_shares` (topic, weight, questions), `question_count`, `time_limit_seconds`, `opens_at`, `closes_at`, `active`, `revision`.
- `practice_tests` (school-owned): blueprint, learner, user, group, `time_limit_seconds`, `started_at`, `deadline_at`, `submitted_at`, `closed_by` (`learner` or `time`), `correct_count`, `marked_count`; one per blueprint and learner.
- `practice_test_questions` (school-owned): sitting, position, practice item version or library item version, topic, encrypted `answer`, `answered_at`, `marking` (`auto`, `awaiting_marker` or empty when unanswered), `correct`.
- Rolling the migration back is refused while any of the three tables holds rows.

## API (`/api/v2/learning/practice-tests`, in `openapi.yaml`, tag Practice)

| Operation | Who |
| --- | --- |
| `GET groups/{group}/options` | teacher: topics with ready questions, published past papers, bounds |
| `GET groups/{group}/blueprints` | teacher: the group's cycle tests with started and handed-in counts |
| `POST groups/{group}/blueprints` | teacher: set a cycle test |
| `GET groups/{group}/blueprints/{blueprint}/results` | teacher: learners in name order, per-topic summary |
| `POST groups/{group}/blueprints/{blueprint}/withdraw` | teacher |
| `GET learners/{learner}/tests` | learner: open and coming cycle tests, own quick quizzes, own sittings |
| `GET learners/{learner}/groups/{group}/quiz-topics` | learner: topics with questions ready for them |
| `POST learners/{learner}/groups/{group}/quick-quizzes` | learner: start a quick quiz |
| `POST learners/{learner}/blueprints/{blueprint}/start` | learner: start or resume a cycle test |
| `GET learners/{learner}/tests/{test}` | learner: the sitting, and the result once handed in |
| `POST learners/{learner}/tests/{test}/answers` | learner: save answers |
| `POST learners/{learner}/tests/{test}/submit` | learner: hand in |

Writes accept body fields only and carry an operation UUID (saves excepted: the last save wins). Responses are `private, no-store`. A choice the teacher or learner can fix is a field validation error (422), shown in the app and the panel.

## Panel

`/practice-tests` (Academics, "Practice tests"; daily practice module; teachers and academic managers, as question banks). Choose one of your groups taught today; see its cycle tests (window, questions and time, handed in), set one, open its results (learners by name with where they are and right answers, the group per topic) and withdraw it. Writes go through `PracticeTestPanelRequest` and `TestBlueprints`, which check the teacher, the group and the test again.

## Acceptance tests

`Learning/PracticeTestsTest` (API responses checked against `openapi.yaml`), `Learning/LibraryPracticeTest::test_a_quick_quiz_draws_on_the_library_and_its_evidence_says_so`, `Unit/Learning/TestTopicSharesTest`.

| Test | Given | When | Then |
| --- | --- | --- | --- |
| `test_a_teacher_sets_a_cycle_test_and_a_learner_sits_it_with_no_answers_on_the_device_until_it_closes` | four fraction and three decimal questions | Tara sets a 6-question test; Asha starts, resumes, saves, hands in; the test closes | 3 and 3 shares; no answers, explanations or right/wrong before hand-in; resume returns the same sitting; 5 of 6 right, memo hidden until close; 6 practice_test evidence rows, no official marks; Tara sees Asha handed in |
| `test_a_quick_quiz_hand_in_is_idempotent_shows_the_memo_and_hands_a_review_to_the_tutor` | the tutor is on | Asha checks her quiz topics, starts a quiz, tries a second, hands in twice, then differently | second quiz refused while open; memo at once; tutor hand-over with the group, topic and source; the same result twice; changed or new hand-ins refused; evidence once |
| `test_the_server_deadline_refuses_late_answers_and_a_late_hand_in_counts_only_answers_saved_in_time` | a 6-minute quiz | saves inside and after the grace, a late hand-in with every answer | inside the grace saved, after it refused; time_up with no result; closed by time, 2 marked, 1 unanswered, 2 evidence rows; a cycle test's deadline stops at its closing time |
| `test_wrong_role_another_learner_and_a_guardian_are_refused_and_nothing_is_written` | Asha's open sitting | a learner sets or withdraws a test; a teacher sits it; her guardian, another learner, an unapproved Asha | 403 or 404; counts, answers and evidence unchanged |
| `test_another_school_never_reads_or_writes_this_schools_tests` | School A's test and sitting, practice on in School B | School B's teacher and learner use School A's ids | not found; School B has no tests; School A unchanged |
| `test_past_paper_topic_shares_weight_a_cycle_test_and_bounds_are_kind_and_firm` | a published paper with three times the marks on fractions | Tara sets a 5-question test by past papers; asks for too many; withdraws | 4 and 1; "Only 7 questions are ready"; other bounds refused; mock exams refused; stale withdraw refused; a withdrawn test does not start |
| `test_the_panel_page_sets_lists_shows_and_withdraws_a_cycle_test_for_the_teachers_group_only` | Tara's group | she opens the page, sets a test (twice, same form), opens results, withdraws | one test, school time zone; results list Asha; other teacher 403, guardian redirected, School B 404, unknown group 404 |

## Not in this step

- App screens: the learner's timed test screen (timer from `seconds_left`, autosave, hand-in, result and review), the teacher app's cycle test screens, and the parent view. The Dart client is not regenerated here.
- Typed and photographed answers and their marking (Action Plan 3.11 question types, Action Plan 3.16 marking engine): the `TestAnswerMarker` seam is ready; no kind other than choice and number is assembled yet.
- Mock exams (a later blueprint kind, from structured memos and the marking engine).
- Closing sittings nobody hands in automatically (a scheduled sweep); they stay `time_up` and unmarked until handed in.
- Updating daily practice review memory (FSRS) from test answers.
- A tutor hand-over for library topics, and opening the tutor session for the learner automatically.
- A separate module switch for tests.
