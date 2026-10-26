# Teaching mobile fixture qualification

The Android Facilitator job reported a missing saved-title widget after editing.
A focused 390 by 844 test with a 300-pixel keyboard inset exercises the full
existing generation, recovery, edit and approval fixture through the real shared
repository. The old helper reproduces a missing lazy recovery control; waiting
alone never builds that off-screen ListView child.

The helper now checks the persisted revision and exact definition, brings the
refreshed title into view, and scrolls the outer list to recover the original
approval. Exact approval UUID/body and effect-count assertions remain intact.
No product authorization or test timeout was changed.

The old helper fails the new test; the corrected helper passes (one complete
workflow). This is local widget qualification. The previously failing Android CI
job must rerun before claiming that native profile passes.

The final-review editor now also resets its local state after a fresh read of the
same server revision. Its private widget key includes an editor instance counter.
The mobile/browser fixture now waits for the displayed saved revision rather
than depending on that private key. Exact saved-definition, operation UUID and
effect-count checks are retained. The 390 by 844 / 300-pixel keyboard regression
passes with the new selector; the affected Chrome CI flows still need to rerun.

Combined Phase 3/4 qualification: all 96 Teacher app tests pass. The staff
policy/password fixture selects its actual POST sign-in request explicitly;
a subsequent legitimate home read must not be mistaken for that request.
Combined backend cohort: 606 tests / 3,141 assertions pass. Whole-app Family
and Teacher analysis reports zero errors/warnings, and full-panel Pint passes.
