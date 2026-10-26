# Isolated backend CI runners

The full backend suite previously ran serially in the same job as formatting,
static analysis and real socket/scanner checks. A current run had already spent
over an hour in the test step, delaying feedback on the integration branch.

The PHPUnit suite now runs in four GitHub jobs, each with its own checkout,
MariaDB service and private filesystem. There is no concurrent access to a
shared test database or storage directory. Discovery reads every configured
PHPUnit test directory, sorts its test files and assigns each file exactly once.
Unsupported discovery configuration, overlapping directories and empty shards
fail explicitly rather than silently omitting tests. Data providers remain with
their test class. Local `php artisan test` is unchanged.

The existing `Admin panel (PHP tests)` check is an aggregate that succeeds only
when **all four shards** and the separate formatting/static/cache/real-ClamAV/
Reverb/Apache checks succeed. It also fails when a dependency fails or is
cancelled. No test or runtime qualification is removed.

Local verification: the two partition regression tests passed. Actual PHPUnit
discovery on the integration tree found exactly **2,923 test cases**; the union
of all four generated configurations matched that complete list exactly, with
no duplicates or omissions. This verifies discovery, not execution of those
tests; the new GitHub run must execute every shard before merge.
