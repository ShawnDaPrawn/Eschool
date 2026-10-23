# Legacy profile backfill — 23 October 2026

This change adds a bounded, explicit-school/profile console backfill. It does
not retire legacy JSON, caste/religion columns or public files, enable guardian
enforcement, run a live migration, or claim Phase 1 acceptance.

## Local verification

`LegacyProfileBackfillTest` uses two synthetic schools on MariaDB. It covers a
fully immutable dry run, encrypted apply and audit safety, hidden field review,
learner/parent/teacher ownership, wrong-school source rows, resumable batches,
newer form edits and explicit clears, malformed/conflicting maps, options and
numeric validation, precision safety, ambiguous person records, unavailable
mappings, invalid existing storage, pending private files and command limits.

Run the new class with the existing schema, validation, form, file and panel
tests: 73 passed, 427 assertions. Pint and Larastan passed for the change. The full backend and required
PR checks must pass before merging; consult the PR for exact-head results.
No app code, API shape, route or database schema changes in this slice.

Review regressions cover real option-keyed legacy checkbox maps becoming JSON
lists, and a second MariaDB connection committing an explicit clear after the
transaction snapshot. The current read preserves that clear; removing the lock
reproduces a duplicate-insert failure. Both review findings are fixed.

The Security Diff Scan skill was consulted, but its referenced preflight,
terminal helpers and scan tools are unavailable in this host. No completed
Codex Security scan is claimed. Direct source review covers explicit school
selection, source ownership, insert-only encrypted writes, fresh locked
definitions, private reports and the absence of destructive operations.

## Outstanding evidence

Rehearse against an isolated backup of existing data before a live backfill.
Resolve fixed-code reports, review hidden definitions, migrate archived records
and files, implement compatibility writes, and verify old installed clients
before removing plaintext storage. These are tracked implementation/rollout
tasks; they do not freeze unrelated phase work. Cloud staging, device/owner
acceptance and production release remain separate.
