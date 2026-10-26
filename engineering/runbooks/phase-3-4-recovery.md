# Phase 3/4 recovery handover

This is a source and synthetic-test audit of main `990ea2a7b`, followed by the
bounded outbox corrections recorded below. It is preparation for Phase 5, not a
production recovery drill or permission to activate providers. Use the deployed
release's configuration and approved incident process before running commands.

## Find the failure boundary

| Boundary | Durable evidence | Recovery behavior |
| --- | --- | --- |
| Business write | Feature operation UUID, keyed immutable intent, canonical revision and receipt | Reauthorize and retry the **same** request, or query its outcome. A timeout/404 after an uncertain write does not prove that nothing committed. |
| Transactional outbox | `domain_events.id`, school, type, attempts, availability and dispatch time | Listener writes roll back on failure. Due events retry with configured backoff; exhausted events park. Live acceleration and the minute dispatcher obey the same parked limit. |
| Notice fanout | Original notification ID and per-recipient/channel claim | Resume the same source intent. Existing claims prevent a fanout retry from repeating previously queued recipients. Fresh source, membership, module, destination and recipient choice are checked again. |
| Mail/push transport | Delivery key, per-attempt status, provider message ID and `failed_jobs` UUID | A provider may accept a request before its response is lost. Transport is not an exactly-once payment boundary. Reconcile provider evidence before a manual retry; fresh access may suppress a stale notice. |
| Temporary private copies | Feature expiry/purge marker and storage path under its own school | Use the feature cleanup command. Do not replace it with a bulk table or bucket deletion. Retain canonical records, operation receipts and metering where the feature requires them. |

`DispatchOutbox` catches per-event failures: its queue job can complete while
`outbox_listener_failed` or `outbox_event_parked` is recorded. Permanent push
refusals can similarly produce a failed delivery without a `failed_jobs` row.
Monitor these structured events and delivery statuses as well as the queue.
`/up/worker` measures queue waiting age; it does not certify every parked event
or a remote provider's delivery result.

## Inspect metadata first

Use the trusted operator database connection in UTC. Keep payloads, addresses,
message bodies, exception text and encrypted job blobs out of incident exports.
For example, bind the school and configured maximum-attempt count rather than
copying a school's data into a general-purpose log:

```sql
SELECT id, school_id, type, attempts, available_at, dispatched_at
FROM domain_events
WHERE school_id = :school_id AND dispatched_at IS NULL
ORDER BY id LIMIT 50;

SELECT id, uuid, queue, failed_at
FROM failed_jobs ORDER BY failed_at DESC LIMIT 20;

SELECT delivery_key, type, channel, status, attempt, provider,
       provider_message_id, queued_at, sent_at, failed_at
FROM notification_deliveries
WHERE school_id = :school_id
ORDER BY id DESC LIMIT 50;
```

Compare `attempts` with `platform.outbox.max_attempts`; due rows below the limit
are eligible for `php artisan outbox:dispatch`. Future rows remain in backoff.
This command reports event failures in its summary; its current process exit
code alone is not an outbox health check.

After fixing the cause, retry only an identified failed queue UUID:

```bash
php artisan queue:failed
php artisan queue:retry '<reviewed-failed-job-uuid>'
```

Do not start with `queue:retry all`, delete delivery/channel claims, recreate a
business operation with a new UUID, or erase a receipt to make a retry succeed.
For unknown remote outcomes, first preserve the original operation and reconcile
its provider ID/status. Bounces, complaints, invalid addresses/tokens, revoked
access and expired consent are not transient failures to override.

There is no general operator unpark command. A parked event needs a reviewed,
event-specific repair after its listener/source problem is fixed. If a repair
resets its delivery state, lock and recheck the exact event ID, school, type,
attempt count and absent dispatch time; retain the original payload and all
business receipts. Record the incident and reason. Direct `dispatchOne` is not a
way to bypass the parked limit. Do not mass-reset attempts or replay external
financial effects based only on an outbox error.

## Expiry and retention

The scheduler is defined once in `admin-panel/routes/console.php`.
`php artisan schedule:list` verifies the installed release's entries; it is not
proof that a deployed scheduler/worker has executed them. Before an approved
manual cleanup, verify the actual feature policy, school scope and private disk.

| Scheduled command/job | Copy boundary to preserve |
| --- | --- |
| `ApplyRetention` / `academic-export-retention` | Temporary academic exit artifacts expire; canonical educational records and retained receipt metadata remain. |
| `transfers:prune-copies` | Temporary package/staging copies expire; accepted canonical receipts are a separate record. |
| `admissions:maintain --retention` | Due unsuccessful application copies; accepted reservations/enrolment and canonical learner history are guarded separately. |
| `gallery:prune-copies`, `activities:prune-material-files` | Only owned retired/expired paths and recognized old orphan derivatives; no general bucket sweep. |
| `digest:weekly --retention` | Expired encrypted digest copies; these are not permanent financial receipts. |
| `ai:prune-quarantine`, `exam-coach:prune-working`, `learning:prune-teaching-drafts` | Temporary feature media/proposals; canonical published school records and metering remain separate. |
| `assistance:prune-private`, `chat:prune-groups` | Expired private bodies are erased/redacted while minimal source/operation evidence is retained by the feature. |
| `model:prune --model=App\\Models\\Platform\\IdempotencyKey` | The 24-hour HTTP response replay cache only; not a durable business receipt. |

Expiry must refuse a read immediately; a delayed cleanup does not extend access.
Privacy cleanup must still run when the relevant module or school is paused.
Do not invent a legal retention period or automatically purge failed jobs and
unresolved outcomes. Such records need their approved retention/incident rule.

Outbox instants are UTC at creation, persistence, query and reload. A school's
display timezone must not change a queued backoff. The correction does not
rewrite historical rows: if an existing installation has non-UTC legacy values,
preserve and review them against known creation context before a scoped repair;
do not guess a timestamp conversion from the school's current timezone.

## Financial reuse boundary

Phase 5 needs a durable, school-scoped business receipt/unique intent in the same
transaction as postings. The generic HTTP idempotency cache expires after 24
hours and can release a stale in-flight request after five minutes; neither
expiry proves a financial operation did not commit. Queue retry, notice retry,
temporary-copy retention and provider callbacks must not reset that business
identity or delete canonical postings. A notice is a derived delivery effect,
not evidence that money settled. No new payment or ledger flow is introduced by
this audit.

## Joined screen-state review

The merged baseline already has exact-intent recovery and context-generation
retirement in group chat, family consent/Health, meetings/collection, assistance,
private notices and the private document reader. The bounded source review
followed loading/empty/read-only states, immutable retry, late-response refusal
and account/school/role/guardian withdrawal through those owners and repositories.
No additional actionable screen defect was established. No app source changed
and no new phone/browser run is claimed here; the final baseline's actual app
and 390/820/1280 evidence remains linked from the phase verification records.

New operational evidence: three real-database regressions failed before the
outbox correction (parked acceleration, Honolulu-to-UTC backoff and a UTC-to-
Kiritimati worker). The full outbox/live-publisher/notifier/queued-delivery cohort
then passed 42 tests / 195 assertions. Five existing assistance/chat/teaching/
digest expiry and retention checks also passed (26 assertions), covering
immediate refusal, body erasure and retained evidence with disabled modules.
Selected analysis of the three changed production files reports zero errors;
changed PHP passes Pint, new documentation links resolve, and scoped secret and
whitespace checks pass. The handover PR's hosted CI remains separate from this
local evidence. Deployed alerts, recovery drills, provider reconciliation and
real-device acceptance remain unrun.
