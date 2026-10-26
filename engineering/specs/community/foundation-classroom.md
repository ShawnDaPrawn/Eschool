# Foundation classroom card entry

Root owns this retained Phase 4 increment; offline reader/cache/reconciliation
and physical encoding remain with the community stream. It extends the current
cards, academic allocations and approved daily-practice engine. New credentials
have no general Student, Teacher, v1/v2, gate, wallet or collection authority.

A school card/academic manager explicitly maintains which current teaching groups
are eligible for Foundation classroom use. Grade labels are never guessed. A
currently allocated Teacher authorizes a separately registered classroom device
for one eligible group for at most one hour. The short delegation and every
learner session recheck the fresh school, registration issuer, Teacher role,
allocation, current year, eligibility, card and guardian-approved current learner.
Removing any source authority prevents subsequent reads and writes.

The existing printed `NELOC1.school.card` value selects a current roster identity
inside that authorized classroom. It is not authentication for ordinary learner
accounts or any gate action. No accepted gate scan is manufactured. Each device
has at most one active learner; selection, expiry, explicit leave or withdrawal
invalidates that learner's short session. Learner and classroom lease secrets
are stored as hashes only; reference-only idempotency results can reconstruct an
exact response from a domain-separated keyed derivation without storing secrets.

A dedicated classroom web surface holds credentials in memory, erases them and
stops camera capture on background/exit, and never initializes an ordinary
Student or Teacher app session. It provides QR/manual classroom locator entry,
one learner's approved current-group practice and explicit leave. It exposes no
profile, marks, messages, finance, child switching or other school menus.

The checked practice adapter supplies the server-bound learner and group to the
existing DailyPractice issue/read/review services. It preserves active account
setup, guardian approval, published sources, module rules, FSRS scheduling and
exact operation semantics. It cannot change official marks or select arbitrary
learners/groups. Existing normal app routes do not accept classroom credentials.

Verification covers both isolated HTTP and real classroom/manager browser
journeys: wrong school/device/Teacher/group/learner, malformed or replayed
selection, expiry/revocation/frozen card/withdrawn approval and eligibility,
reference-only receipts, no ordinary session tokens or gate logs, source-bound
practice and account/context erasure. Physical tablets/cameras, source policy,
staging and production acceptance remain separate evidence.
