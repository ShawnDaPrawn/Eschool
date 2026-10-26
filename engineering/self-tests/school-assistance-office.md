# School support office and private update notices

Implementation adds the panel `/ask-school` office workbench and recipient-bound
generic update notices to the existing assistance services. No API operations or
SDK schemas change; external inference remains disabled by default.

Recorded checks so far:

- Office panel HTTP: 6 cases / 68 assertions pass, including real claim-before-body,
  exact reply replay, original-preserving translation review with inference off,
  approved document versioning and withdrawal, read-only access, selected historical
  year refusal without session flash, account/school pinning, other-school source
  IDs and guardian/office withdrawal. Invalid version edits retain their document
  key rather than becoming a separate publication.
- Source inference plus preceding panel cohort: 20 / 146 passed. Additional source
  regressions keep internal released-record IDs/audit fields out of the reviewed
  provider text while preserving the full immutable source hash, and minimise the
  original authorised office author without changing the original message.
- Notice focused cohort: 5 / 29 pass, covering exact replay, prepared inbox and
  queued delivery withdrawal, wrong school/owner/role, malformed source binding,
  bounded expiry and the real outer transaction rollback.
- Panel TypeScript checks pass. Selected Pint checks pass.

The final combined native/inventory and migration rehearsal, static analysis and
actual three-width panel browser proof are pending at this checkpoint. App client
journeys are a separate increment. This record does not claim real provider,
semantic translation, physical-device, production or human acceptance evidence.
