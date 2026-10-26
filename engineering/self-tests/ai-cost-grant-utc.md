# Global AI cost-grant expiry — 26 October 2026

An applied local clock changed the SQL comparison against UTC DATETIME grants.
A genuinely expired grant revived in Pacific/Honolulu; a valid grant was refused
in Pacific/Kiritimati; the console also persisted a local 04:00 deadline instead
of its intended 14:00 UTC. All three cases were executed against the old code and
failed before the correction.

The existing UtcInstant cast now normalizes the model, console expiry is written
in UTC and every cost-access SQL comparison uses UTC. No permission, role,
allowed duration, schema, provider setting or school capability is added.

All ten AI operations cases pass (60 assertions), retaining cost-only authority,
identity-plus-grant, expiry/revocation/impersonation, distinct currencies, private
content exclusion, caps and CLI checks. The three timezone regressions cover
expired/valid access, exact decoded instants and the persisted console deadline.
Selected Larastan, Pint and the patch secret check are recorded at publication.
The source receives an independent review alongside the matching new SaaS-grant
correction. No real operator grant or provider call is made.
