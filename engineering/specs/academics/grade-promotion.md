# Promote by grade

Action Plan step 1.18. Owner role: Backend and Admin UX.

The legacy "Promote learners" page works one class at a time and writes only the `student_sessions` projection. Promote by grade moves the whole school into next year in one go and writes the dated enrolments (`EnrolmentService::place`), with the projection kept in step.

## User stories

- As a school administrator at year end, I promote every learner into next year in one go, so I don't have to promote 40 homerooms one at a time.
- As a school administrator, I mark the learners who repeat a grade, leave the school or go to a different homeroom, before anything is written.
- As a school administrator, I paste those exceptions from the sheet the grade heads sent me, by admission number.
- As a school administrator, I can run it again after placing a few learners by hand; those learners are left as they are.

## Rules

- **Who.** Every learner with an enrolment in the "from" year on that year's last day.
- **Default move.** A homeroom goes to the homeroom in the next grade (`grade_levels.sort_order`) with the same section letter and the same medium, preferring the same stream and shift. A homeroom in the last grade (Grade 12) defaults to *leaves*. A homeroom whose class has no grade level, or with no matching homeroom next year, has no default: its learners must be placed.
- **Exceptions per learner.** Promote, repeat (same homeroom), place (a chosen homeroom) or leave.
- **Already placed.** A learner who already has an enrolment in the "to" year, or is already marked as leaving it, is shown and left alone.
- **Dates.** Enrolments start on the "to" year's first day. The "to" year must start after the "from" year ends; this year's enrolments are not changed.
- **Projection.** Each learner's `student_sessions` row for the "to" year gets status 1 (continues), result 1 (or 0 when repeating) and `previous_session_year_id`; a leaver's row gets status 0 and no homeroom, as the legacy page does. When the "to" year is already the current year, learners' homerooms move and leavers' sign-ins are switched off now, as making a year current does (`SessionYearService::applyPromotions`); otherwise that happens at the switch.
- **Check, then promote.** The page shows the default moves. "Check changes" applies the choices on the page and a pasted sheet. "Promote learners" writes in one transaction and is refused if any learner, placement or choice differs from what the page showed (a fingerprint of the plan), or if a learner has no homeroom.
- **Audit.** `people.grade_promotion_committed` with the two years and the counts.

## Acceptance tests (`People/GradePromotionTest`)

| Given | When | Then |
| --- | --- | --- |
| Grade 5 A with two learners, Grade 6 A exists; Grade 12 C with one | plan | both Grade 5 A learners go to Grade 6 A; Grade 12 C leaves; School B's learners are not in it |
| The default plan | promote | next year's enrolments start on its first day in Grade 6 A; the leaver has none; projection rows and the audit row are written; this year's enrolments still run to its last day; running again changes nothing |
| One learner repeats, one is placed in Grade 6 A | promote | repeat keeps the homeroom with result 0; the placed learner is in Grade 6 A |
| A sheet with a header, a repeat, a place by homeroom name (any case or spacing), an unknown admission number, an unknown outcome and an unknown homeroom | parse | two exceptions; three row errors naming the row |
| A learner placed by hand after the page was shown | promote | refused; the learner now shows as already placed |
| No Grade 6 A | plan, promote | one homeroom without a target is unresolved; promoting is refused |
| "From" and "to" reversed | plan | refused |
| A school administrator | open, check with a repeat, promote | the counts show; the repeat is written; a form missing its last field is refused (422) |
| A teacher without the promotion permissions | open or promote | 403; nothing written |

## Endpoints (panel)

| Method and path | Permission | Notes |
| --- | --- | --- |
| `GET /promotions/by-grade?from=&to=` | `promote-student-list` | defaults: the current year into the upcoming year (`NewYearService::upcomingYear`); with no next year, back to the new-year wizard |
| `POST /promotions/by-grade/check` | `promote-student-list` | `from`, `to`, `learners[id][outcome]`, `learners[id][homeroom]` (name), `sheet`, `complete` |
| `POST /promotions/by-grade` | `promote-student-edit` | the same plus `fingerprint` |

No API change.

## Screens

`people/grade-promotion`: year choice; summary counts; one table per homeroom ("Grade 5 A to Grade 6 A") with each learner's outcome and homeroom (typed, from a list of all homerooms); a sheet box; "Check changes" and "Promote learners" (disabled while a learner has no homeroom). Empty state when no learner is enrolled on the year's last day. `grade-promotion.js` sends only the choices that differ from the default, so a whole school fits under PHP's `max_input_vars`; the form ends with a `complete` field, and a request without it (cut short by the server) is refused rather than half-applied. The new-year wizard's promotion check links here. Navigation: Calendar and years, "Promote by grade".

## Security notes

School isolation through `BelongsToSchool` on every model read (years, homerooms, enrolments, learners). Homeroom choices resolve only to this school's homerooms. Writes happen in one transaction with the "to" year locked, so two administrators cannot promote at the same time. Leavers keep their records.
