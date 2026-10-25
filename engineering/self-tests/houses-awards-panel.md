# Houses, awards and assembly — staff panel qualification

The existing Material 3 school panel now provides named scoped learner choices,
configured house emblems/colours, aggregate counters, point issue/reversal and
reasoned dated house moves; reusable award definitions/categories, year nomination
windows, category appointments, reviewed nominations, presentations, outside awards
and retained certificate corrections. Assembly mode omits nomination and review
reasons. Permanent awards remain separate from house-point history.

37 MySQL service/API/panel tests pass (321 assertions), with the configured
whole-application static analysis and full Pint check passing. The last fixture
format check is separate. HTML cases verify point/reversal and award workflows,
whole-shell rendering, account/school binding, family refusal, escaped private
validation without session old-input and read-only-year handling. Added regressions
cover SDK optional-null normalization, revoking an existing category appointment
after staff suspension and documented wrong-year conflicts.

Actual Chromium workflows passed at 390, 820 and 1280 pixels: issue five points,
append a reasoned reversal, nominate, review, switch to assembly, present an
approved award and retain a certificate correction. Each checks accessibility,
horizontal overflow, duplicate IDs, own-origin errors and escaped script text.
A real low-contrast learner link was corrected before the passing run. The browser
harness was also corrected to open a native details summary before finding its
hidden form and to use isolated contribution text and observed counter baselines
while retaining prior synthetic ledger rows. Synthetic browser configuration uses
a 500-point allowance to avoid exhausting it during repeated rehearsals; domain
tests separately prove the default 50-point cap and school-local rollover.

The panel clears its rendered private contents on background/navigation and reloads
on resume. Fresh account/school checks precede every mutation, including read-only
refusals. Known validation text returns in a no-store page, never session flash.
Counter polling is a fallback and is not evidence for the two-second live target.
TypeScript and JavaScript syntax checks pass. No real recipients or provider calls
are used by these fixtures.

Native app workflows, private notice integration and live latency follow separately.
Device/staging/first-user/fluent-language acceptance and production activation are
not established by these tests.
