# Shared private wellbeing inbox

The existing clinical and identified-reporting screens now contain an inline
private-updates mode, so the owning account/context generation also owns and
disposes the inbox. Dedicated Facilitators need neither a TeacherModel nor the
legacy Firebase bootstrap. Staff and reporter repositories pin account/school,
use private requests, narrow their area, and guard authority before/after reads.

Only fixed generic text, unread metadata and first-read receipts are rendered.
Opening a notice acknowledges its own receipt and returns through a fresh source
check. The foreground roundtrip preserves an unsent case note or report draft;
background/context replacement erases it. A changed case revision retains the
existing draft comparison flow. Withdrawn access erases the private reader and
late background/disposed responses are discarded. Read-only does not disable
permitted notices or read acknowledgements. No generic notice opens a report by
ID or embeds a private follow-up code.

All 51 initial focused wellbeing repository/session/widget/photo tests pass,
including 390/820/1280 inbox layouts, explicit read, foreground draft roundtrip,
withdrawal and late lifecycle response. Scoped source/test analysis reports zero
errors/warnings (40 style informational messages). Two additional cookie-header
and audience-narrowing repository cases are included in the full shared run.
The first added lifecycle test incorrectly awaited a void framework callback;
that test fixture was corrected before the final passing focused run.

Actual Chromium full-app checks pass for the dedicated Facilitator, learner and
Parent. Each opens a generic notice, acknowledges it and returns through fresh
access with an unsent draft retained; existing case/report writes, photo upload,
private-cache checks and access withdrawal still pass. Facilitator checks verify
all wellbeing requests pin account/school and no legacy authBox opens. Family
checks continue to cover both profiles and erased private content on withdrawal.

The inherited family-content CI failure used a fixed offline lease that had
expired as the test clock advanced. Its smoke fixture now creates a bounded
one-hour lease from the current clock; production expiry logic is unchanged.
The actual Parent learning-content Chrome flow passes, including saving and
reading offline under that valid lease and preserving selected-child isolation.
Local Android and iOS builds were not run for this slice; CI/native and physical
device/provider acceptance remain separate evidence. No live email/push sent.

All offered app catalogues contain the new labels with explicit English fallback
where a translation is pending. No unimplemented route/menu was introduced.

The full shared academics suite passes all 372 tests, including both additional
cookie account/school header and staff/reporter area checks. Completed ignored
Flutter build outputs were reclaimed after qualification; source and evidence
logs were retained.
