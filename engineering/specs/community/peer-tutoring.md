# Supervised peer tutoring

The retained Phase 4 feature matches a learner's current mastery root gaps with
an approved peer who has evidenced level 3 or 4 in that topic. A currently
authorised teacher/academic lead supervises the session within their existing
academic group scope. An outside facilitator does not gain Teacher mastery
access. Matching data is staff-only; learners and current guardians see their
own supervised arrangements and service ledger, never a partner's mastery.

The `peer_tutoring` module is off by default and depends on `mastery`. Scheduling
rechecks published topic visibility, current group/year, dated allocations,
approved current participants and the selected match. School-local times use
the existing unambiguous wall-time parser. Sessions are bounded to three hours,
within the current year, and cannot overlap for a supervisor or participant.
Writes are school-locked, revision checked and exactly idempotent.

Only the recorded supervisor or authorised academic leadership can confirm
actual elapsed attendance, cancel or correct a session. Completion appends
integer service minutes for the tutor. Optional explicit house points use the
real HousePoints authority, categories, current house, daily cap, events and
notifications in the same transaction. A correction appends the opposite service
entry and reverses the actual point entry with a reason; it never rewrites
history or restores the staff allowance. Attendance never manufactures mastery.
Missing/disabled point configuration does not stop independent supervised
sessions or service minutes; it is displayed as no points awarded.

No chat, private notes, counselling data or provider inference is added. Events
and idempotency receipts contain references only. Every read/replay reauthorises
the current actor and participants. Family clients are read-only; staff clients
and the responsive panel provide matching, scheduling and attestation. Current
context changes/background erase private matches and input; unknown writes
retain only a bounded encrypted exact intent. Published-head CI, staging,
devices and production acceptance are tracked separately.

## Every child teaches something (Action Plan 4.7)

Builds on the supervised model above. Nothing about who may tutor, who
supervises or what a session records is loosened.

### User stories

- As a teacher allocating peer tutors, I see first the learners who have not
  tutored yet this term, with the reason ("Has not tutored yet this term"), so
  that tutoring is not always given to the same few learners.
- As a teacher, I can pair a learner who captains an activity group (the chess
  club, a soccer team) with a learner in my teaching group, to teach that
  activity as a non-academic skill, under my supervision.
- As a principal, academic head or grade head, I see how many learners in each
  grade have tutored at least once this term, as counts, never as a list or a
  ranking of learners.

### Rules

- **This term.** For a grade, the term covering today (`terms.grade_from` to
  `grade_to`). Between terms, the term that began most recently, running on to
  the end of the school year. Before any term has begun, the whole school year.
  School-local dates; a session belongs to the term its start falls in.
- **Fairer matching.** The eligible peers (unchanged: a current member of the
  supervised teaching group, enrolled this year, with an approved guardian
  consent, and with level 3 or 4 evidence in one of the tutee's root gaps) are
  ordered by the number of peer sessions they have as tutor this term, fewest
  first, then by learner number. Scheduled and completed sessions count, in any
  teaching group of the school; cancelled and corrected (reversed) sessions do
  not. The order is deterministic and needs no stored score. The panel shows
  each match's count as its reason: "Has not tutored yet this term", "Has
  tutored once this term" or "Has tutored N times this term", and the term's
  name. At most 50 matches are offered, as before.
- **Activity skills.** With the `groups` module on, a peer who holds a captaincy
  this school year (role `captain`, membership active and begun, in an active
  group of the current year) is offered to teach that group's activity, named by
  the group. A plain member is not offered. A group the tutee also captains is
  left out. Every other check is the same as for a mastery topic: the same
  teaching group, supervisor, consent, enrolment, three-hour limit, overlap
  check, revision and idempotency. The session stores `activity_group_id` and
  the group's name at scheduling (`skill_name`); `topic_id` is empty. Completion
  appends service minutes and optional house points exactly as before. It never
  writes mastery.
- **API.** The v2 API and the apps are unchanged: they carry mastery-topic
  matches and sessions only, in the same shape. The API's matches use the same
  fair order but carry no reason field. Activity-skill sessions are not listed
  there, and reading or changing one through the API is "not found".
- **School measure.** `GET /peer-tutoring/measure`. Per grade: current learners
  (enrolled this year today, with an active learner membership) and how many of
  them have at least one completed, uncorrected session as tutor this term, with
  the share as a whole percentage. Counts only: no names, lists or ranking.
  Staff with `academic-structure-manage` or a current principal appointment see
  every grade; a current grade head sees their own grade; everyone else gets
  403. The peer tutoring page links to it for those who can see it.
- **Schools.** Everything is read through the current school: another school's
  learners, activity groups and sessions are never matched, scheduled or
  counted.

### Data

- `peer_tutoring_sessions.topic_id` becomes nullable; new nullable
  `activity_group_id` (foreign key to `activity_groups`) and `skill_name`
  (191 characters). Exactly one of `topic_id` and `activity_group_id` is set,
  enforced by the service. New index `(school_id, status, starts_at)` for the
  term counts. Rolling back refuses while any activity-skill session exists.

### Endpoints (panel)

| Method and path | Who |
| --- | --- |
| `POST /peer-tutoring/skill-sessions` (`teaching_group_id`, `tutor_learner_id`, `tutee_learner_id`, `activity_group_id`, `venue`, `starts_at`, `ends_at`, `operation_id`, `expected_school_id`, `expected_account_id`) | the same supervisors as `POST /peer-tutoring/sessions` |
| `GET /peer-tutoring/measure` | `academic-structure-manage`, a current principal or a current grade head (own grade) |

### Acceptance tests (`PeerTutoring/PeerMatchingFairnessTest`)

| Test | Given | Then |
| --- | --- | --- |
| `test_matches_favour_learners_who_have_not_tutored_this_term` | Ben and Cara both eligible; Ben is scheduled to tutor | Cara is offered first, "Has not tutored yet this term", Ben second, "Has tutored once this term"; the API's matches have the same order and only the contract's fields; cancelling Ben's session restores the learner-number order |
| `test_sessions_from_an_earlier_term_do_not_count` | Term 1 in January, Term 2 from February; Ben tutored in January | in Term 2 Ben counts 0 |
| `test_eligibility_rules_still_apply_to_learners_who_have_not_tutored` | Cara has not tutored but her consent is withdrawn; then Ben's evidence drops to level 2 | Cara is not offered; then nobody is |
| `test_a_captain_can_be_matched_to_teach_an_activity_skill_under_the_same_supervision` | Cara captains Chess club, Ben is a member | Cara is offered "Chess club", Ben is not; the API matches have no activity skill; scheduling records the group and its name with no topic and the teacher as supervisor; completion appends service minutes; the API does not list, read or reverse the session |
| `test_activity_skill_needs_the_groups_module_and_a_captaincy` | captaincy withdrawn; `groups` module off; a plain member | not offered; scheduling refused (409); nothing written |
| `test_panel_schedules_an_activity_skill_and_the_wrong_role_is_refused` | the panel; an unallocated teacher; a parent | the supervising teacher schedules once (replay is idempotent; a topic field is refused); the others get 403; nothing written |
| `test_another_schools_activity_group_or_learner_is_refused_and_left_unchanged` | School B's activity group and learners used from School A | refused; no session written in either school; School B's groups unchanged |
| `test_school_measure_counts_learners_who_tutored_this_term_per_grade_without_names` | Ben completed a session, Cara only scheduled one | Grade 6: 1 learner, 0 tutored; Grade 7: 2 learners, 1 tutored, 50%; the page shows no learner names; an unknown query is refused; after a correction Grade 7 counts 0 |
| `test_grade_head_sees_only_their_grade_and_other_roles_are_refused` | Theo heads Grade 6; Tara is a teacher; a parent; a learner | Theo sees Grade 6 only; the others get 403 and no link |
| `test_school_measure_never_counts_another_school` | School A has a completed session; School B's measure | School B counts only its own learner and 0 tutored; School A's counts are unchanged |

### Not in this step

- The apps and the v2 API: activity-skill matches and sessions, the fairness
  reason and the measure are panel-only. Families see activity-skill sessions
  only once the API and apps carry them.
- Other recognised skills: awards in activity categories, coach endorsements or
  learner-declared skills. Only captaincies count for now.
- A measure over several terms or by teaching group, and a nudge to staff when a
  grade's share is low.
- Feeding peer tutoring into "No child unseen" (4.5) as a positive contact.
