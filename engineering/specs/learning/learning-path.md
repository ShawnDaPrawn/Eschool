# Guided learning path

Action Plan 3.9. Owner role: Backend, Learning. Builds on the mastery map
(`engineering/specs/growth/mastery.md`), daily practice
(`daily-practice.md`), the tutor and its verified checkpoints, and the content
library (`content-library.md`).

A learner who opens a subject is not left to choose what to do. The server
finds the gap, teaches it (the tutor leads when it may, otherwise the
published lesson), gives practice, checks understanding with questions it
grades itself, and schedules a review for later. Then it finds the next gap.

## User stories

- As a learner, I open my learning path for a subject and see the one topic
  to work on now and why (a gap in a prerequisite, a weak topic, or not yet
  tried), with the lesson to learn it from.
- As a learner whose school has the tutor on (and the AI switched on), the
  tutor opens on that topic and leads the lesson instead of waiting for me.
- As a learner, I practise a few questions, then take a short check. I see
  whether each answer was right and the worked explanation only after I
  answer; the expected answer is never sent to my device.
- As a learner who does not pass the check yet, I am told kindly and go back
  to the lesson and practice. After three tries in a row the topic rests until
  tomorrow and I am pointed to my teacher.
- As a learner who passes, I am told when the topic will come back for a
  short review. On that day my path starts with the review check.
- As a teacher of the group, I see which step each of my learners is on and
  who may need support, without scores or rankings.
- As a guardian, I see a plain summary for my own child: the subject, the
  step in plain words, the topic and the next review date.

## Steps (server-side state machine)

`learning_paths.step` is one of `gap`, `teach`, `practise`, `check`,
`review`. Only `LearningPathService` changes it; the app sends an action and
the revision it last saw.

| From | Action | To | Rule |
| --- | --- | --- | --- |
| no path, `gap`, `review` (or a stalled step) | `next` | `check` (review mode) | a topic's review is due today or earlier: oldest due first |
| same | `next` | `teach` | otherwise the first gap found by `MasteryService::roots` |
| same | `next` | `gap` | no gap: `caught_up`, `rest` (only the resting topic is left) or `nothing_ready` (no approved practice in the group) |
| `teach` | `open_tutor` | `teach` | opens a tutor session on the topic when the tutor is available (below) |
| `teach` | `practise` | `practise` | issues up to 5 practice questions (a new cycle) |
| `practise` | `check` | `check` | every practice question answered; issues 3 check questions |
| `check` | last answer | `review` | at least two thirds right (`ceil(2n/3)`, at least 1): FSRS schedules the topic's review |
| `check` | last answer | `teach` | fewer right, fewer than 3 failed checks: notice `not_yet` |
| `check` | last answer | `gap` | the third failed check in a row: notice `rest`; the topic rests until tomorrow |

A stalled step lets the learner choose again (`next`) instead of being stuck:
the topic is no longer taught in the group; in teach, the topic has no
question left that could be given (all deactivated, their material archived
or unreadable, or the library switched off); in practise or check, every
question of the step was withdrawn, or in a check every remaining question is
answered. The read
then shows `step: gap` with notice `withdrawn`.

**Finding the gap.** The candidates are the group's current topics
(published curriculum topics of its grade in its current assessment plans,
as for the tutor) that have at least one approved question (the school's
own, or a published library question under a same-named library topic),
minus topics with a scheduled review that is not yet due and the topic
resting until tomorrow. `MasteryService::view` gives each its level and
confidence from the learner's own family-or-practice record (never staff-only
marks), and `MasteryService::roots` walks the prerequisite links to the
deepest weak topic. The first root with at least one question that can be given now (approved,
active, material readable, or library version published) is the target;
roots without one are set aside and the gaps found again. A due review whose
topic has no question to give is skipped the same way. The target's `reason` is
`prerequisite_gap`, `topic_gap` or `not_yet_assessed`. Prerequisites the
group does not teach (an earlier grade) are outside the candidates in this
step: the path works within what the learner's group teaches now.

**Teach.** `teach.lessons` lists at most three: first the school's own
published material behind its approved questions on the topic that the
learner may read (`content_version_id`, opened with the existing academic
content reader), then published library lessons (`kind: lesson`) under a
same-named library topic of the group's framework, subject code and grade
while the school's library switch is on (`body` is the lesson's Markdown).
`teach.tutor` says whether the tutor can lead:

- `not_ready`: the AI is not switched on (`ai.inference.enabled` and the tutor
  feature). This is today's state until a provider is qualified (3.1); the
  path works fully without the tutor.
- `not_available`: the tutor module or the learner's stage does not allow it,
  or the learner may not use the tutor in this group (`TutorAccess`).
- `no_school_material`: the tutor needs the school's own material as its
  source; library lessons are not given to the tutor in this step.

`open_tutor` creates the session through `TutorSessions::create` with that
material and the topic, so every tutor rule (approval, enrolment, caps, the
Mastery tutor's own per-learner AI cap, moderation, quarantine) applies
unchanged. No billing or entitlement is added here.

**The tutor leads.** `TutorContext::capture` adds `learning_path`
(`step`, `lead` true while teaching, `attempt`) to the approved context of a
session the path opened, and the manifest records the path id and step; a
later reauthorization checks the path is still this learner's in this group,
not that the step is unchanged, so earlier turns stay readable. Both tutor
prompts carry one instruction for it (v1 `tutor` 1.2.0, and v2 `tutor-v2`
2.1.0, the default): when `lead` is true, teach the topic in small steps (one
idea, one short worked example, one question) instead of waiting; otherwise
support the step with hints only, and never give the path's question answers.
No safety rule changed. `LearningPathTest` sends a real v2 turn to a local
fake provider and checks the path context and the instruction both reach the
model; the `learning_path_tutor_leads` case in `resources/ai/evals/tutor.jsonl`
is scored against the configured prompt (v2).

**Practice and check questions.** From `LearningPathSources::candidates`: the
school's approved questions on the topic in the group (active, latest
version, material readable by the learner), then published library practice
questions under a same-named library topic; each list due-for-review first
(the question's own FSRS memory). Practice takes the first five. The check
prefers questions not practised in this cycle and not used in an earlier check
on the topic, then ones not practised in this cycle, then any.

**Grading.** Every answer is graded on the server with `PracticeAnswers`
against the immutable approved version. The first answer is final
(`learning_path_items` refuses changes and deletes). The response gives each
question's kind, prompt and options only; `correct` and the worked
explanation appear only once it is answered; the approved answers never leave
the server. Each answer moves that question's FSRS memory exactly as daily
practice does (right is "good", wrong is "again"), so daily sets see it, and
writes self-study mastery evidence on the path's topic (`source_type`
`path_practice` or `path_check`, audience `practice`, `content_source` school or
library, weight 0.5 in mastery v2 like other practice). No official mark is
written.

**Resting.** A topic rests until tomorrow after its third failed check in a
row. Passing a different topic in the meantime does not end that rest; only
passing the resting topic itself, or the rest expiring, clears it, so the
teacher's view keeps showing the resting topic.

**Review later.** Passing a check reviews the topic's own FSRS memory
(`learning_path_reviews`, one row per path and topic): all right is "good",
some wrong is "hard". The review date is the FSRS interval from today. A failed
review check is a lapse ("again") and the topic goes back to teach.

**Idempotency.** Every write carries an `operation_id`; the receipt (the path
id only) is kept in `practice_operations` and every replay re-reads the path
with fresh authorization. A step action also carries `expected_revision`; a
second tap with a new operation id after the path moved is refused (409), so
a double submit advances once. An answer to an already answered question with
a new operation id is refused (409).

## Who may do what

| Who | Read | Act |
| --- | --- | --- |
| The learner (own account, approved by a guardian, enrolled in the group) | own path | `next`, `open_tutor`, `practise`, `check`, answer |
| A teacher (or academic leader) who may read the group's marks (`MasteryPolicy::staff`) | the group's steps (`not_started` for learners without a path), topic, resting topic, `needs_support`, next review | none |
| A guardian of the child (`MasteryPolicy::family`, Parent role, child approved) | plain summary per subject this school year | none |
| Anyone else, another learner, another child's guardian, another school | 403 or 404 | none |

The learner endpoints use `PracticeAccess::learner` (own approved learner,
current group and year) and require the `daily_practice` module (writable for
writes) and `mastery` (writable for writes), with the learner's stage allowing
daily practice (4.1). The teacher and family reads require both modules on.
The tutor is optional and checked separately. Teacher and family views carry
no levels, confidence, correctness or ordering by result.

## API

| Method and path | Who |
| --- | --- |
| `GET /api/v2/learning/paths/learners/{learner}/groups/{group}` | learner |
| `POST /api/v2/learning/paths/learners/{learner}/groups/{group}/actions` (`operation_id`, `expected_revision`, `action`) | learner |
| `POST /api/v2/learning/paths/learners/{learner}/groups/{group}/items/{item}/answer` (`operation_id`, `answer`) | learner |
| `GET /api/v2/learning/paths/groups/{group}/learners` | teacher |
| `GET /api/v2/learning/paths/learners/{learner}/summary` | guardian |

Described in `openapi.yaml` (tag `LearningPath`). Writes accept body fields
only. Responses are `private, no-store`. The Dart client
(`packages/eschool_api`) must be regenerated.

## Data

| Table | Tenancy | Holds |
| --- | --- | --- |
| `learning_paths` | required school-owned | learner, account, group, subject, year, topic, step, mode (`learn` or `review`), reason, notice, cycle, failed checks, resting topic and date, tutor session, revision; one per learner and group |
| `learning_path_items` | required school-owned | path, topic, cycle, purpose (`practice` or `check`), position, the school question version or library version, the learner's answer (encrypted), correct, FSRS schedule, answered time |
| `learning_path_reviews` | required school-owned | path, topic, FSRS state, last review day, due day |

The migration's `down` refuses while any row exists. Paths, items and reviews
cannot be deleted through the models.

## Choices made

- One path per learner and learning group (a group is one subject in one
  year). A learner moved to another group of the same subject starts a new
  path there.
- The checkpoint is the path's own server-graded check, not
  `TutorCheckpoint`, because the path must work with the tutor module off.
  Both grade with `PracticeAnswers`.
- A school topic has no explicit link to a library topic yet, so library
  lessons and questions are matched by the same name within the group's
  framework, subject code and grade.
- Library lessons are shown to the learner but not given to the tutor as
  source material.

## Not in this step

- App screens (learner path, teacher step list, guardian summary).
- Diagnostic start (3.7) seeding the map; new question types (3.11). The path
  calls `PracticeAnswers::correct` and shows `kind`, `prompt` and `options`, so
  new kinds can slot in.
- Prerequisite gaps from earlier grades, a teacher assigning a target topic,
  notifications (`mastery.gap_closed`, 3.13) and Mastery billing (3.20).
