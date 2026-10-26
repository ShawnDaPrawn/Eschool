# Learning access while fees are outstanding

A valid, guardian-approved learner can use Home, learning, marks and released
reports while fees remain unpaid. The legacy compulsory-fee field remains a
string in the v1 settings contract
and is always returned as `0`, so older installed clients cannot restore the
former lock. The stored setting still controls overdue reminder calculation,
which remains independent of navigation and academic access. Panel wording
describes overdue
fee reminders and their start date rather than paid access to learning.

Fresh learner sign-in and returning-session startup both open Home. A parent's
child tile opens the child's academic screens even with unpaid fees; a fee
reminder is informational. The old saved fee-lock route redirects to Home even when the fees module is off.
Fee history and normal fee entry points retain existing module and guardian
fee-visibility permissions. Approval, active membership, owner and school
isolation checks remain authoritative.

Verification: server arrears tests assert the actual computed unpaid flag and
exercise Home/subjects/marks/reports, guardian fee privacy and other-school
refusal. Existing learner and child-switch smoke journeys now use unpaid
profiles with compulsory mode enabled, including a persisted learner session.
A widget test exercises the saved legacy route.


The Parent child cards use a minimum height and expand for overdue-reminder text
and larger content. The informational reminder has no link underline; opening
the child keeps its academic destination. A real Chrome RED→GREEN case verifies
the formerly overflowing two-child unpaid journey without suppressing errors.
