# Adult withdrawal qualification

Initial withdrawal/refund regression passed24 tests/174 assertions. Subsequent
own v2 and Finance panel checks passed15 tests/114 assertions. The outside payout
test then passed both wallet-first and fee-refund-first service paths, refusing a
duplicate reference while retaining the other reservation (1 test/7 assertions).
Its initial fixture lacked the fee account's required dated enrolment; that test
setup was corrected. No financial policy or product assertion was relaxed.

Four independent MariaDB process races passed33 assertions: competing reservations
cannot overdraw, one UUID reserves once, one pickup place cannot be booked twice,
and one outside payout identity cannot be recorded twice. SQL immutability and
retained-history rollback refusal are tested. Current withdrawal/refund/concurrency regression passed34 tests/285 assertions.

The complete67-case shared wallet suite passed. The expanded16-case withdrawal
suite passed with both exact-boundary choices and a390px Teacher cash form,
bringing the current combined inventory to69 tests. Cases cover unknown outcome
recovery after reopening, opaque persisted UUIDs, private-view clearing, malformed
scope/money/policy/receipt replies, bank forms at390/820/1280px, approval and payout
receipts, and no holder cancellation after approval. The final combined69-test suite passed after restoration.

Four real Chromium Finance cases passed without retries at390/820/1280px plus
Teacher refusal. They cover explicit boundary choice, independent approval,
verified bank payout/replay, clean print, rejection restoration and disabled
history, with accessibility/overflow/duplicate-ID/browser-error assertions.
Money PHPStan5 and changed staff smoke-source analysis passed after restoration.
The regenerated2627-file SDK is byte identical to the recorded snapshot.

Full Learner/Parent/Teacher/dedicated Facilitator native Chrome journeys passed.
The SDK suite passed all 182 tests, configured panel static analysis and the
documentation build passed, and fresh Pint passed. A populated upgrade preserved
original financial rows byte for byte, backfilled an already-paid fee refund once
and completed a new original-holder bank withdrawal with healthy reconciliation.
Empty rollback/reapply passed and retained rollback was refused. Exact-source
hosted CI/review/main integration remains pending. Gateway execution, physical
devices/staging, fluent language review and independent financial acceptance remain
separate evidence. New language entries use explicit English fallbacks where draft
core translations are not supplied.

The expanded full-app Parent flow exposed an ambiguous generic statement button
when the additional section pushed a child wallet below the visible list. Wallet
statement buttons now have stable wallet keys and the smoke journey scrolls to the
intended child, retaining its exact balance and privacy assertions. The list now has an explicit scroll target, including sections below expanded
deposit history. Final native qualification passed all four profiles; the initial
failed Parent runs are retained as failures, followed by the corrected run.
