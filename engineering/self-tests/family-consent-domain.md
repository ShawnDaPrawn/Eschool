# Family consent and restricted health domain — 25 October 2026

This is a domain increment. New HTTP endpoints, SDK models, panel screens and app
screens are not delivered by this PR. Existing generic profile screens remain
compatible. Meeting booking and early-collection passes remain separate work.

## Implemented behavior

- Reuse the encrypted learner Health profile category. A fresh restricted family
  actor and current enrolled/approved child precede access. Guardians need current
  rights; staff need explicit health permission plus the existing profile-field
  policy. Platform identities and impersonation are refused. Category filtering
  happens before value loading/decryption; a corrupt unrelated identity value
  cannot prevent the health reader from working.
- Publish an immutable activity/trip request with exact wording/event dates and a
  named dated group audience. School/role/allocation/membership grants must cover
  the whole event, including when the actor has another school's longer grant.
- Authenticated guardians explicitly acknowledge the exact fingerprint and sign
  their account name. Responses append decision, guardian, timestamp, revision and
  a server-authenticated attestation. Earlier responses remain intact. This is an
  auditable online attestation, not a qualified legal digital-signature claim.
- Compare-and-swap revisions prevent one guardian silently replacing another's
  decision. Exact operation IDs recover committed responses after a transport loss
  or response deadline; changed bodies fail. Negative withdrawal remains available
  after the positive response deadline.
- Effective consent is recalculated from current approval, guardian, group and
  enrolment sources and the text/response signatures. A retained yes row alone is
  never operational authority. Cancellation retains the old signed history.
- School-composite foreign keys prevent substituting another school's group,
  request or learner. Wording, typed signature names and receipts are encrypted;
  audit metadata includes no body or signature. Rollback refuses signed history.

## Evidence

The initial targeted domain cohort passed 14 tests/49 assertions. The final cohort
passes 66 tests/1,032 assertions: 17 new consent/health cases plus existing profile
schema/validation, table inventory and role-matrix regressions. Includes an actual
parent linked at both schools, wrong guardian/role/school, stored wording/signature
tampering, ended future grants, stale guardian revisions, outer transaction rollback,
UTC round-trip and health-value encryption/category isolation.

A direct legacy profile read reproduced a real platform-identity medical-value
leak before the field policy correction. The health category and locked medical
keys now also refuse platform/across-school/impersonation access through the old
generic profile readers/editors; normal school and guardian behavior is retained.

Whole-app PHPStan reports no errors; Pint passes. The first migration attempt
caught an absent composite group index before any publication; the migration now
creates and removes that prerequisite in the correct order. No external provider,
production database, paid service or real child/medical data was used.

No new browser/native journey is claimed here because the new endpoints/screens
are still pending. Continue directly with those consumers, then meetings and gate
passes; these checks do not freeze independent development.
