# Bank import and EFT matching scoped review

Reviewed surface: the bank CSV parser, private panel request/controller/views,
import/matching services and models, migration/triggers, reconciliation extension,
navigation/form protection and the new tests. Supporting boundaries inspected:
MoneyPolicy, BillingOperations, FeePayments, FeeStatements, CurrentSchool,
PrivateDomainResponse and existing upload validation. This is a scoped implementation
review, not an independent live-money assessment or a repository-wide audit.

Threats checked: a foreign-school finance user reading/importing/matching another
school's bank rows; teacher/parent access; stale actor/school sessions; revoked
permissions/modules; forged or stale review bodies; repeated/overlapping imports;
manual EFT/new import duplication; bank debits converted into receipts; holder or
learner-pin substitution; plaintext/private data retention; CSV injection into HTML;
partial writes, altered history and destructive migration rollback.

Controls include school-scoped ORM reads and composite SQL links, fresh locked
policy at confirmation/replay, CSRF and actor/school-bound private forms, explicit
CSV bounds/types, HMAC reviews, stable business identity, duplicate/conflict refusal,
exact existing-receipt comparison, immutable SQL guards, encrypted details/reasons,
escaped no-store views and ID/count-only audit. Importing and excluding are
non-posting. A new match uses the existing atomic double-entry receipt workflow;
allocation remains separately reviewed. Source/receipt uniqueness and school locks
serialize competing writers. Injected late-failure tests verify financial, audit,
event and replay rollback. Ordinary unresolved rows are legitimate queue state.

No confirmed exploitable issue remains from this scoped review. Managed Codex
Security scan/preflight tools and referenced helper resources were not available
in this host; no managed scan, independent sign-off, SARIF or exhaustive coverage
is claimed. Existing hosted history/secret scans and exact-head code review remain
part of PR qualification. External bank-format validation, real-money observation
and the independent money-flow assessment remain separate release evidence.
