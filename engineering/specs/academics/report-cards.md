# Report cards

Phase 2 adds report design, comment banks, grade-head then principal approval,
queued private PDFs, and explicit bulk release. Fees never enter any report
eligibility, download, delivery or release decision. Existing exam/result/report
routes keep their installed-app contracts.

School templates select ordered sections (subject table is mandatory), headings,
plain-text introduction/footer, comment blocks and optional school crest. Template
revisions affect new drafts; an existing report retains its selected design.
Report drafts contain immutable mark snapshot references and copied identities,
labels, attendance summaries and design. Staff comments are encrypted; allocated
subject/homeroom teachers edit only their permitted comment scope. Comment edits use per-scope revisions, so independent draft comments can progress
together. A changed approval revision requires review; accepted edits reset prior
signatures and generated artifacts.
Published cards remain immutable; corrections create a new version.

A batch selects one homeroom, school year/term (or annual period) and explicit
learners whose dated enrolments overlap the period. Preview shows incomplete
results; a selected subset can progress without holding the rest of the school.
Official reports require complete results. Explicit provisional reports use the
running mark and carry a visible provisional label. Missing plans/marks cannot
silently become zero. Provisional reports explain ungraded, excused and absent
results without inventing a percentage. Preparation recomputes canonical snapshots; sign-off and
release recheck the source fingerprint so changed marks/rosters cannot publish an
unreviewed result. The workflow does not automatically activate learner accounts.

Current dated grade heads sign first, current dated principals second. Current
appointments govern their school/grade report history, including earlier years; a
calendar rollover does not make historical reports impossible to correct. School
managers/current report leaders may correct comment scopes with an encrypted
audit reason. Ordinary teachers retain current allocation/ownership checks. There is
no generic admin bypass for signatures. These are report workflow requirements,
not a development freeze. Signed batches queue one bounded job per learner; jobs
recheck the approved content version before committing artifacts. Retries retain
private artifacts and cannot generate notices twice. Report PDFs use escaped
text and embedded, validated local crest bytes with remote resource loading off.
Encrypted PDFs live on a private disk, never public storage.

Only released cards appear to the learner/active own-child guardian. Downloads
need fresh ownership plus a signed, expiring URL bound to school and requester;
a copied link grants no other account access. Staff preview requires fresh
academic authority. Responses are private/no-store. Report notices use Notifier,
outbox and per-card recipient claims; transports recheck family rights before
sending. Family and staff history is paged so earlier versions remain reachable. A fresh
access request renews an expired family link after checking ownership and private
PDF integrity. Read state is per recipient. Release needs a preview fingerprint and
all selected PDFs ready, then one action queues every eligible parent notice.

Automated verification covers exact PDF contents, escaping/private storage,
workflow order/stale signatures/mark changes/retries, fees independence, own-child
signed downloads and expiry, wrong role/school/owner, fresh replay authority and
notification revocation. Shared app and panel screens need cubit/widget/Chrome
and 1024/1280 Playwright evidence. Actual parent delivery, device acceptance and
full-term pilot evidence remain separate operational work.
