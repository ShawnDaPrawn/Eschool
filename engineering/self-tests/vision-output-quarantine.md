# Vision output quarantine references

Output-only moderation previously retained the flagged output without its
approved crop references. Safeguarding media resolvers correctly refused that
unbound crop. The gateway now retains the exact already-resolved manifest refs
with flagged output after the existing fresh source/context check. No pixels,
URLs, extra sources or new reader privileges enter the snapshot.

Two regressions were proved RED: the registered vision output snapshot lacked
refs, and a real Tutor output-only flag could not supply its crop to a fresh
safeguarding reviewer. Both now pass. The full registered-vision and Tutor
domain cohort passes 33 tests / 180 assertions; selected gateway PHPStan and
owned Pint pass. Learner preview and non-safeguarding access remain refused;
unknown-outcome charging and exact source manifests remain covered. Fixtures
are synthetic HTTP protocol evidence, not live-model safety qualification.
