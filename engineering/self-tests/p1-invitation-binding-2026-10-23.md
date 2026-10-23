# Invitation lifecycle verification — 23 October 2026

[Specification](../specs/platform/invitation-lifecycle.md).

Two targeted tests reproduced old-mailbox acceptance before the fix. After
binding and lifecycle changes, 62 tests passed with 451 assertions: guardian
account invitations, learner setup, guardian approvals, mailbox retirement
migration, tenancy migration rollback and queued mail compatibility.

Cases include raw/bulk address edits, clearing edited-address verification,
changing an address back, missing legacy binding, ended memberships and
relationships, non-approving guardian overrides of legacy parent fields, expired
or used links while queued, and old unbound queued bodies. Suppressed jobs do
not call mail. Public School B links and printed codes (including lifelong
student numbers) work from the default school context without changing School A
credentials; the caller's school context returns afterwards. Retirement keeps
printed codes and rollback does not reactivate old emailed secrets.

Static analysis passed before final integration. Combined-main verification
and PR CI follow before merge. All mail used fakes or Laravel's array transport.
No production migration or live email was run. Staging mailbox/provider checks,
owner review and the family transition remain separate acceptance work.
