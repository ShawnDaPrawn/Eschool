# Parent and office assistance clients

Shared clients implement approved document reading and selected-source review,
explicit Parent-to-office handoff, original-preserving message translation,
scoped office claim/reply/reassignment and approved document versions. Parent
routes pin the selected child; every request pins account/school and every context
loading generation retires the old reader. Private bodies retire on background,
source denial and bounded foreground expiry. Uncertain or malformed submitted
results retain only their encrypted exact intent for bounded replay.

Automated qualification:

- Shared: 13 widget, 4 transport and 7 context-boundary cases pass. They include
  390/820/1280 widths, read-only/absent capability, selected-child response binding,
  exact retry through background/404/malformed2xx, source denial, late response
  rejection, review expiry and preventing a refreshed editor cloning a document.
- Family: 18 unique entry/notice-action/catalogue cases pass, including actual
  reader/school markers, wrong portal/account/role, loading retirement, trusted
  deep links and the existing language/glossary checks. Teacher: 7 entry/catalogue
  cases pass. Approved SA glossary terms are retained; new wording otherwise has
  explicit English fallbacks without claiming fluent translation review.
- Shared and both selected app analyses have zero errors/warnings; existing
  style infos remain. Native current Teacher/Facilitator principal availability
  and appointment withdrawal passes 1 / 14.

Actual Chrome journeys passed with process exit 0 for all three profiles:

- Parent: actual home entry, approved-source reader, selected-source review,
  explicit handoff that copies only the typed question, original-preserving reply
  and translation review, recipient/school-bound notice action and withdrawal
  erasure. Log: `assistance-parent-browser-second.log`.
- Classic Teacher with a current principal appointment: actual office entry,
  explicit claim before reading, office-persona reply, original-preserving
  translation review and reasoned reassignment that erases both private bodies.
  Log: `assistance-teacher-browser-first.log`.
- Dedicated Facilitator: actual private staff inbox action and read marker,
  the scoped office journey, then fresh feature withdrawal removes the reader
  and its bodies while legacy Hive `authBox` remains closed. This ran with both
  `--flavor=facilitator` and `--dart-define=FLAVOR=facilitator`.
  Log: `assistance-facilitator-browser-first.log`.

The first Parent browser run exposed a fixture-only scroll issue: the fixed home
header covered an entry after alignment at the top. The corrected helper centers
and verifies the actual hit-testable control before tapping; all meaningful
source, recipient, original-message, replay and erasure assertions are retained.
Three explicit Chrome CI matrix rows cover these profiles, with the separate
Facilitator flavor preserved; actionlint and YAML matrix parsing pass. Android
keeps discovering the new integration targets.

Logs above are retained in the private execution scratch directory. Their
synthetic transports exercise actual app orchestration and privacy boundaries;
they do not qualify provider output quality. Office backend/panel/notices are
independently qualified in `school-assistance-office.md`. Translation here covers both
directions of Parent↔assigned-office support messages; activity-group chat
translation has a separate source/purpose scope. Provider activation, semantic
translation qualification, physical devices and production acceptance remain
separate evidence.
