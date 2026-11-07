# Practice question types and the offline answer loophole (Action Plan 3.11)

Daily practice ([daily practice](daily-practice.md)) had two question kinds,
`choice` and `number`, and every downloaded set carried each question's
accepted answers and worked explanation so the app could mark offline. A
learner could read the answers before answering. This step adds four kinds
and closes that loophole on every path.

## The loophole, and how it is closed

`DailyPractice::read` returned each entry's full approved `definition`
(answers and explanation included) to the learner's device, for school
questions and platform library questions alike, through the learner API
(`getPracticeDailySet`, `issuePracticeDailySet`) and the shared classroom
tablet (`foundation-learner/practice`). Tutor checkpoints already returned only
`kind`, `prompt` and `options` before an answer.

For a client on the answer-free contract (below), every set entry carries `question` (`PracticeAnswers::question`): the
prompt, options, step prompts, diagram size, regions and labels, the recording
limit. It never carries accepted answers, step answers, the label mapping or
the explanation. They arrive in `review.solution`
(`PracticeAnswers::solution`), and only once the server has recorded the
learner's answer: in the review response and in later reads of the set. An
answer is marked on the server when it syncs; the device no longer marks.
The same holds for school and library questions (one code path), for the
classroom tablet (its web client now reads `question` and `review.solution`)
and for tutor checkpoints (unchanged shape; they now take only `choice` and
`number` questions, which can be marked from one answer).

A learner who answers a question and later meets it again knows the answer:
that is practice working as intended, not a leak.

## The contract: opt in, with the legacy shape kept for installed apps

The installed apps decode `items[].definition` (`eschool_api`
`PracticeSetEntry`) and mark locally, so the answer-free shape is opt-in
(`PracticeContract`):

- A client that sends `X-Practice-Contract: 2` on the practice endpoints
  (`issuePracticeDailySet`, `getPracticeDailySet`, `reviewPracticeEntry`,
  `getPracticeBank` and the classroom tablet's `foundation-learner/practice`
  routes) reads the answer-free contract described above, with every question
  kind. The classroom tablet's web client sends it.
- A client that sends no header (or `1`) reads the legacy contract exactly as
  before 3.11: `definition` with its answers and explanation, the old review
  result, and `choice` and `number` questions only. A legacy issue only picks
  those kinds; a legacy read of a set issued to an updated app shows only its
  choice and number questions (and refuses with 426 when there are none); a
  legacy review of any other kind is not found, and a recording is refused.
  The teacher bank list shows a legacy client only choice and number
  questions. Legacy review receipts hash exactly what they did before, so
  retried pre-update operations replay.
- The legacy contract keeps the old offline answer loophole for un-updated
  clients only. It is one switch: `practice.legacy_set_contract`
  (`PRACTICE_LEGACY_SET_CONTRACT`, default on). Switched off, every legacy
  set request (issue, read, review) is refused with 426 "Update the app"
  rather than served a shape the old app cannot decode. Retire it once the
  apps send the header.

The kinds a client can answer are applied when candidates are chosen, before
the school's own count decides the library top-up
(`LibraryPractice::MINIMUM_OWN`), so a client limited to choice and number
questions still gets library ones when the school's own are all new kinds.

## Question kinds (`PracticeAnswers`)

| Kind | Learner gives | Marked | Mastery evidence |
| --- | --- | --- | --- |
| `choice` | `answer`: an option code | at once | 1 of 1 or 0 of 1 |
| `number` | `answer`: an exact decimal | at once | 1 of 1 or 0 of 1 |
| `steps` | `parts`: each step code (`s1`..`s8`) to its number or option code | at once, one mark per step | correct steps of all steps |
| `short_text` | `answer`: up to 500 characters | at once when it matches an accepted answer after normalising case, spacing and punctuation; otherwise by a teacher | 1 of 1 or 0 of 1 |
| `diagram_label` | `parts`: each region code to a label code | at once, by exact mapping (per-region marks are shown) | 1 of 1 only when every label is right |
| `audio` | a recording (multipart `audio`) | always by a teacher | 1 of 1 or 0 of 1 |

A response of the wrong shape (an answer for a step question, a recording for
a number question) is refused with 422. Definitions normalise to a stable shape
(normalising twice changes nothing), and the key order of `choice` and `number`
definitions is unchanged, so retained tutor checkpoint hashes still match.

AI teaching drafts still produce only `choice` and `number`
(`TeachingDraftDefinitions`); teachers write the other kinds themselves.

### Short written answers and AI

No AI provider is qualified yet (Action Plan 3.1), so short answers are marked
without AI: an exact match after normalisation, or the teacher. The seam for
AI-assisted marking is `ShortTextAssist`: switched off, it makes no provider
call and returns no suggestion. After qualification it may call the AI gateway
(never a provider directly) for a suggested mark and reason, shown to the
teacher in the queue (`suggestion`, always `null` today). A suggestion never
marks an answer by itself.

## Teacher marking (`PracticeMarking`)

An answer that waits has `state: awaiting_teacher`, no `correct`, no unit
counts and no memory schedule. It waits in the marking queue of the group the
set belongs to:

- Only the group's current teachers, by their dated allocation
  (`PracticeAccess::staff`, the same check as approving questions), can list,
  hear or mark it. Another teacher of the same school, a subject lead without
  an allocation, a learner, a parent or anyone in another school is refused.
  The review is found only through that group's sets.
- Marking is `correct` or `not yet`, written once: the same operation replays
  its receipt; any other attempt is refused with 409. The teacher's mark then
  writes practice memory and mastery evidence through the same code as an
  automatic mark (`DailyPractice::settle`, `MasteryRecompute::practice`,
  keyed by `practice:<review id>`), so evidence is written once.
- If the question has since stopped reaching the group (the teacher stopped
  using it, or the school switched the library off), it leaves the queue and
  cannot be marked; the answer is kept.

API: `getPracticeMarking`, `getPracticeMarkingAudio`, `markPracticeAnswer`.
Panel: `/question-banks/marking` (Material 3), linked from the question banks.

## Files

| File | Where | Limits | Who reads it |
| --- | --- | --- | --- |
| School diagram picture (`practice_diagrams`) | `academic_private`, `schools/<id>/practice-diagrams/<uuid>.enc`, encrypted | JPEG or PNG up to 4 MB, re-encoded as pixels only (`SafeImages`: no camera or location data), scanned before and after | the learner whose set holds the question (`getPracticeEntryDiagram`); the school's teachers who write practice (panel preview) |
| Library diagram picture (`library_diagrams`, platform) | `library_private`, `diagrams/<uuid>.enc`, encrypted | the same | the learner whose set holds the published library question; HQ library writers and reviewers |
| Recorded answer (`practice_audio_answers`) | `academic_private`, `schools/<id>/practice-audio/<uuid>.enc`, encrypted | M4A/AAC, WebM, Ogg or MP3 by content (never by name), at most 3 MB, scanned; dangerous uploads are refused by `RejectDangerousUploads` first | the current teachers of the learner's group only; every listen is audited |

A labelling question names its picture by id; the server fills its size from
the stored picture, found only in the current school (a school picture) or in
the library (a library picture). Another school's picture id is refused.

Retention: a recording is deleted 90 days after it is made, or 30 days after
a teacher marks it, whichever is first (`learning:prune-practice-audio`, daily
at 03:55). The mark stays. Recordings never reach the learner's family or the
learner again. Diagram pictures are kept with the questions that use them.

Rollback refuses to remove the question-type columns while recorded per-part
results remain, including automatically marked answers. Their encrypted parts
and unit counts must be exported and retained before rollback; backfilled legacy
reviews with no parts remain compatible with the previous schema.

## Authoring

- Teachers: the API approval (`approvePracticeQuestion`, with
  `uploadPracticeDiagram` for pictures) accepts every kind; the panel has
  `/question-banks/write` (Material 3) with one plain form for all kinds. The
  form's text formats: options and labels as `code: text` per line; steps as
  `step | answer` or `step | answer code | a: option; b: option`; regions as
  `code: across, down` in per cent; the mapping as `region: label`.
- HQ library writers: `/saas/library` item forms take the same fields and a
  diagram picture upload; reviewers see the picture and the mapping.
- `PracticeAnswers::definition` checks everything the same way for both.

## Schools kept apart

Every new school row has `school_id` (`practice_audio_answers`,
`practice_diagrams`) and is read through `BelongsToSchool`; files sit under the
school's own storage folder and their paths are checked before reading.
`library_diagrams` is platform-owned like the rest of the library and refuses
writes while a school is current. Tests cover the wrong school, wrong teacher,
wrong learner and parent for the queue, recordings and diagram pictures.

## Client change

In the answer-free contract `items[].question` replaces `items[].definition`,
and `review` gains `state`, unit counts, `parts`, `marked_how` and `solution`
(`correct` and `schedule` may be `null` while an answer waits). In
`openapi.yaml` these are `PracticeDailySetV2`, `PracticeSetEntryV2` and
`PracticeReviewV2`; the legacy `PracticeDailySet`, `PracticeSetEntry` and
`PracticeReview` are unchanged, and the responses accept either. The Dart
client (`packages/eschool_api`) must be regenerated, and the learner app must
send the header, stop marking locally, show "saved, marked when it syncs",
fetch diagram pictures, record audio and show the solution from the review.
Until it does, it keeps working on the legacy contract. The classroom tablet's
web client in this repository already opts in; it offers `choice` and
`number` questions, and sets issued from it contain only those.

## Not in this step

- Updating and regenerating the Flutter apps and the Dart client.
- AI-assisted short answer marking (needs Action Plan 3.1).
- Teacher feedback text to the learner, partial marks for teacher-marked
  answers, and marking on behalf of an absent teacher by a subject lead.
- Recording on the shared classroom tablet.
- Numeric tolerance for number answers.
