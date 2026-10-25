# Clinical UTC instant adoption

Casework explicitly normalizes appointment starts/ends and case-entry happened-at
values to UTC before storing them. Those three columns now use UtcInstant, so a
selected school's timezone does not reinterpret their naive stored UTC values.
Local created/submitted timestamps and calendar dates are untouched. No data
rewrite is necessary for these columns' existing UTC write contract.

The real Casework regression creates an entry and appointment at 08:00Z under
Africa/Johannesburg, verifies the exact stored SQL value and fresh model values,
then checks appointment listing and JSON still return 08:00Z/08:30Z. Full clinical
domain/API qualification passes 27 tests / 648 assertions; explicit Pint and
whole-app Larastan pass. Authorization, case grants, appointment conflicts,
private reporting and readonly/profile checks remain covered by that cohort.
Shared UTC cast roundtrip evidence is recorded separately. No external deployment,
physical device or calendar-provider integration was performed.
