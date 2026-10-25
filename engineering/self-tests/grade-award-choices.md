# Grade nomination choices

A regression reproduced a grade catalogue returning music and subject award categories before any learner in that grade had current matching membership. The correction filters candidate current placements by active learner membership and subject/group references, then calls the existing canonical `eligible()` policy. An ended membership removes the choice again. The exact nomination write policy and retained historical award access are unchanged.

The regression was proved failing before the correction and now passes **1 test, 4 assertions**. The complete recognition feature/API/panel cohort passes **53 tests, 392 assertions**; complete configured PHPStan and selected Pint pass. This corrects nomination choices; no private-category disclosure or physical/operational acceptance is claimed.
