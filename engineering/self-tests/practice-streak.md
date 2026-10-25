# Practice streak date aggregation

A learner completing ten subject sets a day over one hundred consecutive dates
previously received an eighty-day streak because the latest 800 rows were selected
before duplicate dates were removed. The new query selects distinct completion
dates within the bounded current/prior-365-date window. It retains the existing
maximum 365-day display and the allowance for beginning the count yesterday.

Qualification: the real MySQL regression inserts 1,000 completed sets over 100
dates. The original source failed (80 versus 100); the corrected source returns
100. Removing an interior completion date then returns 40. The full practice
domain/API cohort passes: 16 tests, 184 assertions. Affected Larastan and Pint pass.
No provider calls or production records were used.
