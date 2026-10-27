# Fee-plan rules and real payment schedules

Authorized Phase5 increment3 extends the existing annual plan, preview, family and
invoice workflow. Grade/class/all targeting remains. Additional rules are ANDed;
any listed value can match within a rule. Grade phase uses canonical grade data.
Campus, boarding and other learner attributes use eligible school-owned profile
fields, including encrypted custom values and supported identity sources. Only
non-hidden, non-sensitive fields visible to school administration are eligible.
Archived categories/definitions, unreadable encryption or newly unavailable fields
produce explicit affected-plan exceptions. A financial permission grants no health
profile export. Raw profile values never enter the bill snapshot, transport or audit.

Matching uses dated enrolments and the currently authorized profile values at
preview/review time. It does not invent historical profile values. Ambiguous plans,
missing grades/fields and incomplete enrolment dates remain reviewable exceptions.
Changes affecting eligibility invalidate the bill HMAC before approval/posting.
Rules are bounded typed data, with no caller expressions, column names or raw SQL.

A plan offers up to six named payment options, each with either the run's fallback
due date or up to24 relative month/day and percentage installments totaling100%.
The school explicitly chooses the default; Finance can select another option per
learner/year in the family workbench before tuition is posted. A plan/selection
mismatch is an exception. Selection writes bind actor/school, learner-family,
revision and permanent UUID. Posted schedules remain on their original invoices.
Copy-forward preserves relative dates and revalidates the target year, including
leap dates and non-calendar school years. School policy is not guessed.

Each holder's annual integer cents are split by largest remainders, chronological
ties, across installment weights. Each holder and learner annual total is exact;
individual installment totals reflect those holder-cent roundings. Zero-total
installments are omitted. Reviewed due dates and exact holder shares are visible
before approval. Posting seals all installments atomically in the existing annual
invoice and balanced receivable/income entry; scheduling never creates extra debt.

An immutable line carries its installment number/date. Legacy immutable lines keep
slot1 and their invoice due date; no old posted invoice is edited. Statements,
allocation order, aged balances, reminders and CSV charge details use each actual
line due date, so future installments are not prematurely overdue. Existing API
fields and context-bound family app pages continue to work with paginated lines.
No gateway, debit mandate, parent Pay now change or late-charge policy is added.

SQL validates each new annual line against its indexed reviewed learner schedule,
matching account, school/year, plan revision, period, description, slot, date, cents
and holder shares. A complete-schedule seal guard checks reviewed line count and
annual total. Annual tuition stays unique to one invoice per learner/year across
all slots. Item charges retain their separate occurrence/learner uniqueness.
A populated expanded schedule/configuration refuses destructive rollback. A genuine
pre-schedule posted invoice is rehearsed through up/down/up without financial edits.

Qualification covers rules, real other-school definitions, guardian/holder boundaries,
changed previews, typed/null form parsing, selection/replay, wrong role/family/school,
invalid/duplicate/leap/out-of-year dates, exact rounding, actual due-date allocation,
reminder suppression, copy-forward, partial-invoice refusal and 1,200-learner billing.
Finance browser journeys include full review/post/replay at390/820/1280px and Teacher
refusal, accessibility/private headers/overflow/errors and exact ledger totals.
External fluent language, staging/device and independent money review are recorded
separately; they do not freeze unrelated increments.
