# Incident

What to do when an alert fires or a school reports a problem: confirm, contain, find the cause, fix, tell people, and write it down. It is written for one engineer working alone.

**Status.** The alerts it refers to are created by `infra/gcp/provision.sh`; none has fired yet because the environments are not provisioned.

## First five minutes

1. **Confirm.** Open the alert, then the panel's sign-in page and `/up/worker` in a browser. Is it real, is it production, how many schools and profiles are affected?
2. **Note the time** and keep a running log (what you saw, what you did, with times) in the incident issue.
3. **Contain** if users are hurt now: a bad release is rolled back first and investigated after ([rollback.md](rollback.md)).
4. **Tell the product owner** for anything users notice; production fixes are reported the same day.
5. **Security or personal information?** Record it in the HQ incident register (`/saas/incidents`) at once, with the schools it affects. If personal information was, or may have been, reached by someone not authorised, tell each affected school (its Information Officer, by phone first) immediately and record when and whom; each school decides whether to notify the Information Regulator and the people affected. See [HQ incidents](../specs/platform/hq-incidents.md).

## Alerts and what they mean

| Alert | Means | Look at |
| --- | --- | --- |
| `<env>: panel is down` (`/login`) | the panel does not answer | Cloud Run `navigateed-admin` revisions and startup logs; Cloud SQL status |
| `<env>: API is down` (`/up`) | Laravel does not boot | the same; usually the database or a missing secret |
| `<env>: worker is down` (`/up/worker` 503) | a due job has waited more than 10 minutes: the worker is not taking jobs | worker logs; the `jobs` table |
| `<env>: worker not running` | no worker instance for 10 minutes | `navigateed-worker` revisions and logs |
| `<env>: queued job failed` | a job used up its 3 tries | the "Queued job failed" entry (job name and id), then the worker's error lines with the same `job_id` |
| `<env>: errors above threshold` | more error lines than usual in 5 minutes | Error Reporting, grouped by stack trace |
| `<env>: scheduler runs failing` | `schedule:run` executions fail | `navigateed-scheduler` executions and their logs |
| Error Reporting e-mail | a new kind of error | the error group's stack trace and the request id in its log entry |

## Where to look

**Logs** (Cloud Logging, or `gcloud logging read '<filter>' --project=<project> --freshness=1h --limit=50`). Every line from the panel, worker and scheduler is JSON (`LOG_CHANNEL=json`) with `request_id`, `user_id`, `school_id`, `route` and `method`, and jobs add `job` and `job_id`. Useful filters:

```
resource.type="cloud_run_revision" AND resource.labels.service_name="navigateed-admin" AND severity>=ERROR
jsonPayload.request_id="<X-Request-Id from the user's report or browser>"
jsonPayload.message="Queued job failed"
jsonPayload.job_id="<id>"
jsonPayload.route="api/student/timetable"
resource.type="cloud_run_job" AND resource.labels.job_name="navigateed-scheduler"
```

A user's screenshot or browser network tab shows the `X-Request-Id` response header; that id finds every log line of their request, and of any job it queued. Logs hold ids only, never names or message contents; look the ids up in the panel.

**Error Reporting** groups exceptions by stack trace with first and last seen times and the release (`serviceContext.version` is the tag or commit).

**Cloud Run**: revisions and traffic (`gcloud run revisions list --service=<service> --region=africa-south1 --project=<project>`), instance counts and request latency on the service's Metrics tab.

**Cloud SQL**: the instance's overview (CPU, memory, connections, storage) and Query Insights for slow queries.

**The queue**, from Cloud SQL Studio:

```sql
SELECT queue, COUNT(*) AS waiting, MIN(FROM_UNIXTIME(available_at)) AS oldest FROM jobs WHERE reserved_at IS NULL GROUP BY queue;
SELECT id, uuid, queue, failed_at FROM failed_jobs ORDER BY failed_at DESC LIMIT 20;
```

## Common failures

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| New revision never ready after a deploy | migration failed, or a secret has no value | read the revision's startup logs; the old revision still serves; fix forward or [rollback.md](rollback.md) |
| 500 on every page, logs say access denied for the database user | database password secret and Cloud SQL user out of step | set the user's password from the secret ([environments.md](environments.md), "Secrets") |
| 400 Bad Request on every page | the host is not `APP_URL`'s or in `TRUSTED_HOSTS` | set `APP_URL` in the env file and re-run `provision.sh` |
| `/up/worker` 503, worker running | a job hangs or fails in a loop; or the worker cannot reach the database | worker logs by `job_id`; failed jobs above; redeploy the worker to restart it |
| Jobs fail with timeouts | a job longer than 120 seconds | split the work; the job class can set a longer `$timeout` up to `retry_after` (180 seconds) |
| Scheduled task did not run | scheduler trigger paused, or job executions failing | `gcloud scheduler jobs describe navigateed-scheduler-every-minute --location=africa-south1 --project=<project>`; executions list |
| Cloud SQL CPU or connections at the limit | a slow query or a traffic spike | Query Insights; raise `ADMIN_MAX_INSTANCES` only with the tier in mind |

## One-off Artisan commands

The image's `artisan` role runs one command and exits. Run it as an execution of the scheduler job, which already has the database, secrets and uploads mounted; the overrides apply to that execution only:

```bash
gcloud run jobs execute navigateed-scheduler --region=africa-south1 --project=<project> --wait \
  --update-env-vars=CONTAINER_ROLE=artisan --args='queue:retry,<reviewed-failed-job-uuid>'
```

Other useful ones: `--args=queue:failed`, `--args=migrate:status`, `--args=schedule:list`, `--args=permission:cache-reset`, `--args=db:seed,--class=InstallationSeeder,--force`. The output is in the execution's logs. Retry only reviewed failed-job UUIDs once their cause is fixed. A provider timeout does not prove it rejected the request: preserve the original operation/receipt and reconcile the unknown outcome before retrying. Do not dump job payloads or exception text into general incident logs. See [Phase 3/4 recovery](phase-3-4-recovery.md) for parked outbox events, fresh-recipient suppression and retention boundaries.

## Afterwards

Within two working days, write a short review in the incident issue (and, for a security incident, as the closing review in the HQ register): what happened, the impact (schools, profiles, duration), the timeline, the cause, what fixed it, and the follow-up issues (a test, an alert, a runbook change). Update this runbook if a step was missing. No blame; the aim is the next fix being faster.

## See also

[rollback.md](rollback.md) · [restore.md](restore.md) · [deploy.md](deploy.md) · [environments.md](environments.md)
