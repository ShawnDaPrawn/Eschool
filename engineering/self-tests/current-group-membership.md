# Current group learner membership

Four real MySQL regressions first failed: a parent still received a current group
and remained in its parent communication audience after the child's Student
membership ended or account was suspended. A current Student role in another
school did not make this valid.

Current group reads and current communication enrolment resolution now require an
active learner account and an active, dated Student membership in the selected
school. Guardian relationships and historical academic/enrolment records remain
retained; their historical readers use their existing policies. This predicate
does not grant new staff or parent authority.

Evidence: all 78 affected ActivityGroups, MessageEngine, MessageRecipientRole and
consultation electorate cases passed (931 assertions). The final four regression
cases also preserve another valid classmate's guardian and pass (36 assertions).
Automated API reads verify an allocated coach remains authorized. PHP 8.3.36 runs
against the real isolated MySQL database; the own-worktree application autoload
was checked by reflection. Whole-app Larastan reported zero errors and Pint passed.
