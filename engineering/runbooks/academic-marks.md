# Academic marks operations

Migrate before enabling the new client and run the ordinary queue worker and
every-minute outbox scheduler. Publish the school-approved term assessment
definitions, map grades, and set dated groups/enrolments/teacher appointments.
Generic DBE ratios in tests are worked examples, never automatic school policy.

Capture and moderation use revisions. On409 reload and compare; never overwrite
a stale row. A grade-head correction needs a reason and requires fresh
moderation. Lock dates use the school timezone. Complete term results can be
released while annual/future work continues.

Release preview selects learners and returns an evidence fingerprint; commit
requires that fingerprint. Official mode needs complete selected results. Running
mode is explicitly provisional and does not replace v1 official exam results.
A later release appends history; existing releases never change. Fees never gate
academic release or family access. Seven-point ratings do not imply promotion.

## Weekly sheet rehearsal

Keep the sheet outside public storage, with these exact CSV headers:

```csv
student_number,subject_code,percentage
2600000018,MAT,75.05
```

The example is synthetic, not an enrolled learner or real pilot evidence. Use
valid platform student numbers from the imported school and its unique subject
codes. Decimal percentages have up to two places, with a dot.

```sh
php artisan marks:reconcile 1 1 --sheet=/private/pilot-week-01.csv --refresh
```

The command emits JSON with exact basis points and snapshot IDs. Exit0 requires
every row to match; differences, incomplete evidence, missing identifiers and
ambiguous codes return failure without overwriting marks. Duplicate rows and
malformed/bounded inputs are refused. Keep the JSON and original sheet privately
with a written explanation/resolution for every difference. A synthetic passing
rehearsal does not satisfy the full-term parallel-run requirement.

## Recovery

Retry failed recompute jobs after fixing their cause. Identical input fingerprints
create no extra snapshot. Release/event retries do not mutate pinned evidence.
`--refresh` provides an explicit recomputation before comparison. Back up before
migration; once results have been released or fractional legacy marks recorded,
rollback must restore a tested backup. The migration refuses a destructive down
that would discard that evidence. No production deployment is authorized here.
