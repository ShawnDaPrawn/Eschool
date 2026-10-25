# Live house points and recognition readers

The existing credential-bound socket owner now feeds the family and staff
recognition readers. Reconnects trigger protected HTTP refreshes as well as source
invalidations. The family home card shows the current school-year house counters
and a short inline celebration when its selected learner receives a new positive
point. No reason, person name or private record body arrives over the socket.
The card reads the current owned learner history before showing a celebration.

Twenty-five shared recognition widget/repository/session tests pass, including
390/820/1280 layouts, counter and celebration refresh, foreign-school event
exclusion, current refusal, background erasure, late old-child responses,
coalesced updates, and reconnect-only invalidations. Actual learner and Parent
Chrome flows pass against a real loopback Reverb socket and signed synthetic
publisher: the counter changes5→10 and the own positive-point celebration appears
within the two-second assertion, then protected history/awards and revoked-resume
erasure continue to work. The isolated PHP relay/source/outer-commit tests are
qualified separately; this browser fixture certifies transport and rendered
client behavior, not production latency or a live-school result.

The first browser run exposed a retained expired snackbar controller being closed
again during background. The celebration now belongs to the inline card, expires
there and clears directly on background/withdrawal, without interacting with
other screens’ notification queues. The home card uses an inline current-context
guard rather than embedding a full-page refusal/loading screen in its layout.
It never stores a disk copy or sends a mutation.

Shared and affected family/Teacher/facilitator analysis has no errors or warnings
(informational style lints remain). The staff readers consume the same adapter;
existing staff source authority and controls are unchanged. The current dedicated
facilitator and classic Teacher destination checks remain in the CI matrix;
this increment’s actual real-relay browser journey covers learner and Parent.
Actionlint and Python syntax checks pass. The integration driver saves captured
synthetic framework errors on failure so browser regressions are reviewable.
