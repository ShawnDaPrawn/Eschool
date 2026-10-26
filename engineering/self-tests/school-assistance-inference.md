# Assistant and message translation inference foundation

This increment implements purpose-scoped inference over approved school documents,
school calendar, the Parent's own released marks/canonical attendance, and exact
immutable Parent-office support messages. It does not yet register API routes or
ship screens. Those are the next vertical delivery.

Local evidence:

- 9 initial source/protocol cases passed (55 assertions), including no provider
  calls with inference disabled, hidden other-owner/school/role sources, current
  released records, both translation directions, unchanged originals, invented
  citation quarantine, guardian loss during an HTTP callback and expiry.
- Source/domain plus school-table inventory/full rollback/reapply passed 21 tests
  / 1,343 assertions before the narrow Admin hook was added; migration unchanged.
- Assistant plus existing feature-inference regressions passed 23 / 141 with the
  narrow hook; assistant plus staff foundation passed 31 / 183. Successful expiry
  now also proves gateway replay text is erased while the metering total remains.
- An additional storage-bound regression passed 1 / 3: 50 live review turns block
  another prepare even while external inference is disabled.
- Selected PHPStan and Pint are run on the changed boundary before publication.

The literal Admin exception is restricted to a Staff scope for one server-owned
feature policy registered identically in both allowlists. It checks school/actor
before calling the hook, still requires Admin in feature roles, then reauthorizes
current office permission and the exact claimed message. Tests deny missing or
mismatched hooks, config without Admin, unclaimed/reassigned messages, forged
actor/school scope, Parent-as-staff, revoked office and unrelated features. The
shared global staff role list remains unchanged.

These are synthetic bounded HTTP protocol checks, not real-provider evaluations.
Both inference features and provider qualification remain disabled. Versioned
prompts and evaluation case files are present; no adjudicated provider observation,
semantic grounding/translation quality, device or production acceptance is claimed.
Known-name minimisation requires human review of the exact preview and does not
claim universal PII detection. Quarantine blocks normal turn previews immediately;
its separate safeguarding retention remains under the existing review policy.
