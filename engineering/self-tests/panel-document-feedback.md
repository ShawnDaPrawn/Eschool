# Panel save feedback

Hosted source qualification exposed successful notification/profile/grade saves
whose redirect page had no success message. Browser traces showed Home widget
requests overlapping navigation; session locks already prevent stale writes, but
background responses still aged the next document's flash feedback.

A narrow web/stateful-API middleware retains only existing flashed feedback keys
on AJAX, fetch, script and JSON responses. The next document consumes them once.
Private old-input/error bags do not gain extra lifetime, and bearer API responses
do not start a session. Existing school/year bindings and locks remain in force.

Real file-backed session middleware and independent-process file/database lock
tests passed7 cases/20 assertions. With four PHP workers and two Chromium workers,
notification rules, profile configuration/private files and teaching groups passed
all8 browser journeys with zero retries. Initial local missing-avatar errors were
resolved by the normal storage symlink; no product/test assertion was weakened.
Configured PHPStan, fresh formatting and TypeScript passed. Additional school/year/
refund regression and final hosted results are recorded on the PR.

School switching, historical years, manual refunds and feedback regression passed
31 tests/214 assertions on this source. Hosted exact-source qualification remains
required before merge.
