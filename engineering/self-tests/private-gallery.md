# Private gallery domain qualification

MySQL domain/transfer/inventory cohort: **44 tests / 1,201 assertions**, selected
Pint and whole-application Larastan pass. The earlier 41-test cohort had 1,185
assertions; the latest adds actual pixel-retention and orphan-erasure checks.

Coverage includes scanned/sanitized encrypted originals and thumbnails; stable
upload retries; guardian-first defaults and explicit self-consent configuration;
tightening a rule; wrong staff, family, school, approval rights and impersonation;
revoked learner approval; removing a tag and refusing stale reapproval; owned
pending/approved counts; approved handles inside current activity rosters;
retention after withdrawal even with the module off; and old orphan cleanup that
preserves both known variants, recent files and the other school.

Actual academic prepare/guardian-consent/release/accept operations prove default
tag removal and separately fingerprinted retained-photo copies into the exact
receiving family's private scope, with no other tags, source group grant or learner
activation. Changing that selection invalidates release. The existing 23 transfer
cases pass alongside the gallery cases. The completion listener is registered
once. The populated schema rollback refuses to erase consent history.

This increment implements domain services and transfer hooks. Public gallery API,
SDK, panel, native screens and their browser evidence follow separately. File
scanner responses are synthetic clean/refusal/outage fixtures; a deployed scanner,
physical devices and external staging acceptance are not claimed. No production,
store, paid-service activation or whole Phase 4 completion is claimed.
