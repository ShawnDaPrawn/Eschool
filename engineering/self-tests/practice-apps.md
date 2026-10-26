# Daily practice client qualification

The shared client supplies an actual Teacher question bank/editor and a Student
self-study reader. Classic Teacher and dedicated Facilitator Teacher profiles
use the same editor. The family app offers practice only to approved Students;
the Parent profile cannot submit answers on behalf of a learner.

Teachers select their current group, published topic and approved source, then
approve decimal or multiple-choice questions with worked explanations. Each edit
uses an immutable revision and exact operation UUID. Existing arbitrary valid
estimated durations remain editable. Unknown outcomes freeze the original input;
refusals expose the error/retry controls even after submitting a long form.

Learners resume their own group/day set, answer one question at a time, view
worked feedback after answering, and synchronize their study schedule. This
never writes official marks. The unranked streak comes from confirmed server
completion dates. AI question generation remains a separate unfinished feature.

Explicit offline copies use a separate AES-encrypted Hive box and a secure-storage
key scoped to environment/account/school. The conservative server-relative lease
is at most 24 hours. Clock rollback refuses access. Copies preserve exact queued
review UUIDs for at most seven days; expired question bodies are removed on
access, and pending work is sent only after fresh learner/source authorization.
A missing or read-only cached grant cannot queue answers. Fresh group withdrawal
removes affected copies before offline reopening. A known authorization refusal
also erases the durable scope key, even if the route closes during that erasure;
a late old-scope erasure cannot delete a replacement account/school key.

Twenty copies are allowed; the twenty-first is refused, preserving pending work.
Copies can be explicitly removed with their pending work. Temporary disk/network
failures preserve existing exact operations. Changing school/account, signing out,
or a known role/approval/module withdrawal erases the old key. Backgrounding
removes rendered question bodies and requires refreshed access on resume.

Focused qualification currently passes 33 tests: real encrypted Hive files and
restart, no plaintext prompts/explanations on disk, capacity preservation,
scope/late-generation erasure, clock/retention limits, exact retry, revoked source,
read-only grant, wrong response identity, private cookie headers/log redaction,
identical-profile generation invalidation, and editor workflows at 390/820/1280.
The filesystem/crypto tests use the production Hive store with the secure-storage
plugin's test adapter; they do not establish physical OS keychain acceptance.

The full shared Flutter suite passes: 458 tests. Actual Chrome at 390px passes
for classic Teacher approval and source withdrawal; Facilitator Teacher approval
and fresh-profile erasure with the legacy auth box unopened; and Student answer,
worked feedback and withdrawal, plus the Parent profile having no practice entry
or requests. Three explicit Chrome CI targets preserve those checks. Provider execution is disabled/unqualified by default;
none of these checks makes a paid model call or uses production records.
