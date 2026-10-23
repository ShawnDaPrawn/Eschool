# Phase 1 security evidence

| Control | Evidence | Status |
| --- | --- | --- |
| Guardian ownership and school boundaries | Approval/invitation tests and route tenancy suite | Local automated coverage; staging pending |
| Verified consent and immediate withdrawal | Consent version, verified mailbox, per-learner lock, token/session revocation, link status checks | Implemented and tested with enforcement enabled |
| Invitation replay and privacy | Random token, hash lookup, encrypted reminder secret, atomic consume, expiry and replacement | Implemented and tested |
| Legacy guardian rights | Ended links override legacy parent fields; fee visibility separate from academic access | Regression coverage added |
| Production rollout | Approval enforcement stays disabled until existing-family onboarding is verified | Not enabled; no consent back-filled |
| Modern sign-in, profile encryption, support access and retention | Remaining implementation work | Pending |
| Staging security and rate/load tests | Requires funded infrastructure and configured access | Pending; does not freeze other implementation |
