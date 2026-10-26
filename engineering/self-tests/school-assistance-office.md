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

The combined assistance/inventory cohort passes 45 cases / 1,373 assertions,
including all seven final notice cases. The first combined attempt encountered an
old root-owned local file-cache lock directory; the native runner uses the real
database session-lock store, the exact case passes, and the complete affected
cohort then exits green. No locking or product assertion was disabled. Full
existing-school down/up/backfill rehearsal passes 1 / 327 with the new intent
migration. Selected affected PHP static analysis reports no errors. Actual panel browser
journeys pass at 390/820/1280 pixels (3 / 3, 56.2 seconds), including claim,
private original messages, escaped script text, translation review with provider
disabled, document publication/withdrawal, serious/critical axe checks, console
errors and horizontal overflow. The first browser attempt used an exact DOM-label
finder that included the required asterisk; the final test selects the exact
accessible textbox role/name, with no product or assertion bypass. App client
journeys are a separate increment. This record does not claim real provider,
semantic translation, physical-device, production or human acceptance evidence.
