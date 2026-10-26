# Phase 3/4 panel navigation label qualification

Integration CI at `7ae099f4e` exposed a missing Pathways navigation label in
all four panel language catalogues. Reproduced the exact failing navigation-label
test locally before the fix (1 failing test). Added explicit English fallbacks
for `Subject choice and pathways` and the new `Peer tutoring` navigation entry,
preserving every existing translation and catalogue entry.

The complete existing NavigationTest suite passes: 9 tests, 641 assertions.
No assertion, catalogue coverage or CI requirement is weakened. Fluent school
wording review remains separate from complete fallback coverage.
