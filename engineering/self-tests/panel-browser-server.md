# Panel browser server qualification

The full browser run on integration commit `2fc2323` lost its panel server and
then produced connection-refused failures across otherwise unrelated workflows.
Its retained server log ends without an application fatal error. The exact exit
cause is not established by that artifact.

CI now starts PHP directly with Laravel's installed router, from the public
working directory, and preserves four workers, the real application, existing
fixtures and all browser assertions. This removes the extra Artisan serve/log
parser process from the long run. There is no automatic restart. The browser
step reports a dead server and fails while retaining the original test result
when the server is alive.

Local verification used the same direct router and four workers against a fresh,
synthetic MySQL demo database. All three report workflows passed in one clean
run (1.1 minutes): preparation/review/sign/PDF preview/release at 1024 and 1280
pixels, and an allocated teacher retaining and reviewing a conflicting draft.
Actionlint and whitespace checks pass. The complete CI browser suite remains
required for the new integration head; these focused cases do not establish a
full-run pass or the precise cause of the earlier server exit.
