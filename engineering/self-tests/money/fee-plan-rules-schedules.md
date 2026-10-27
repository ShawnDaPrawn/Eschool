# Fee-plan rule and schedule qualification

Initial rule/schedule and existing plan/billing checks passed **48 tests /359
assertions**, including real1,200-learner billing. The final source adds complete
schedule sealing and an explicit balanced-but-incomplete-invoice rejection test;
its broader regression result is tracked on the feature PR.

A review replaced a full snapshot scan per inserted line with an indexed learner
map and per-account schedule budgets. The final guards preserve reviewed data and
avoid multiplying the school-sized snapshot walk by every inserted installment.
An initial interrupted regression was retained as development evidence, not passed.

Initial tests corrected canonical grade-phase fixtures and the existing `items`
pagination key. A wrong-family test now uses a fresh UUID so it tests ownership;
reusing the previous UUID correctly conflicts before attempting another write.
Browser checks caught Laravel normalizing blank optional fields to null. The form
now handles blank rows explicitly, rejects malformed types, and returns private
422 refusals for invalid schedule text without session flashing. No access, money,
layout or error assertion was removed.

Final fresh-schema backend/browser/static and hosted results are recorded on the
feature PR. Staging, fluent translations, physical devices and independent live
money acceptance remain separate release evidence. No gateway activation.

Final Money regression passed **153 tests /1,236 assertions** with the indexed
line and complete-invoice SQL guards. It includes a balanced partial posting that
is refused at invoice sealing, immutable dates and1,200-learner billing. A genuine
pre-schedule invoice survived up/down/up unchanged: one posted entry, one invoice,
two original lines; existing line dates remain null-fallback rather than rewritten.

Final Finance/Teacher browser checks passed **4 cases**, zero automatic retries,
at390/820/1280px: typed campus rule, configured schedule, per-learner option, review,
approval/posting/replay, exact five-line annual total, accessible layouts, no-store,
overflow/duplicate IDs and console/HTTP assertions. An earlier partial browser run
hit ENOSPC and is retained as failed infrastructure evidence. Generated build output
was cleared and the complete four-case run passed; no assertion was waived.

Baseline-free Money level5 and configured panel analysis, fresh-cache Pint,
TypeScript, actual CI-project discovery of all four cases and docs build passed.
